# Configuración del Sistema de Autenticación y Roles

## Módulo 1: Autenticación, Roles y Multi-tenancy

Se ha implementado el sistema base de autenticación con roles y multi-tenancy para el Portal de Proveedores.

### ✅ Archivos Creados

#### Backend (Laravel)
1. **Enum de Roles**: `app/Enums/UserRole.php`
   - Define los 3 roles: Admin, Contratista, Supervisor
   - Incluye métodos de verificación de permisos

2. **Modelo Contratista**: `app/Models/Contratista.php`
   - Gestiona empresas contratistas
   - Incluye validación de RUT chileno

3. **Migraciones**:
   - `database/migrations/2026_01_06_000001_create_contratistas_table.php`
   - `database/migrations/2026_01_06_000002_add_role_and_contratista_to_users_table.php`

4. **Factory y Seeder**:
   - `database/factories/ContratistaFactory.php` - Genera RUTs chilenos válidos
   - `database/seeders/ContratistaSeeder.php` - Crea datos de prueba

5. **Middleware**:
   - `app/Http/Middleware/EnsureUserBelongsToContratista.php` - Multi-tenancy
   - Actualizado `app/Http/Middleware/HandleInertiaRequests.php` - Compartir datos de auth

6. **Modelos Actualizados**:
   - `app/Models/User.php` - Agregados roles, relaciones y scopes

#### Frontend (React + TypeScript)
1. **Tipos TypeScript**: `resources/js/types/index.d.ts`
   - Interfaz `User` con todos los campos de roles
   - Interfaz `Contratista`
   - Interfaz `Auth` actualizada

### 📋 Pasos para Ejecutar

#### 1. Ejecutar Migraciones y Seeders

```bash
# Opción 1: Fresh migration (borra todo y recrea)
php artisan migrate:fresh --seed

# Opción 2: Solo migraciones nuevas
php artisan migrate
php artisan db:seed --class=ContratistaSeeder
```

#### 2. Usuarios de Prueba Creados

Después de ejecutar el seeder, tendrás:

**Admin del Sistema:**
- Email: `admin@portal.com`
- Password: `password`
- Rol: Admin
- Permisos: Acceso total al sistema

**5 Contratistas** (cada uno con):
- 1 Usuario administrador del contratista
- 1-2 Supervisores
- Email formato: `admin@[nombrecontratista].com`
- Password: `password`

#### 3. Verificar Instalación

```bash
# Ver usuarios creados
php artisan tinker
>>> User::with('contratista')->get(['id', 'name', 'email', 'role', 'contratista_id'])

# Ver contratistas
>>> Contratista::count()
```

### 🎯 Funcionalidades Implementadas

#### Roles y Permisos

**Admin**:
- ✅ Puede gestionar contratistas
- ✅ Puede ver datos de todos los contratistas
- ✅ No está asociado a ningún contratista específico

**Contratista**:
- ✅ Administra su empresa contratista
- ✅ Puede gestionar trabajadores de su contratista
- ✅ Solo ve datos de su contratista

**Supervisor**:
- ✅ Puede gestionar trabajadores de su contratista
- ✅ Solo ve datos de su contratista
- ✅ Permisos más limitados que Contratista

#### Multi-tenancy

El sistema asegura que:
- Cada usuario (excepto Admin) está asociado a un contratista
- Los datos se filtran automáticamente por contratista
- Middleware `EnsureUserBelongsToContratista` aplica las restricciones

#### Datos Compartidos con Frontend

Todos los datos de autenticación están disponibles en Inertia:

```typescript
import { usePage } from '@inertiajs/react';

function Component() {
  const { auth } = usePage().props;

  // auth.user.isAdmin
  // auth.user.canManageWorkers
  // auth.contratista.razon_social
}
```

### 🔍 Estructura de Datos

#### Tabla `contratistas`
```sql
- id
- rut (único, formato: 12345678-9)
- razon_social
- nombre_fantasia
- direccion, comuna, region
- telefono, email
- estado (activo|inactivo|bloqueado)
- observaciones
- timestamps + soft deletes
```

#### Tabla `users` (campos agregados)
```sql
- role (admin|contratista|supervisor)
- contratista_id (FK a contratistas, nullable)
- is_active (boolean)
```

### 🚀 Próximos Pasos

Con el sistema de autenticación base implementado, puedes proceder con:

1. **Módulo 2**: Onboarding y auto-registro de contratistas
2. **Módulo 3**: Gestión de personal (trabajadores)
3. **Módulo 4**: Sistema de documentación y cumplimiento

### 🐛 Troubleshooting

**Error: "Class 'UserRole' not found"**
```bash
composer dump-autoload
```

**Error en migraciones de foreign keys**
```bash
# Asegúrate de que las migraciones se ejecuten en orden
php artisan migrate:fresh
```

**No aparecen los datos de contratista en frontend**
```bash
# Verifica que el middleware esté registrado
# Revisa bootstrap/app.php
```
