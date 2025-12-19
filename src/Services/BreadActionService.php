<?php

namespace TCG\Voyager\Services;

use Illuminate\Http\Request;
use InvalidArgumentException;

class BreadActionService
{
    public function run(Request $request, $dataType)
    {
        $actionClass = $request->action;
        if (!$actionClass || !is_string($actionClass) || !class_exists($actionClass)) {
            throw new InvalidArgumentException("Action {$actionClass} doesn't exist or has not been defined");
        }

        $action = new $actionClass($dataType, null);

        return $action->massAction(explode(',', $request->ids), $request->headers->get('referer'));
    }
}

