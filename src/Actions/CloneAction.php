<?php

namespace TCG\Voyager\Actions;

class CloneAction extends AbstractAction
{
    public function getTitle()
    {
        return __('voyager::generic.clone');
    }

    public function getIcon()
    {
        return 'voyager-documentation';
    }

    public function getPolicy()
    {
        return 'add';
    }

    public function getAttributes()
    {
        $id = $this->data->{$this->data->getKeyName()};

        return [
            'class'                   => 'btn btn-sm btn-success clone',
            'data-id'                 => $id,
            'data-confirm-target'     => '#clone_modal',
            'data-confirm-form'       => '#clone_form',
            'data-confirm-form-action'=> route('voyager.'.$this->dataType->slug.'.clone', ['id' => $id]),
            'id'                      => 'clone-'.$id,
        ];
    }

    public function getDefaultRoute()
    {
        return 'javascript:;';
    }

    public function getOrder()
    {
        return 3; // Leftmost position
    }
}
