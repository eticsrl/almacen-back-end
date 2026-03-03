# Sistema de Roles y Permisos - Backend Laravel

Este documento describe cómo configurar el sistema de roles y permisos en el backend.

## Instalación y Configuración

### 1. Ejecutar las migraciones

```bash
php artisan migrate
```

Esto creará las siguientes tablas:
- `roles` - Tabla de roles
- `permissions` - Tabla de permisos
- `role_permission` - Tabla pivot para relación roles-permisos
- `role_user` - Tabla pivot para relación usuarios-roles

### 2. Ejecutar el seeder (Opcional)

Para cargar roles y permisos predefinidos:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Esto creará:
- **4 Roles**: admin, pharmacist, warehouse, doctor
- **25 Permisos**: relacionados con medicamentos, ingresos, egresos, recetas, reportes y administración

## Endpoints API

### Roles

```
GET    /api/roles                        - Obtener todos los roles
GET    /api/roles/{id}                   - Obtener un rol específico
POST   /api/roles                        - Crear nuevo rol
PUT    /api/roles/{id}                   - Actualizar rol
DELETE /api/roles/{id}                   - Eliminar rol
GET    /api/roles/{id}/permissions       - Obtener permisos de un rol
POST   /api/roles/{id}/permissions       - Asignar permisos a un rol
```

### Permisos

```
GET    /api/permissions                  - Obtener todos los permisos
GET    /api/permissions/{id}             - Obtener un permiso específico
POST   /api/permissions                  - Crear nuevo permiso
PUT    /api/permissions/{id}             - Actualizar permiso
DELETE /api/permissions/{id}             - Eliminar permiso
```

## Ejemplos de Uso

### Crear un nuevo rol

```bash
curl -X POST http://localhost:8000/api/roles \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "nurse",
    "description": "Enfermera"
  }'
```

### Asignar permisos a un rol

```bash
curl -X POST http://localhost:8000/api/roles/1/permissions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "permission_ids": [1, 2, 3, 4]
  }'
```

### Obtener permisos de un rol

```bash
curl -X GET http://localhost:8000/api/roles/1/permissions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Asignar roles a usuarios

### En el modelo User

```php
// Asignar un rol a un usuario
$user->roles()->attach($roleId);

// Asignar múltiples roles
$user->roles()->attach([1, 2, 3]);

// Remover un rol
$user->roles()->detach($roleId);

// Reemplazar roles
$user->roles()->sync([1, 2]);

// Verificar si el usuario tiene un rol
$user->hasRole('admin');
```

### A través de API (crear endpoint personalizado)

Puedes crear un endpoint como este:

```php
// RoleController.php
public function assignRoleToUser(Request $request, $userId)
{
    $validator = Validator::make($request->all(), [
        'role_ids' => 'required|array',
        'role_ids.*' => 'integer|exists:roles,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->messages()], 422);
    }

    $user = User::findOrFail($userId);
    $user->roles()->sync($request->role_ids);

    return response()->json([
        'message' => 'Roles asignados exitosamente',
        'user' => $user->load('roles')
    ]);
}
```

Luego agrega la ruta:

```php
Route::post('users/{id}/roles', [RoleController::class, 'assignRoleToUser']);
```

## Verificación de permisos en Controllers

```php
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function store(Request $request)
    {
        // Verificar si el usuario tiene permiso
        if (!$request->user()->hasPermission('create_medicines')) {
            return response()->json([
                'message' => 'No tienes permiso para crear medicamentos'
            ], 403);
        }

        // Lógica para crear medicamento
    }
}
```

## Middleware de permisos (Opcional)

Puedes crear un middleware para proteger rutas:

```bash
php artisan make:middleware CheckPermission
```

```php
// app/Http/Middleware/CheckPermission.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!$request->user() || !$request->user()->hasPermission($permission)) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este recurso'
            ], 403);
        }

        return $next($request);
    }
}
```

Registra el middleware en `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... otros middlewares
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

Úsalo en rutas:

```php
Route::post('medicines', [MedicineController::class, 'store'])
    ->middleware('permission:create_medicines');
```

## Respuesta de login con roles y permisos

Cuando un usuario inicia sesión, la respuesta incluye sus roles y permisos:

```json
{
  "status": true,
  "message": "Usuario logeado satisfactoriamente",
  "token": "YOUR_TOKEN",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "avatar": null,
    "entity": {
      "id": 1,
      "descripcion": "Farmacia Centro"
    },
    "roles": [
      {
        "id": 1,
        "name": "admin",
        "description": "Administrador del sistema"
      }
    ],
    "permissions": [
      {
        "id": 1,
        "name": "view_medicines",
        "description": "Ver medicamentos"
      },
      {
        "id": 2,
        "name": "create_medicines",
        "description": "Crear medicamentos"
      }
      // ... más permisos
    ],
    "created_at": "2026-03-02T10:00:00Z"
  }
}
```

## Notas importantes

1. **Validación en Backend**: Siempre valida permisos en el backend, nunca confíes solo en validaciones del frontend.

2. **Jerarquía de Roles**: El seeder proporciona 4 roles con permisos predefinidos. Puedes personalizar esto según tus necesidades.

3. **Performance**: Para aplicaciones grandes, considera cachear los roles y permisos del usuario.

4. **Auditoría**: Considera agregar logs cuando se cambien roles o permisos para fines de auditoría.

## Ejemplo completo de flujo de autenticación y autorización

1. Usuario inicia sesión en el frontend
2. Recibe token y datos de usuario con roles/permisos
3. Frontend almacena rol/permisos en localStorage
4. Frontend puede mostrar/ocultar UI según permisos
5. Al realizar acciones, el token se envía en headers
6. Backend valida el token y verifica permisos
7. Solo se ejecutan operaciones si el usuario tiene permisos

Este enfoque proporciona seguridad multicapa: validación frontend para UX y validación backend para seguridad real.
