<?php

namespace TCG\Voyager\Database;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\Connection as LaravelConnection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TCG\Voyager\Database\Types\Type;

class DoctrineManager
{
    /**
     * Cached Doctrine connections keyed by Laravel connection name.
     *
     * @var array<string,\Doctrine\DBAL\Connection>
     */
    protected static array $connections = [];

    /**
     * Cached Doctrine schema managers keyed by Laravel connection name.
     *
     * @var array<string,\Doctrine\DBAL\Schema\AbstractSchemaManager>
     */
    protected static array $schemaManagers = [];

    /**
     * Get a Doctrine connection for the given Laravel connection.
     */
    public static function connection(?string $connection = null): DoctrineConnection
    {
        $connection = $connection ?: config('database.default');

        if (!isset(static::$connections[$connection])) {
            /** @var \Illuminate\Database\Connection $laravelConnection */
            $laravelConnection = DB::connection($connection);
            static::$connections[$connection] = static::createDoctrineConnection($laravelConnection);
        }

        return static::$connections[$connection];
    }

    /**
     * Get the Doctrine schema manager for the given Laravel connection.
     */
    public static function schemaManager(?string $connection = null): AbstractSchemaManager
    {
        $connection = $connection ?: config('database.default');

        if (!isset(static::$schemaManagers[$connection])) {
            static::$schemaManagers[$connection] = static::connection($connection)->createSchemaManager();
        }

        return static::$schemaManagers[$connection];
    }

    /**
     * Forget cached Doctrine instances.
     */
    public static function forget(?string $connection = null): void
    {
        if ($connection === null) {
            static::$connections = [];
            static::$schemaManagers = [];
        } else {
            unset(static::$connections[$connection], static::$schemaManagers[$connection]);
        }

        Type::flushCache();
    }

    /**
     * Create a Doctrine connection configured from Laravel's database settings.
     */
    protected static function createDoctrineConnection(LaravelConnection $connection): DoctrineConnection
    {
        if (!class_exists(DriverManager::class)) {
            throw new RuntimeException('Voyager database tools require doctrine/dbal to be installed.');
        }
        Type::flushCache();

        $driver = (string) $connection->getConfig('driver');
        $parameters = [
            'dbname' => $connection->getDatabaseName(),
            'driver' => static::mapDriver($driver),
            'host' => $connection->getConfig('host'),
            'port' => $connection->getConfig('port'),
            'unix_socket' => $connection->getConfig('unix_socket') ?? $connection->getConfig('socket'),
            'charset' => $connection->getConfig('charset'),
            'user' => $connection->getConfig('username'),
            'password' => $connection->getConfig('password'),
            'serverVersion' => $connection->getConfig('server_version'),
            'driverOptions' => $connection->getConfig('options') ?? [],
        ];

        if ($driver === 'sqlite') {
            $database = $connection->getDatabaseName();

            if ($database === ':memory:') {
                $parameters['memory'] = true;
            } else {
                $parameters['path'] = $database;
            }
        }

        $parameters = array_filter($parameters, static function ($value) {
            return $value !== null;
        });

        return DriverManager::getConnection($parameters);
    }

    /**
     * Map Laravel connection drivers to Doctrine driver names.
     */
    protected static function mapDriver(?string $driver): string
    {
        $driver = $driver ?: 'mysql';

        $map = [
            'mysql' => 'pdo_mysql',
            'mariadb' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'postgresql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
        ];

        return $map[$driver] ?? ('pdo_'.$driver);
    }
}
