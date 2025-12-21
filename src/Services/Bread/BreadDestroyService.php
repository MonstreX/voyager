<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use TCG\Voyager\Events\BreadDataDeleted;

class BreadDestroyService
{
    public function __construct(protected BreadCleanupService $cleanupService)
    {
    }

    public function destroy($dataType, array $ids): int
    {
        $affected = 0;

        $modelClass = $dataType->model_name;
        $usesSoftDeletes = $modelClass && in_array(SoftDeletes::class, class_uses_recursive($modelClass));

        foreach ($ids as $id) {
            $data = call_user_func([$modelClass, 'findOrFail'], $id);

            Gate::authorize('delete', $data);

            if (!$usesSoftDeletes) {
                $this->cleanupService->cleanup($dataType, $data);
            }

            $res = $data->delete();

            if ($res) {
                $affected++;
                event(new BreadDataDeleted($dataType, $data));
            }
        }

        return $affected;
    }
}
