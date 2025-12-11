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
        return [
            'class'   => 'btn btn-sm btn-success clone',
            'data-id' => $this->data->{$this->data->getKeyName()},
            'id'      => 'clone-'.$this->data->{$this->data->getKeyName()},
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
