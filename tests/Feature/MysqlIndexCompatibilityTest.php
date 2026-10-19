<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MysqlIndexCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_indexed_string_length_is_utf8mb4_safe(): void
    {
        $blueprint = new Blueprint(DB::connection(), 'index_probe');
        $column = $blueprint->string('indexed_value');

        $this->assertSame(191, $column->length);
        $this->assertLessThanOrEqual(1000, $column->length * 4);
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertSame('InnoDB', config('database.connections.mariadb.engine'));
    }

    public function test_indexed_permission_names_and_catalog_slug_remain_bounded(): void
    {
        // SQLite normalizes all varchar declarations to "varchar" in
        // actual tables, so check the explicit migration declarations
        // that MySQL compiles into indexed column widths.
        $permissionMigration = file_get_contents(database_path('migrations/2026_10_18_124125_create_permission_tables.php'));
        $catalogMigration = file_get_contents(database_path('migrations/2026_10_18_150100_create_catalog_tables.php'));
        $editor = file_get_contents(app_path('Livewire/Admin/Catalog/ProductEditor.php'));

        $this->assertSame(2, substr_count($permissionMigration, "\$table->string('name', 100);"));
        $this->assertSame(2, substr_count($permissionMigration, "\$table->string('guard_name', 32);"));
        $this->assertLessThanOrEqual(1000, (100 + 32) * 4 + 8);
        $this->assertStringContainsString("\$table->string('slug', 191)->unique();", $catalogMigration);
        $this->assertStringContainsString("'max:191'", $editor);
    }
}
