<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permision = [
            //Categorias
            'create_categories',
            'read_categories',
            'update_categories',
            'delete_categories',

            //atributos
            'create_attributes',
            'read_attributes',
            'update_attributes',
            'delete_attributes',

            //Productos
            'create_products',
            'read_products',
            'update_products',
            'delete_products',

            //Variantes
            'create_variants',
            'read_variants',
            'update_variants',
            'delete_variants',

            //Almacenes
            'create_warehouses',
            'read_warehouses',
            'update_warehouses',
            'delete_warehouses',

            //Proveedores
            'create_suppliers',
            'read_suppliers',
            'update_suppliers',
            'delete_suppliers',

            //Ordenes de compra
            'create_purchase-orders',
            'read_purchase-orders',
            'update_purchase-orders',
            'delete_purchase-orders',

            //Compras
            'create_purchases',
            'read_purchases',
            'update_purchases',
            'delete_purchases',

            //Clientes
            'create_customers',
            'read_customers',
            'update_customers',
            'delete_customers',

            //Cotizaciones
            'create_quotes',
            'read_quotes',
            'update_quotes',
            'delete_quotes',

            //Ventas
            'create_sales',
            'read_sales',
            'update_sales',
            'delete_sales',

            //Movimientos
            'create_movements',
            'read_movements',
            'update_movements',
            'delete_movements',

            //transacciones
            'create_transactions',
            'read_transactions',
            'update_transactions',
            'delete_transactions',

            //users
            'create_users',
            'read_users',
            'update_users',
            'delete_users',

            //roles
            'create_roles',
            'read_roles',
            'update_roles',
            'delete_roles',

            //permisos
            'create_permissions',
            'read_permissions',
            'update_permissions',
            'delete_permissions',

            //empresas
            'create_companies',
            'read_companies',
            'update_companies',
            'delete_companies',

            //secuencias
            'create_sequences',
            'read_sequences',
            'update_sequences',
            'delete_sequences',

            // Lealtad
            'create_loyalty-programs',
            'read_loyalty-programs',
            'update_loyalty-programs',
            'delete_loyalty-programs',

            // reportes
            'create_reports',
            'read_reports',
            'update_reports',
            'delete_reports',

            'access_dashboard',
            'import_products',
            'read_variants_kardex',
            'upload_variant_images',
            'upload_product_images',
            'delete_images',
            'export_pdf_purchase-orders',
            'export_pdf_purchases',
            'export_pdf_quotes',
            'export_pdf_sales',
            'export_pdf_movements',
            'export_pdf_transfers',
            'read_qr_labels',
            'create_posconfig',
            'read_posconfig',
            'update_posconfig',
            'delete_posconfig',
            'read_pos_sessions',
            'create_journals',
            'read_journals',
            'update_journals',
            'delete_journals',
            'create_transfers',
            'read_transfers',
            'update_transfers',
            'delete_transfers',
            'read_ubigeo',
            'read_sunat-connections',
            'create_sunat-connections',
            'update_sunat-connections',
        ];

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permision as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        Role::create(['name' => 'admin'])
            ->givePermissionTo(Permission::all());

        Role::create(['name' => 'almacen'])
            ->givePermissionTo([
                'create_categories',
                'read_categories',
                'update_categories',
                'delete_categories',
                'create_attributes',
                'read_attributes',
                'update_attributes',
                'delete_attributes',
                'create_products',
                'read_products',
                'update_products',
                'delete_products',
                'create_variants',
                'read_variants',
                'update_variants',
                'delete_variants',
                'create_warehouses',
                'read_warehouses',
                'update_warehouses',
                'delete_warehouses',
                'create_suppliers',
                'read_suppliers',
                'update_suppliers',
                'delete_suppliers',
            ]);

        Role::create(['name' => 'lector'])
            ->givePermissionTo([
                'read_categories',
                'read_attributes',
                'read_products',
                'read_variants',
                'read_warehouses',
                'read_suppliers',
                'read_purchase-orders',
                'read_purchases',
                'read_customers',
                'read_quotes',
                'read_sales',
                'read_movements',
                'read_transactions',
                'read_users',
                'read_roles',
                'read_permissions',
            ]);

        Role::create(['name' => 'editor'])
            ->givePermissionTo([
                'create_categories',
                'read_categories',
                'update_categories',
                'delete_categories',
                'create_attributes',
                'read_attributes',
                'update_attributes',
                'delete_attributes',
                'create_products',
                'read_products',
                'update_products',
                'delete_products',
                'create_variants',
                'read_variants',
                'update_variants',
                'delete_variants',
                'create_warehouses',
                'read_warehouses',
                'update_warehouses',
                'delete_warehouses',
                'create_suppliers',
                'read_suppliers',
                'update_suppliers',
                'delete_suppliers',
            ]);

        Role::create(['name' => 'viewer'])
            ->givePermissionTo([
                'read_categories',
                'read_attributes',
                'read_products',
                'read_variants',
                'read_warehouses',
                'read_suppliers',
                'read_purchase-orders',
                'read_purchases',
                'read_customers',
                'read_quotes',
                'read_sales',
                'read_movements',
                'read_transactions',
                'read_users',
                'read_roles',
                'read_permissions',
            ]);

        $user = User::factory()->create([
            'name' => 'Jhamil Crispin',
            'email' => 'j99crispin@gmail.com',
            'password' => bcrypt('123123123'),
        ]);

        $user->assignRole('admin');

        $user->companies()->attach(1);
    }
}
