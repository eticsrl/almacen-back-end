<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Medicines
            ['name' => 'view_medicines', 'description' => 'Ver medicamentos'],
            ['name' => 'create_medicines', 'description' => 'Crear medicamentos'],
            ['name' => 'edit_medicines', 'description' => 'Editar medicamentos'],
            ['name' => 'delete_medicines', 'description' => 'Eliminar medicamentos'],

            // Entries
            ['name' => 'view_entries', 'description' => 'Ver ingresos'],
            ['name' => 'create_entries', 'description' => 'Crear ingresos'],
            ['name' => 'edit_entries', 'description' => 'Editar ingresos'],
            ['name' => 'delete_entries', 'description' => 'Eliminar ingresos'],

            // Discharges
            ['name' => 'view_discharges', 'description' => 'Ver egresos'],
            ['name' => 'create_discharges', 'description' => 'Crear egresos'],
            ['name' => 'edit_discharges', 'description' => 'Editar egresos'],
            ['name' => 'delete_discharges', 'description' => 'Eliminar egresos'],

            // Receipts
            ['name' => 'view_receipts', 'description' => 'Ver recetas'],
            ['name' => 'create_receipts', 'description' => 'Crear recetas'],
            ['name' => 'edit_receipts', 'description' => 'Editar recetas'],
            ['name' => 'delete_receipts', 'description' => 'Eliminar recetas'],

            // Reports
            ['name' => 'view_reports', 'description' => 'Ver reportes'],
            ['name' => 'export_reports', 'description' => 'Exportar reportes'],

            // Administration
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios'],
            ['name' => 'manage_roles', 'description' => 'Gestionar roles'],
            ['name' => 'manage_permissions', 'description' => 'Gestionar permisos'],
            ['name' => 'manage_entities', 'description' => 'Gestionar entidades'],
            ['name' => 'manage_suppliers', 'description' => 'Gestionar proveedores'],
            ['name' => 'manage_categories', 'description' => 'Gestionar categorías'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador del sistema']
        );

        $pharmacistRole = Role::firstOrCreate(
            ['name' => 'pharmacist'],
            ['description' => 'Farmacéutico']
        );

        $warehouseRole = Role::firstOrCreate(
            ['name' => 'warehouse'],
            ['description' => 'Encargado de almacén']
        );

        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['description' => 'Médico']
        );

        // Assign all permissions to admin
        $adminRole->permissions()->syncWithoutDetaching(Permission::pluck('id')->toArray());

        // Assign permissions to pharmacist
        $pharmacistPermissions = Permission::whereIn('name', [
            'view_medicines',
            'view_entries',
            'view_discharges',
            'create_discharges',
            'edit_discharges',
            'view_receipts',
            'create_receipts',
            'edit_receipts',
            'view_reports',
        ])->pluck('id')->toArray();
        $pharmacistRole->permissions()->syncWithoutDetaching($pharmacistPermissions);

        // Assign permissions to warehouse
        $warehousePermissions = Permission::whereIn('name', [
            'view_medicines',
            'view_entries',
            'create_entries',
            'edit_entries',
            'view_discharges',
            'view_reports',
        ])->pluck('id')->toArray();
        $warehouseRole->permissions()->syncWithoutDetaching($warehousePermissions);

        // Assign permissions to doctor
        $doctorPermissions = Permission::whereIn('name', [
            'view_medicines',
            'view_receipts',
            'create_receipts',
            'view_reports',
        ])->pluck('id')->toArray();
        $doctorRole->permissions()->syncWithoutDetaching($doctorPermissions);
    }
}
