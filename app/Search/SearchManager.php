<?php

declare(strict_types=1);

namespace App\Search;

use Illuminate\Support\Facades\DB;

class SearchManager
{
    protected ?SearchDriver $driver = null;

    public function driver(): SearchDriver
    {
        if ($this->driver === null) {
            $this->driver = $this->resolveDriver();
        }

        return $this->driver;
    }

    protected function resolveDriver(): SearchDriver
    {
        $connection = DB::getDefaultConnection();
        $driver = config("database.connections.{$connection}.driver");

        return match ($driver) {
            'pgsql' => new PostgresSearchDriver,
            default => new SqliteSearchDriver,
        };
    }
}
