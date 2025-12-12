<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

class AdvFieldsGroupContentType extends BaseType
{
    public function handle()
    {
        if (isset($this->options->fields)) {
            $data = $this->options;

            foreach ($data->fields as $key => $field) {
                $value = $this->request->input($this->row->field.'_'.$key);
                $data->fields->{$key}->value = $value;
            }

            return json_encode($data);
        }

        return null;
    }
}
