# Backend - Modelo ServicePersonal

## Descripción
El modelo `ServicePersonal` representa el personal de servicio en la farmacia. Contiene información básica sobre el personal de servicio y su estado de actividad.

## Estructura

### Base de Datos - Tabla: `service_personals`
```sql
- id: bigint (Primary Key)
- apellidos_nombres: string
- estado: tinyint (0-1, boolean)
- id_service: tinyint (Foreign Key -> document_types.id, nullable)
- created_at: timestamp
- updated_at: timestamp
- deleted_at: timestamp (para soft deletes)
```

## Archivos Creados/Modificados

### Modelos
- `app/Models/ServicePersonal.php` - Modelo principal con relaciones, scopes y métodos

### Controladores
- `app/Http/Controllers/Api/V1/ServicePersonalController.php` - CRUD completo con manejo de errores

### Requests (Validación)
- `app/Http/Requests/V1/StoreServicePersonalRequest.php` - Validación para crear
- `app/Http/Requests/V1/UpdateServicePersonalRequest.php` - Validación para actualizar
- `app/Http/Requests/V1/ServicePersonalRequest.php` - Validación genérica

### Recursos (Respuestas)
- `app/Http/Resources/ServicePersonalResource.php` - Transformación de datos para respuestas API

### Servicios
- `app/Services/ServicePersonalService.php` - Lógica de negocio reutilizable

### Factory
- `database/factories/ServicePersonalFactory.php` - Generador de datos para tests

### Seeder
- `database/seeders/ServicePersonalSeeder.php` - Poblar base de datos de prueba

### Testing
- `tests/Feature/ServicePersonalTest.php` - Tests de API

### Migración
- `database/migrations/2026_02_02_204015_create_service_personals_table.php` - Estructura de tabla

## Rutas API

### Todas las rutas requieren autenticación (`auth:sanctum`)

```
GET    /api/v1/servicePersonals              - Listar con paginación
POST   /api/v1/servicePersonals              - Crear nuevo
GET    /api/v1/servicePersonals/{id}         - Obtener uno
PUT    /api/v1/servicePersonals/{id}         - Actualizar
DELETE /api/v1/servicePersonals/{id}         - Eliminar (soft delete)
```

## Ejemplos de Uso

### Listar Personal de Servicio (con paginación)
```bash
GET /api/v1/servicePersonals
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": [...],
    "message": "Personal de servicio obtenido correctamente."
}
```

### Crear Personal de Servicio
```bash
POST /api/v1/servicePersonals
Authorization: Bearer {token}
Content-Type: application/json

{
    "apellidos_nombres": "Juan Pérez García",
    "estado": true,
    "id_service": 1
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "apellidos_nombres": "JUAN PÉREZ GARCÍA",
        "nombre_completo": "JUAN PÉREZ GARCÍA",
        "estado": true,
        "estado_label": "Activo",
        "id_service": 1,
        "document_type": {...},
        "is_active": true,
        "created_at": "2026-03-04 10:30:00",
        "updated_at": "2026-03-04 10:30:00"
    },
    "message": "Personal de servicio creado correctamente."
}
```

### Obtener Personal de Servicio
```bash
GET /api/v1/servicePersonals/1
Authorization: Bearer {token}
```

### Actualizar Personal de Servicio
```bash
PUT /api/v1/servicePersonals/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "apellidos_nombres": "Pedro López",
    "estado": false
}
```

### Eliminar Personal de Servicio
```bash
DELETE /api/v1/servicePersonals/1
Authorization: Bearer {token}
```

## Validaciones

### Crear (StoreServicePersonalRequest)
- `apellidos_nombres`: Requerido, string, máx 255 caracteres, único
- `estado`: Opcional, boolean
- `id_service`: Opcional, debe existir en la tabla document_types

### Actualizar (UpdateServicePersonalRequest)
- `apellidos_nombres`: Opcional, string, máx 255 caracteres, único (excluyendo registro actual)
- `estado`: Opcional, boolean
- `id_service`: Opcional, debe existir en la tabla document_types

## Scopes Disponibles en el Modelo

```php
// Obtener solo activos
ServicePersonal::active()->get();

// Obtener solo inactivos
ServicePersonal::inactive()->get();

// Buscar por término
ServicePersonal::search('Juan')->get();

// Obtener por tipo de documento
ServicePersonal::byDocumentType(1)->get();
```

## Métodos Útiles del Modelo

```php
$servicePersonal->activate();      // Activar
$servicePersonal->deactivate();    // Desactivar
$servicePersonal->isActive();      // Verificar si es activo
$servicePersonal->isInactive();    // Verificar si es inactivo
$servicePersonal->documentType;    // Relación con DocumentType
```

## Métodos del Servicio (ServicePersonalService)

```php
$service = app(ServicePersonalService::class);

$service->getAllPaginated(10);                      // Obtener paginado
$service->getAllActive();                           // Obtener activos
$service->create($data);                            // Crear
$service->update($servicePersonal, $data);          // Actualizar
$service->delete($servicePersonal);                 // Eliminar
$service->findById($id);                            // Obtener por ID
$service->search($term);                            // Buscar
$service->getByDocumentType($documentTypeId);       // Obtener por tipo
$service->activate($servicePersonal);               // Activar
$service->deactivate($servicePersonal);             // Desactivar
$service->getActiveCount();                         // Contar activos
$service->getInactiveCount();                       // Contar inactivos
$service->existsByName($name, $excludeId);          // Verificar existencia
```

## Ejecutar Tests

```bash
# Todos los tests de ServicePersonal
php artisan test tests/Feature/ServicePersonalTest.php

# Con output más detallado
php artisan test tests/Feature/ServicePersonalTest.php --verbose
```

## Ejecutar Seeders

```bash
# Poblar la base de datos
php artisan db:seed --class=ServicePersonalSeeder

# O con el seeder general
php artisan db:seed
```

## Notas Importantes

1. **Soft Deletes**: El modelo utiliza soft deletes, por lo que los registros eliminados no se eliminarán físicamente de la base de datos.

2. **Mutadores**: Los apellidos y nombres se almacenan en mayúsculas pero se muestran en formato normal.

3. **Timestamps**: Las respuestas API incluyen `created_at` y `updated_at` en formato `Y-m-d H:i:s`.

4. **Paginación**: Por defecto, el listado retorna 10 registros por página.

5. **Autenticación**: Todas las rutas requieren un token válido de Sanctum.

## Estado - Comportamiento

- **estado = 1 (true)**: Personal activo, disponible para usar
- **estado = 0 (false)**: Personal inactivo, no disponible para usar

## Relaciones

- `documentType`: Relación BelongsTo con DocumentType (un personal pertenece a un tipo de documento servicio)
