<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Events\FileDeleted;
use TCG\Voyager\Http\Controllers\ContentTypes\AdvInlineSetContentType;
use TCG\Voyager\Http\Controllers\ContentTypes\Checkbox;
use TCG\Voyager\Http\Controllers\ContentTypes\Coordinates;
use TCG\Voyager\Http\Controllers\ContentTypes\File;
use TCG\Voyager\Http\Controllers\ContentTypes\Image as ContentImage;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleCheckbox;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleImage;
use TCG\Voyager\Http\Controllers\ContentTypes\Password;
use TCG\Voyager\Http\Controllers\ContentTypes\Relationship;
use TCG\Voyager\Http\Controllers\ContentTypes\SelectMultiple;
use TCG\Voyager\Http\Controllers\ContentTypes\Text;
use TCG\Voyager\Http\Controllers\ContentTypes\Timestamp;
use TCG\Voyager\Traits\AlertsMessages;
use TCG\Voyager\Services\BreadFieldUploadService;
use Validator;

abstract class Controller extends BaseController
{
    use DispatchesJobs;
    use ValidatesRequests;
    use AuthorizesRequests;
    use AlertsMessages;

    public function getSlug(Request $request)
    {
        if (isset($this->slug)) {
            $slug = $this->slug;
        } else {
            $slug = explode('.', $request->route()->getName())[1];
        }

        return $slug;
    }

    public function insertUpdateData($request, $slug, $rows, $data)
    {
        $multiSelect = [];
        $isCreating = !$data->exists;

        $request->attributes->set('voyagerModel', $data);

        // Pass $rows so that we avoid checking unused fields
        $request->attributes->add(['breadRows' => $rows->pluck('field')->toArray()]);

        $translations = $this->prepareTranslations($data, $request);

        $this->fillModelFromRows($request, $slug, $rows, $data, $multiSelect);
        $this->fillAdditionalAttributes($request, $data);

        $this->handleAdvImageUploads($request, $rows, $data);

        $data->save();

        if ($isCreating) {
            $this->handleAdvInlineSetUploads($request, $rows, $data);
        }

        $this->handleAdvMediaFilesUploads($request, $rows, $data);

        $this->persistTranslations($data, $translations);
        $this->syncBelongsToManyRelations($data, $multiSelect);
        $this->renameMediaPickerFoldersIfNeeded($request, $slug, $rows, $data);

        return $data;
    }

    protected function prepareTranslations($data, Request $request): array
    {
        return is_bread_translatable($data) ? $data->prepareTranslations($request) : [];
    }

    protected function fillModelFromRows(Request $request, string $slug, $rows, $data, array &$multiSelect): void
    {
        foreach ($rows as $row) {
            if ($this->shouldSkipRowForRequest($request, $row)) {
                continue;
            }

            if ($this->isBelongsToRelationshipRow($row)) {
                continue;
            }

            $content = $this->getContentBasedOnType($request, $slug, $row, $row->details);

            if ($this->isNonBelongsToManyRelationshipRow($row)) {
                $row->field = @$row->details->column;
            }

            $content = $this->mergeExistingMultipleFieldContentIfNeeded($row, $data, $content);
            $content = $this->applyNullContentFallbacks($request, $row, $data, $content);

            if ($this->isBelongsToManyRelationshipRow($row)) {
                $multiSelect[] = $this->buildBelongsToManySyncPayload($row, $content);
                continue;
            }

            $data->{$row->field} = $content;
        }
    }

    protected function shouldSkipRowForRequest(Request $request, $row): bool
    {
        if ($request->hasFile($row->field) || $request->has($row->field) || $row->type === 'checkbox') {
            return false;
        }

        if (isset($row->details->type) && $row->details->type !== 'belongsToMany') {
            return true;
        }

        return false;
    }

