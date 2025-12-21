<?php

namespace TCG\Voyager\Services\Media;

use Illuminate\Database\Eloquent\Model;

class MediaPathGenerator
{
    const STRATEGY_DATED = 'dated';

    public static function generate(array $options = []): string
    {
        $model = $options['model'] ?? null;

        if (!$model instanceof Model) {
            throw new \InvalidArgumentException('Model must be provided and must be an instance of Illuminate\Database\Eloquent\Model');
        }

        return self::generateDated($model);
    }

    protected static function generateDated(Model $model): string
    {
        $table = $model->getTable();
        $year = date('Y');
        $month = date('m');

        return "{$table}/media/{$year}/{$month}";
    }
}
