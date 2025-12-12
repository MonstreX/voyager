<?php

namespace TCG\Voyager\FormFields;

class AdvFieldsGroupHandler extends AbstractHandler
{
    protected $codename = 'adv_fields_group';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_fields_group', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
