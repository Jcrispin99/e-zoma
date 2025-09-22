# Migraciones para Sistema de Compañías Jerárquico (Modelo Unificado)

## Migración 1: Crear tabla companies (Modelo Unificado)

**Archivo:** `database/migrations/2024_01_01_000001_create_companies_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('parent_path')->nullable(); // Optimización jerárquica: "1/3/7/"
            $table->string('name');
            $table->enum('type', ['company', 'branch', 'department'])->default('company');
            $table->string('code', 20)->unique()->nullable(); // Código único
            $table->string('tax_id', 50)->nullable(); // RUC, Tax ID
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('manager_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('parent_path');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

## Migración 2: Modificar tabla warehouses

**Archivo:** `database/migrations/2024_01_01_000002_add_company_to_warehouses_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('location')
                  ->constrained('companies')->onDelete('set null');

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
```

## Migración 3: Modificar tabla users

**Archivo:** `database/migrations/2024_01_01_000003_add_company_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('email_verified_at')
                  ->constrained('companies')->onDelete('set null');

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
```

## Migración 4: Migración de datos existentes

**Archivo:** `database/migrations/2024_01_01_000004_migrate_existing_data_to_companies.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear empresa por defecto si no existe ninguna
        if (DB::table('companies')->count() === 0) {
            $companyId = DB::table('companies')->insertGetId([
                'parent_id' => null,
                'parent_path' => '1/',
                'name' => 'Empresa Principal',
                'type' => 'company',
                'code' => 'MAIN',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar todos los almacenes existentes a la empresa principal
            DB::table('warehouses')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        // Remover asignaciones de empresa de almacenes
        DB::table('warehouses')->update(['company_id' => null]);

        // Eliminar empresa por defecto
        DB::table('companies')->where('code', 'MAIN')->delete();
    }
};
```

````

## Modelos Laravel para el Sistema Unificado

### Modelo Company

**Archivo:** `app/Models/Company.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'parent_path',
        'name',
        'type',
        'code',
        'tax_id',
        'email',
        'phone',
        'address',
        'logo',
        'manager_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relación con empresa padre
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    // Relación con empresas hijas (sucursales)
    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    // Obtener todos los descendientes
    public function descendants()
    {
        return Company::where('parent_path', 'like', $this->parent_path . $this->id . '/%');
    }

    // Obtener todos los ancestros
    public function ancestors()
    {
        $ids = array_filter(explode('/', trim($this->parent_path, '/')));
        return Company::whereIn('id', $ids);
    }

    // Relaciones con otras entidades
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Scopes
    public function scopeCompanies($query)
    {
        return $query->where('type', 'company');
    }

    public function scopeBranches($query)
    {
        return $query->where('type', 'branch');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Métodos auxiliares
    public function isCompany(): bool
    {
        return $this->type === 'company';
    }

    public function isBranch(): bool
    {
        return $this->type === 'branch';
    }

    public function updateParentPath(): void
    {
        if ($this->parent_id) {
            $parent = $this->parent;
            $this->parent_path = $parent->parent_path . $parent->id . '/';
        } else {
            $this->parent_path = $this->id . '/';
        }
        $this->save();
    }
}
````

## 6. Migración: Hacer Campos Obligatorios

**Archivo**: `2025_01_15_105000_make_company_fields_required.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hacer company_id obligatorio en warehouses después de la migración
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });

        // Hacer company_id obligatorio en users después de la migración
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });
    }
};
```

## 7. Seeder para Datos de Prueba

**Archivo**: `database/seeders/CompanyStructureSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\User;

class CompanyStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear compañía de ejemplo con sucursales
        $company = Company::create([
            'name' => 'Distribuidora Nacional SAC',
            'document_type' => 'RUC',
            'document_number' => '20123456789',
            'address' => 'Av. Principal 123, Lima',
            'phone' => '01-234-5678',
            'email' => 'info@distribuidora.com',
        ]);

        // Crear sucursales
        $branchLima = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Lima',
            'code' => 'LIM001',
            'address' => 'Av. Principal 123, Lima',
            'phone' => '01-234-5678',
            'manager_name' => 'Juan Pérez',
        ]);

        $branchArequipa = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sucursal Arequipa',
            'code' => 'AQP001',
            'address' => 'Calle Comercio 456, Arequipa',
            'phone' => '054-123-456',
            'manager_name' => 'María García',
        ]);

        // Crear almacenes para cada sucursal
        Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branchLima->id,
            'name' => 'Almacén Central Lima',
            'location' => 'Lima - Zona Industrial',
            'is_main' => true,
        ]);

        Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branchArequipa->id,
            'name' => 'Almacén Arequipa',
            'location' => 'Arequipa - Zona Comercial',
        ]);

        // Crear usuarios de ejemplo
        User::create([
            'name' => 'Admin General',
            'email' => 'admin@distribuidora.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'role' => 'company_admin',
        ]);

        User::create([
            'name' => 'Manager Lima',
            'email' => 'manager.lima@distribuidora.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branchLima->id,
            'role' => 'branch_manager',
        ]);

        User::create([
            'name' => 'Manager Arequipa',
            'email' => 'manager.arequipa@distribuidora.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branchArequipa->id,
            'role' => 'branch_manager',
        ]);
    }
}
```

## 8. Comandos de Ejecución

### Ejecutar Migraciones en Orden

```bash
# 1. Crear las nuevas tablas
php artisan migrate --path=database/migrations/2024_01_01_000001_create_companies_table.php

# 2. Modificar tablas existentes
php artisan migrate --path=database/migrations/2024_01_01_000002_add_company_to_warehouses_table.php
php artisan migrate --path=database/migrations/2024_01_01_000003_add_company_to_users_table.php

# 3. Migrar datos existentes
php artisan migrate --path=database/migrations/2024_01_01_000004_migrate_existing_data_to_companies.php

# 4. Hacer campos obligatorios
php artisan migrate --path=database/migrations/2025_01_15_105000_make_company_fields_required.php

# 5. Ejecutar seeder (opcional)
php artisan db:seed --class=CompanyStructureSeeder
```

## Ejemplos de Uso del Modelo Unificado

### Crear Estructura Jerárquica

```php
// Crear empresa matriz
$company = Company::create([
    'name' => 'Corporación ABC',
    'type' => 'company',
    'code' => 'ABC',
    'tax_id' => '20123456789',
    'parent_path' => '1/',
]);

// Crear sucursales
$branch1 = Company::create([
    'parent_id' => $company->id,
    'name' => 'Sucursal Norte',
    'type' => 'branch',
    'code' => 'ABC-N',
    'parent_path' => '1/2/',
]);

$branch2 = Company::create([
    'parent_id' => $company->id,
    'name' => 'Sucursal Sur',
    'type' => 'branch',
    'code' => 'ABC-S',
    'parent_path' => '1/3/',
]);
```

### Consultas Jerárquicas

```php
// Obtener todas las sucursales de una empresa
$branches = $company->children()->where('type', 'branch')->get();

// Obtener todos los almacenes de una empresa y sus sucursales
$warehouses = Warehouse::whereHas('company', function($query) use ($company) {
    $query->where('parent_path', 'like', $company->parent_path . $company->id . '/%')
          ->orWhere('id', $company->id);
})->get();

// Obtener la jerarquía completa
$hierarchy = Company::orderBy('parent_path')->get();
```

### Validación y Pruebas

```bash
# Ejecutar migraciones
php artisan migrate

# Verificar estructura
php artisan tinker
>>> Schema::hasTable('companies')
>>> Schema::hasColumn('companies', 'parent_id')
>>> Schema::hasColumn('companies', 'parent_path')
>>> Schema::hasColumn('warehouses', 'company_id')
```

### Rollback (si es necesario)

```bash
# Rollback en orden inverso
php artisan migrate:rollback --path=database/migrations/2025_01_15_105000_make_company_fields_required.php
php artisan migrate:rollback --path=database/migrations/2024_01_01_000004_migrate_existing_data_to_companies.php
php artisan migrate:rollback --path=database/migrations/2024_01_01_000003_add_company_to_users_table.php
php artisan migrate:rollback --path=database/migrations/2024_01_01_000002_add_company_to_warehouses_table.php
php artisan migrate:rollback --path=database/migrations/2024_01_01_000001_create_companies_table.php
```

## 9. Validaciones y Consideraciones

### Validaciones de Integridad

1. **Constraint de Sucursal-Compañía**: Asegura que una sucursal pertenezca a la compañía correcta
2. **Almacén Principal Único**: Solo un almacén puede ser principal por compañía
3. **Usuario-Sucursal**: Un usuario solo puede pertenecer a una sucursal de su compañía

### Consideraciones de Performance

* Índices optimizados para consultas frecuentes

* Foreign keys con cascada apropiada

* Campos nullable donde corresponde para flexibilidad

### Compatibilidad

* Todas las consultas existentes siguen funcionando

* Los datos existentes se migran automáticamente

* No se rompe ninguna funcionalidad actual

Esta estructura de migraciones permite implementar el sistema de compañías y sucursales de manera gradual y segura, manteniendo la compatibilidad con el código existente.
