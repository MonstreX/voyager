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
use TCG\Voyager\Services\BreadMediaPickerPathService;
use TCG\Voyager\Services\BreadBelongsToManySyncService;
use TCG\Voyager\Services\BreadTranslationService;
use TCG\Voyager\Services\BreadRowFillService;
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

        $translations = app(BreadTranslationService::class)->prepare($data, $request);

        app(BreadRowFillService::class)->fillModelFromRows(
            $request,
            $slug,
            $rows,
            $data,
            fn (Request $req, string $slugValue, $row, $options) => $this->getContentBasedOnType($req, $slugValue, $row, $options),
            $multiSelect
        );
        $this->fillAdditionalAttributes($request, $data);

        $this->handleAdvImageUploads($request, $rows, $data);

        $data->save();

        if ($isCreating) {
            $this->handleAdvInlineSetUploads($request, $rows, $data);
        }

        $this->handleAdvMediaFilesUploads($request, $rows, $data);

        app(BreadTranslationService::class)->persist($data, $translations);
        app(BreadBelongsToManySyncService::class)->sync($data, $multiSelect);
        app(BreadMediaPickerPathService::class)->renameFoldersIfNeeded($request, $slug, $rows, $data);

        return $data;
    }

    // row fill logic moved to BreadRowFillService

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

    // translation logic moved to BreadTranslationService

    // belongsToMany sync logic moved to BreadBelongsToManySyncService

    // media-picker rename logic moved to BreadMediaPickerPathService

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