    protected function isBelongsToRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type == 'belongsTo';
    }

    protected function isBelongsToManyRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type == 'belongsToMany';
    }

    protected function isNonBelongsToManyRelationshipRow($row): bool
    {
        return $row->type == 'relationship' && $row->details->type != 'belongsToMany';
    }

    protected function mergeExistingMultipleFieldContentIfNeeded($row, $data, $content)
    {
        if (!in_array($row->type, ['multiple_images', 'file'], true) || is_null($content)) {
            return $content;
        }

        if (!isset($data->{$row->field})) {
            return $content;
        }

        $existingFiles = json_decode($data->{$row->field}, true);
        if (is_null($existingFiles)) {
            return $content;
        }

        return json_encode(array_merge($existingFiles, json_decode($content)));
    }

    protected function applyNullContentFallbacks(Request $request, $row, $data, $content)
    {
        if (!is_null($content)) {
            return $content;
        }

        if ($row->type == 'image' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
            return $data->{$row->field};
        }

        if ($row->type == 'multiple_images' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
            return $data->{$row->field};
        }

        if ($row->type == 'file') {
            $current = $data->{$row->field};
            return $current ? $current : json_encode([]);
        }

        if ($row->type == 'password') {
            return $data->{$row->field};
        }

        return $content;
    }

    protected function buildBelongsToManySyncPayload($row, $content): array
    {
        return [
            'model'           => $row->details->model,
            'content'         => $content,
            'table'           => $row->details->pivot_table,
            'foreignPivotKey' => $row->details->foreign_pivot_key ?? null,
            'relatedPivotKey' => $row->details->related_pivot_key ?? null,
            'parentKey'       => $row->details->parent_key ?? null,
            'relatedKey'      => $row->details->key,
        ];
    }

    protected function fillAdditionalAttributes(Request $request, $data): void
    {
        if (!isset($data->additional_attributes)) {
            return;
        }

        foreach ($data->additional_attributes as $attr) {
            if ($request->has($attr)) {
                $data->{$attr} = $request->{$attr};
            }
        }
    }

    protected function persistTranslations($data, array $translations): void
    {
        if (count($translations) > 0) {
            $data->saveTranslations($translations);
        }
    }

    protected function syncBelongsToManyRelations($data, array $multiSelect): void
    {
        foreach ($multiSelect as $syncData) {
            $data->belongsToMany(
                $syncData['model'],
                $syncData['table'],
                $syncData['foreignPivotKey'],
                $syncData['relatedPivotKey'],
                $syncData['parentKey'],
                $syncData['relatedKey']
            )->sync($syncData['content']);
        }
    }

    protected function renameMediaPickerFoldersIfNeeded(Request $request, string $slug, $rows, $data): void
    {
        if (!$request->session()->has($slug.'_path') && !$request->session()->has($slug.'_uuid')) {
            return;
        }

        $oldPath = $request->session()->get($slug.'_path');
        $uuid = $request->session()->get($slug.'_uuid');
        $newPath = str_replace($uuid, $data->getKey(), $oldPath);
        $folderPath = substr($oldPath, 0, strpos($oldPath, $uuid)).$uuid;

        $rows->where('type', 'media_picker')->each(function ($row) use ($data, $uuid) {
            $data->{$row->field} = str_replace($uuid, $data->getKey(), $data->{$row->field});
        });

        $data->save();

        $disk = Storage::disk(config('voyager.storage.disk'));

        if (
            $oldPath != $newPath &&
            !$disk->exists($newPath) &&
            $disk->exists($oldPath)
        ) {
            $request->session()->forget([$slug.'_path', $slug.'_uuid']);
            $disk->move($oldPath, $newPath);
            $disk->deleteDirectory($folderPath);
        }
    }

    protected function handleAdvInlineSetUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvInlineSetUploads($request, $rows, $data);
    }

    /**
     * Validates bread POST request.
     *
     * @param array  $data The data
     * @param array  $rows The rows
     * @param string $slug Slug
     * @param int    $id   Id of the record to update
     *
     * @return mixed
     */
    public function validateBread($data, $rows, $name = null, $id = null)
    {
        $rules = [];
        $messages = [];
        $customAttributes = [];
        $is_update = $name && $id;

        $fieldsWithValidationRules = $this->getFieldsWithValidationRules($rows);

        foreach ($fieldsWithValidationRules as $field) {
            $fieldRules = $field->details->validation->rule;
            $fieldName = $field->field;

            // Show the field's display name on the error message
            if (!empty($field->display_name)) {
                if (!empty($data[$fieldName]) && is_array($data[$fieldName])) {
                    foreach ($data[$fieldName] as $index => $element) {
                        if ($element instanceof UploadedFile) {
                            $name = $element->getClientOriginalName();
                        } else {
                            $name = $index + 1;
                        }

                        $customAttributes[$fieldName.'.'.$index] = $field->getTranslatedAttribute('display_name').' '.$name;
                    }
                } else {
                    $customAttributes[$fieldName] = $field->getTranslatedAttribute('display_name');
                }
            }

            // If field is an array apply rules to all array elements
            $fieldName = !empty($data[$fieldName]) && is_array($data[$fieldName]) ? $fieldName.'.*' : $fieldName;

            // Get the rules for the current field whatever the format it is in
            $rules[$fieldName] = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if ($id && property_exists($field->details->validation, 'edit')) {
                $action_rules = $field->details->validation->edit->rule;
                $rules[$fieldName] = array_merge($rules[$fieldName], (is_array($action_rules) ? $action_rules : explode('|', $action_rules)));
            } elseif (!$id && property_exists($field->details->validation, 'add')) {
                $action_rules = $field->details->validation->add->rule;
                $rules[$fieldName] = array_merge($rules[$fieldName], (is_array($action_rules) ? $action_rules : explode('|', $action_rules)));
            }
            // Fix Unique validation rule on Edit Mode
            if ($is_update) {
                foreach ($rules[$fieldName] as &$fieldRule) {
                    if (strpos(strtoupper($fieldRule), 'UNIQUE') !== false) {
                        $fieldRule = \Illuminate\Validation\Rule::unique($name)->ignore($id);
                    }
                }
            }

            // Set custom validation messages if any
            if (!empty($field->details->validation->messages)) {
                foreach ($field->details->validation->messages as $key => $msg) {
                    $messages["{$field->field}.{$key}"] = $msg;
                }
            }
        }

        return Validator::make($data, $rules, $messages, $customAttributes);
    }

    public function getContentBasedOnType(Request $request, $slug, $row, $options = null)
    {
        $contentType = $this->resolveContentTypeHandler($row->type);
        if (!$contentType) {
            return null;
        }

        return (new $contentType($request, $slug, $row, $options))->handle();
    }

    protected function resolveContentTypeHandler(string $type): ?string
    {
        switch ($type) {
            /********** PASSWORD TYPE **********/
            case 'password':
                return Password::class;
            /********** CHECKBOX TYPE **********/
            case 'checkbox':
                return Checkbox::class;
            /********** MULTIPLE CHECKBOX TYPE **********/
            case 'multiple_checkbox':
                return MultipleCheckbox::class;
            /********** FILE TYPE **********/
            case 'file':
                return File::class;
            /********** MULTIPLE IMAGES TYPE **********/
            case 'multiple_images':
                return MultipleImage::class;
            /********** SELECT MULTIPLE TYPE **********/
            case 'select_multiple':
                return SelectMultiple::class;
            /********** IMAGE TYPE **********/
            case 'image':
                return ContentImage::class;
            /********** DATE TYPE **********/
            case 'date':
            /********** TIMESTAMP TYPE **********/
            case 'timestamp':
                return Timestamp::class;
            /********** COORDINATES TYPE **********/
            case 'coordinates':
                return Coordinates::class;
            /********** RELATIONSHIPS TYPE **********/
            case 'relationship':
                return Relationship::class;
            /********** ADV FIELDS GROUP TYPE **********/
            case 'adv_fields_group':
                return \TCG\Voyager\Http\Controllers\ContentTypes\AdvFieldsGroupContentType::class;
            case 'adv_inline_set':
                return AdvInlineSetContentType::class;
            case 'adv_media_files':
                return null;
            /********** ALL OTHER TEXT TYPE **********/
            default:
                return Text::class;
        }
    }

    public function deleteFileIfExists($path)
    {
        if ($path && Storage::disk(config('voyager.storage.disk'))->exists($path)) {
            Storage::disk(config('voyager.storage.disk'))->delete($path);
            event(new FileDeleted($path));
        }
    }

    /**
     * Get fields having validation rules in proper format.
     *
     * @param array $fieldsConfig
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getFieldsWithValidationRules($fieldsConfig)
    {
        return $fieldsConfig->filter(function ($value) {
            if (empty($value->details)) {
                return false;
            }

            return !empty($value->details->validation->rule);
        });
    }

    protected function handleAdvImageUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvImageUploads($request, $rows, $data);
    }

    protected function handleAdvMediaFilesUploads($request, $rows, $data)
    {
        app(BreadFieldUploadService::class)->handleAdvMediaFilesUploads($request, $rows, $data);
    }

}
