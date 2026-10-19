<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MysqlIndexCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_indexed_string_length_is_utf8mb4_safe(): void
    {
        $blueprint = new Blueprint('index_probe');
        $column = $blueprint->string('indexed_value');

        $this->assertSame(191, $column->length);
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertSame('InnoDB', config('database.connections.mariadb.engine'));
    }

    public function test_migrated_indexes_use_bounded_column_lengths(): void
    {
        $this->assertSame('varchar(191)', Schema::getColumnType('users', 'email', true));
        $this->assertSame('varchar(191)', Schema::getColumnType('password_reset_tokens', 'email', true));
        $this->assertSame('varchar(191)', Schema::getColumnType('cache', 'key', true));
        $this->assertSame('varchar(191)', Schema::getColumnType('products', 'slug', true));
        $this->assertSame('varchar(100)', Schema::getColumnType('roles', 'name', true));
        $this->assertSame('varchar(32)', Schema::getColumnType('roles', 'guard_name', true));
        $this->assertSame('varchar(100)', Schema::getColumnType('permissions', 'name', true));
        $this->assertSame('varchar(32)', Schema::getColumnType('permissions', 'guard_name', true));
    }
}
