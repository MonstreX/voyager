<?php

namespace TCG\Voyager\FormFields;

class AdvInlineSetHandler extends AbstractHandler
{
    protected $codename = 'adv_inline_set';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_inline_set', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}

