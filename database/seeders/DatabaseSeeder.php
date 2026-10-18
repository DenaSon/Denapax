<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Never seed default/demo staff credentials.
        $this->call([RolesAndPermissionsSeeder::class, InitialSettingsSeeder::class]);
    }
}
