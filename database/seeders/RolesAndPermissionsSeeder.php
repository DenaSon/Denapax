<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = [
            'catalog.manage',
            'orders.view',
            'orders.update',
            'payments.view',
            'payments.review',
            'shipping.manage',
            'settings.manage',
            'staff.manage',
        ];

        foreach ($allPermissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $operator = Role::findOrCreate('operator', 'web');

        $superAdmin->syncPermissions($allPermissions);
        $operator->syncPermissions([
            'catalog.manage',
            'orders.view',
            'orders.update',
            'payments.view',
            'shipping.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
