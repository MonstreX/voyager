<?php

namespace TCG\Voyager\FormFields;

class AdvJsonHandler extends AbstractHandler
{
    protected $codename = 'adv_json';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_json', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
