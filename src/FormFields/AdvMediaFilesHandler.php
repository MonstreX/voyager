<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class AdvMediaFilesHandler extends AbstractHandler
{
    protected $name = 'Advanced Media Files';
    protected $codename = 'adv_media_files';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.adv_media_files', [
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
