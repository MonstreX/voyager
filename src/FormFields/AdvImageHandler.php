<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class AdvImageHandler extends AbstractHandler
{
    protected $name = 'Advanced Image';
    protected $codename = 'adv_image';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_image', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
