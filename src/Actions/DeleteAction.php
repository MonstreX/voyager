<?php

namespace TCG\Voyager\Actions;

class DeleteAction extends AbstractAction
{
    public function getTitle()
    {
        return __('voyager::generic.delete');
    }

    public function getIcon()
    {
        return 'voyager-trash';
    }

    public function getPolicy()
    {
        return 'delete';
    }

    public function getAttributes()
    {
        $id = $this->data->{$this->data->getKeyName()};

        return [
            'class'                   => 'btn btn-sm btn-danger delete',
            'data-id'                 => $id,
            'data-confirm-target'     => '#delete_modal',
            'data-confirm-form'       => '#delete_form',
            'data-confirm-form-action'=> route('voyager.'.$this->dataType->slug.'.destroy', ['id' => $id]),
            'id'                      => 'delete-'.$id,
        ];
    }

    public function getDefaultRoute()
    {
        return 'javascript:;';
    }

    public function getOrder()
    {
        return 0; // Rightmost position
    }
}
