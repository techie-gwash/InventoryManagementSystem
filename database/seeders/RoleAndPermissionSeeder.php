<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permissions = [
            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Categories
            'manage categories',

            // Locations
            'manage locations',

            // Purchases
            'view purchases',
            'create purchases',
            'receive purchases',

            // Sales
            'view sales',
            'create sales',

            // Inventory
            'view inventory',

            // Users
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);
        $inventoryManager = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Inventory Manager']);
        $sales = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Sales']);
        $viewer = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Viewer']);

        $admin->givePermissionTo(Permission::all());
        $inventoryManager->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'manage categories',
            'manage locations',
            'view purchases',
            'create purchases',
            'receive purchases',
            'view inventory',
        ]);
        $sales->givePermissionTo([
            'view products',
            'view inventory',
            'view sales',
            'create sales',
        ]);
        $viewer->givePermissionTo([
            'view products',
            'view inventory',
        ]);
    }
}
