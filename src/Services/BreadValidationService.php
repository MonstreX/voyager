<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Validator;

class BreadValidationService
{
    /**
     * Validates bread POST request.
     *
     * @param array $data The data
     * @param \Illuminate\Support\Collection $rows The rows
     * @param string|null $name Slug/table name for unique rules
     * @param int|null $id Id of the record to update
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateBread(array $data, Collection $rows, ?string $name = null, ?int $id = null)
    {
        $rules = [];
        $messages = [];
        $customAttributes = [];
        $isUpdate = $name && $id;

        $fieldsWithValidationRules = $this->getFieldsWithValidationRules($rows);

        foreach ($fieldsWithValidationRules as $field) {
            $fieldRules = $field->details->validation->rule;
            $fieldName = $field->field;

            if (!empty($field->display_name)) {
                if (!empty($data[$fieldName]) && is_array($data[$fieldName])) {
                    foreach ($data[$fieldName] as $index => $element) {
                        if ($element instanceof UploadedFile) {
                            $displayIndex = $element->getClientOriginalName();
                        } else {
                            $displayIndex = $index + 1;
                        }

                        $customAttributes[$fieldName.'.'.$index] = $field->getTranslatedAttribute('display_name').' '.$displayIndex;
                    }
                } else {
                    $customAttributes[$fieldName] = $field->getTranslatedAttribute('display_name');
                }
            }

            $fieldNameWithWildcard = !empty($data[$fieldName]) && is_array($data[$fieldName]) ? $fieldName.'.*' : $fieldName;

            $rules[$fieldNameWithWildcard] = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if ($id && property_exists($field->details->validation, 'edit')) {
                $actionRules = $field->details->validation->edit->rule;
                $rules[$fieldNameWithWildcard] = array_merge(
                    $rules[$fieldNameWithWildcard],
                    is_array($actionRules) ? $actionRules : explode('|', $actionRules)
                );
            } elseif (!$id && property_exists($field->details->validation, 'add')) {
                $actionRules = $field->details->validation->add->rule;
                $rules[$fieldNameWithWildcard] = array_merge(
                    $rules[$fieldNameWithWildcard],
                    is_array($actionRules) ? $actionRules : explode('|', $actionRules)
                );
            }

            if ($isUpdate) {
                foreach ($rules[$fieldNameWithWildcard] as &$fieldRule) {
                    if (strpos(strtoupper($fieldRule), 'UNIQUE') !== false) {
                        $fieldRule = \Illuminate\Validation\Rule::unique($name)->ignore($id);
                    }
                }
            }

            if (!empty($field->details->validation->messages)) {
                foreach ($field->details->validation->messages as $key => $msg) {
                    $messages["{$field->field}.{$key}"] = $msg;
                }
            }
        }

        return Validator::make($data, $rules, $messages, $customAttributes);
    }

    /**
     * @param \Illuminate\Support\Collection $fieldsConfig
     *
     * @return \Illuminate\Support\Collection
     */
    private function getFieldsWithValidationRules(Collection $fieldsConfig): Collection
    {
        return $fieldsConfig->filter(function ($value) {
            if (empty($value->details)) {
                return false;
            }

            return !empty($value->details->validation->rule);
        });
    }
}

