<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

class AdvFieldsGroupContentType extends BaseType
{
    public function handle()
    {
        if (isset($this->options->fields)) {
            // Convert to array for easier manipulation
            $data = json_decode(json_encode($this->options), true);

            // Update each field with the submitted value
            foreach ($data['fields'] as $key => &$field) {
                $value = $this->request->input($this->row->field.'_'.$key);
                $field['value'] = $value;
            }

            return json_encode($data);
        }

        return null;
    }
}
