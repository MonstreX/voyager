<?php

namespace TCG\Voyager\Services\Bread;

class BreadBelongsToManySyncService
{
    public function sync($data, array $multiSelect): void
    {
        foreach ($multiSelect as $syncData) {
            $data->belongsToMany(
                $syncData['model'],
                $syncData['table'],
                $syncData['foreignPivotKey'],
                $syncData['relatedPivotKey'],
                $syncData['parentKey'],
                $syncData['relatedKey']
            )->sync($syncData['content']);
        }
    }
}
