<?php

namespace TCG\Voyager\Services;

use Illuminate\Database\Eloquent\Model;

class PathGeneratorService
{
    const STRATEGY_FLAT = 'flat';
    const STRATEGY_DATED = 'dated';

    public static function generate(array $options = []): string
    {
        $strategy = $options['strategy'] ?? self::STRATEGY_DATED;
        $model = $options['model'] ?? null;

        if (!$model instanceof Model) {
            throw new \InvalidArgumentException('Model must be provided and must be an instance of Illuminate\Database\Eloquent\Model');
        }

        switch ($strategy) {
            case self::STRATEGY_FLAT:
                return self::generateFlat($model);
            case self::STRATEGY_DATED:
                return self::generateDated($model);
            default:
                throw new \InvalidArgumentException("Unknown strategy: {$strategy}");
        }
    }

    protected static function generateFlat(Model $model): string
    {
        $table = $model->getTable();
        $id = $model->getKey();

        return "{$table}/media/{$id}";
    }

    protected static function generateDated(Model $model): string
    {
        $table = $model->getTable();
        $year = date('Y');
        $month = date('m');

        return "{$table}/media/{$year}/{$month}";
    }
}
