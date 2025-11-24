<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Illuminate\Support\Facades\Log;
use TCG\Voyager\Database\Types\Type;

abstract class Column
{
    public static function make(array $column, string $tableName = null)
    {
        $name = Identifier::validate($column['name'], 'Column');
        $type = $column['type'];
        Log::debug('voyager.column.make.input', [
            'name'          => $name,
            'type'          => $type instanceof DoctrineType ? get_class($type) : ($type['name'] ?? $type),
            'autoincrement' => $column['autoincrement'] ?? null,
            'unsigned'      => $column['unsigned'] ?? null,
        ]);
        if (!($type instanceof DoctrineType)) {
            $typeName = is_array($type) ? ($type['name'] ?? '') : (string) $type;
            $type = Type::resolveDoctrineColumnType($typeName);
        }
        $typeLabel = strtolower(Type::getTypeLabel($type));
        $numericAutoIncrement = ['tinyint', 'smallint', 'mediumint', 'integer', 'int', 'bigint'];
        $numericUnsigned = array_merge($numericAutoIncrement, ['decimal', 'numeric', 'float', 'double', 'double precision', 'real']);

        if (!in_array($typeLabel, $numericAutoIncrement, true)) {
            if (!empty($column['autoincrement'])) {
                Log::debug('voyager.column.make.autoincrement.reset', [
                    'name' => $name,
                    'type' => $typeLabel,
                ]);
            }
            $column['autoincrement'] = false;
        }

        if (!in_array($typeLabel, $numericUnsigned, true)) {
            if (!empty($column['unsigned'])) {
                Log::debug('voyager.column.make.unsigned.reset', [
                    'name' => $name,
                    'type' => $typeLabel,
                ]);
            }
            $column['unsigned'] = false;
        }

        $type->tableName = $tableName;

        $lengthRequired = ['varchar', 'nvarchar', 'varchar2', 'bpchar', 'string'];
        if (in_array($typeLabel, $lengthRequired, true) && empty($column['length'])) {
            $column['length'] = 191;
            Log::debug('voyager.column.make.length.default', [
                'name' => $name,
                'type' => $typeLabel,
                'length' => $column['length'],
            ]);
        }

        $options = array_diff_key($column, array_flip(['name', 'composite', 'oldName', 'null', 'extra', 'type', 'charset', 'collation']));

        $doctrineColumn = new DoctrineColumn($name, $type, $options);
        Log::debug('voyager.column.make.output', [
            'name'          => $name,
            'type'          => $typeLabel,
            'autoincrement' => $doctrineColumn->getAutoincrement(),
            'unsigned'      => $doctrineColumn->getUnsigned(),
        ]);

        return $doctrineColumn;
    }

    /**
     * @return array
     */
    public static function toArray(DoctrineColumn $column)
    {
        $columnArr = $column->toArray();
        $columnArr['type'] = Type::toArray($columnArr['type']);
        $columnArr['oldName'] = $columnArr['name'];
        $columnArr['null'] = $columnArr['notnull'] ? 'NO' : 'YES';
        $columnArr['extra'] = static::getExtra($column);
        $columnArr['composite'] = false;

        return $columnArr;
    }

    /**
     * @return string
     */
    protected static function getExtra(DoctrineColumn $column)
    {
        $extra = '';

        $extra .= $column->getAutoincrement() ? 'auto_increment' : '';
        // todo: Add Extra stuff like mysql 'onUpdate' etc...

        return $extra;
    }
}
