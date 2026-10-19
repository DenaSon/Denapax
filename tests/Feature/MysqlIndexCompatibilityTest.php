<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MysqlIndexCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_indexed_string_length_is_utf8mb4_safe(): void
    {
        $blueprint = new Blueprint(DB::connection(), 'index_probe');
        $column = $blueprint->string('indexed_value');

        $this->assertSame(191, $column->length);
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertSame('InnoDB', config('database.connections.mariadb.engine'));
    }

    public function test_migrated_indexes_use_bounded_column_lengths(): void
    {
        $this->assertSame('varchar(191)', $this->declaredColumnType('users', 'email'));
        $this->assertSame('varchar(191)', $this->declaredColumnType('password_reset_tokens', 'email'));
        $this->assertSame('varchar(191)', $this->declaredColumnType('cache', 'key'));
        $this->assertSame('varchar(191)', $this->declaredColumnType('products', 'slug'));
        $this->assertSame('varchar(100)', $this->declaredColumnType('roles', 'name'));
        $this->assertSame('varchar(32)', $this->declaredColumnType('roles', 'guard_name'));
        $this->assertSame('varchar(100)', $this->declaredColumnType('permissions', 'name'));
        $this->assertSame('varchar(32)', $this->declaredColumnType('permissions', 'guard_name'));
    }

    private function declaredColumnType(string $table, string $column): string
    {
        // Laravel 13 getColumnType() normalizes types to "varchar", losing
        // the declared SQLite width. The schema's column list retains it.
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

        $this->assertNotNull($definition);

        return strtolower($definition['type']);
    }
}
