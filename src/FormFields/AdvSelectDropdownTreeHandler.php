<?php

namespace TCG\Voyager\FormFields;

class AdvSelectDropdownTreeHandler extends AbstractHandler
{
    protected $codename = 'adv_select_dropdown_tree';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_select_dropdown_tree', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
