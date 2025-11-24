<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Types\Type as DoctrineType;
use TCG\Voyager\Database\Types\Type;

abstract class Column
{
    public static function make(array $column, string $tableName = null)
    {
        $name = Identifier::validate($column['name'], 'Column');
        $type = $column['type'];
        if (!($type instanceof DoctrineType)) {
            $typeName = is_array($type) ? ($type['name'] ?? '') : (string) $type;
            $type = Type::resolveDoctrineColumnType($typeName);
        }
        $typeLabel = strtolower(Type::getTypeLabel($type));
        $numericAutoIncrement = ['tinyint', 'smallint', 'mediumint', 'integer', 'int', 'bigint'];
        $numericUnsigned = array_merge($numericAutoIncrement, ['decimal', 'numeric', 'float', 'double', 'double precision', 'real']);

        if (!in_array($typeLabel, $numericAutoIncrement, true)) {
            $column['autoincrement'] = false;
        }

        if (!in_array($typeLabel, $numericUnsigned, true)) {
            $column['unsigned'] = false;
        }

        $type->tableName = $tableName;

        $lengthRequired = ['varchar', 'nvarchar', 'varchar2', 'bpchar', 'string'];
        if (in_array($typeLabel, $lengthRequired, true) && empty($column['length'])) {
            $column['length'] = 191;
        }

        $options = array_diff_key($column, array_flip(['name', 'composite', 'oldName', 'null', 'extra', 'type', 'charset', 'collation']));

        return new DoctrineColumn($name, $type, $options);
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
