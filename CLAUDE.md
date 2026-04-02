# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Actúa como un Desarrollador Fullstack Senior experto en el ecosistema Laravel Moderno. Tu objetivo es asistir en el desarrollo, refactorización y debugging siguiendo los estándares de Lightswind.

### STACK TÉCNICO OBLIGATORIO:
- Backend: Laravel 12+ (PHP 8.2+), Eloquent ORM.
- Frontend: React con TypeScript, Inertia.js (Protocolo v2 recomendado).
- Estilizado: Tailwind CSS + Shadcn UI (Radix UI).
- Bases de Datos: MySQL 8.0+ (producción) / SQLite (desarrollo).
- Arquitectura: Patrón de diseño basado en la biblioteca Lightswind (https://lightswind.com/).

### REGLAS DE CODIFICACIÓN:
1. DRY & SOLID: Prioriza la legibilidad y mantenibilidad.
2. TYPESCRIPT: Interfaces estrictas para Props de Inertia y respuestas de API.
3. COMPONENTES: Usa componentes funcionales de React, hooks personalizados y la estética de Shadcn UI.
4. LARAVEL: Usa Type Hinting, Form Requests para validación y Resources para la transformación de datos.
5. LIGHTSWIND: Adhiérete a los patrones de UI/UX definidos en la documentación de lightswind.com, especialmente en la gestión de layouts y estados de carga.

### FLUJO DE TRABAJO:
- Antes de proponer cambios en el frontend, verifica la ruta en `web.php` y el método del Controlador correspondiente.
- Asegúrate de que las migraciones sean compatibles con MySQL

---

## RESUMEN TÉCNICO DEL PROYECTO - MVP

### Alcance del MVP: Portal de Proveedores y Contratistas
Sistema para gestión de contratistas en sector agroindustrial chileno, enfocado en cumplimiento legal y eficiencia operacional.

### Configuración de Base de Datos
- **Desarrollo**: SQLite (por defecto en Laravel 12)
- **Producción**: MySQL 8.0+
- **Importante**: Todas las migraciones deben ser compatibles con MySQL (evitar características específicas de SQLite)

### Integraciones Externas - MVP
**No se incluyen en esta fase:**
- ❌ Validación automática con SII (API Gateway)
- ❌ SDK de escaneo de cédulas (Anyline/BlinkID)
- ❌ Firma electrónica (simple o avanzada)

**Implementación futura (Fase 2):**
- Validación RUT contra SII
- Firma digital de contratos
- Notificaciones push móviles

### Plataforma y Arquitectura Offline
- **Web Principal**: Aplicación responsive Laravel + Inertia + React (funciona en desktop y móvil)
- **Modo Offline**: PWA (Progressive Web App) para formularios críticos:
  - Enrolamiento de personal (líneas 206-213)
  - Registro de asistencia (línea 213, 240-248)
- **Sincronización**: Service Workers + IndexedDB para cola de sincronización automática

### Convenciones de Datos Críticas

#### Formato RUT (Líneas 209-212):
```php
// ID del trabajador (clave primaria en DB)
'id' => '12345678'  // RUT sin puntos, sin dígito verificador

// Documento/Cédula (para validación y display)
'documento' => '12345678-5'  // RUT sin puntos, CON dígito verificador
```

**Validación requerida**:
- El ID debe ser numérico (7-8 dígitos)
- El documento debe validar dígito verificador usando algoritmo Módulo 11
- Ambos campos deben corresponder al mismo RUT

#### Estructura de Enrolamiento (Sección 4.1):
```typescript
interface EnrolamientoWorker {
  id: string;           // RUT sin puntos ni DV (ej: "12345678")
  nombre: string;       // Nombre del trabajador
  apellido: string;     // Apellidos del trabajador
  documento: string;    // RUT sin puntos con DV (ej: "12345678-5")
}
```

### Módulos del MVP (Orden de Implementación Sugerido)

1. **Autenticación y Roles** (Base - Laravel Fortify extendido)
   - Roles: Admin, Contratista, Supervisor
   - Multi-tenancy: Cada contratista ve solo sus datos

2. **Módulo 3: Onboarding de Contratistas** (Líneas 191-203)
   - Auto-registro de empresa contratista
   - Perfil y datos básicos
   - Dashboard con estado de cumplimiento

3. **Módulo 4: Gestión de Personal** (Líneas 204-219)
   - CRUD de trabajadores (manual o Excel)
   - Validación de RUT con algoritmo Módulo 11
   - Asignación a faenas/cuadrillas
   - Gestión de estados (activo/inactivo)

4. **Módulo 5: Cumplimiento y Documentación** (Líneas 220-236)
   - Tipos de documentos administrables (CRUD)
   - Upload de archivos (F30, LRE CSV, Previred PDF)
   - Validación de estructura CSV del LRE
   - Sistema de alertas y vencimientos
   - Bloqueo de pagos por documentación pendiente

5. **Módulo 6: Control de Asistencia** (Líneas 238-254)
   - Registro entrada/salida (modo offline)
   - Inmutabilidad de registros (compliance DT)
   - Reportes por contratista/faena/período
   - Exportación para fiscalización DT

6. **Módulo 7: Portal del Proveedor** (Líneas 256-270)
   - Métricas operativas (personal activo, horas trabajadas)
   - Sistema de mensajería interna
   - Tracking de estados de pago

### Consideraciones Técnicas Especialese

#### Validación de Archivos
```php
// LRE - Libro de Remuneraciones Electrónico
'lre_file' => 'required|file|mimes:csv,txt|max:10240',
// Validación estructura: delimiter=';', headers específicos DT

// Previred PDF
'previred_file' => 'required|file|mimes:pdf|max:5120',

// Certificado F30
'f30_file' => 'required|file|mimes:pdf|max:2048',
```

#### Inmutabilidad de Asistencia (Línea 245)
```php
// Los registros de asistencia NO pueden editarse ni eliminarse
// Usar soft deletes + auditoría completa
// Timestamp con precisión de segundos: Y-m-d H:i:s
```

#### Conformidad Legal DT (Dirección del Trabajo Chile)
- Resolución Exenta N° 38 (registro electrónico de asistencia)
- Ley N° 20.123 (responsabilidad subsidiaria)
- Ley N° 21.561 (jornada de 40 horas)
- Formato LRE según especificaciones Mi DT

---

## Stack

This is a Laravel 12 application with an Inertia.js + React frontend using:
- **Backend**: PHP 8.2, Laravel 12, Inertia.js v2, Laravel Fortify (auth), Laravel Wayfinder (type-safe routing)
- **Frontend**: React 19, TypeScript, Tailwind CSS v4, Radix UI components, Vite
- **Testing**: PHPUnit 11
- **Database**: SQLite (development) / MySQL 8.0+ (production)

## Development Commands

### Setup & Installation
```bash
composer run setup              # Full project setup (install, env, migrate, build)
composer install                # Install PHP dependencies
npm install                     # Install Node dependencies
```

### Development
```bash
composer run dev                # Start dev server, queue worker, and Vite (recommended)
php artisan serve               # Start Laravel server only
npm run dev                     # Start Vite dev server only
composer run dev:ssr            # Start with SSR support (includes Pail for logs)
```

### Building
```bash
npm run build                   # Build frontend assets
npm run build:ssr               # Build frontend with SSR support
```

### Testing
```bash
composer run test               # Run all PHPUnit tests
php artisan test                # Run all tests (alternative)
php artisan test tests/Feature/ExampleTest.php    # Run specific test file
php artisan test --filter=testName                # Run specific test by name
```

### Code Quality
```bash
vendor/bin/pint --dirty         # Format PHP code (run before committing)
npm run lint                    # Lint and fix JavaScript/TypeScript
npm run format                  # Format frontend code with Prettier
npm run format:check            # Check frontend code formatting
npm run types                   # TypeScript type checking
```

## Architecture

### Backend Structure

This is a Laravel 12 application using the streamlined file structure (introduced in Laravel 11):
- **No `app/Http/Kernel.php`** - middleware is registered in `bootstrap/app.php`
- **No `app/Console/Kernel.php`** - console configuration in `bootstrap/app.php` or `routes/console.php`
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available

**Key Directories:**
- `app/Http/Controllers/` - Controllers organized by feature (e.g., `Settings/ProfileController.php`)
- `app/Http/Middleware/` - Custom middleware (`HandleInertiaRequests.php`, `HandleAppearance.php`)
- `app/Http/Requests/` - Form Request validation classes
- `app/Models/` - Eloquent models
- `app/Actions/Fortify/` - Laravel Fortify authentication actions
- `routes/web.php` - Main web routes
- `routes/settings.php` - Settings-related routes (included from `web.php`)
- `bootstrap/app.php` - Application bootstrap, middleware, and routing configuration

**Authentication:**
Laravel Fortify handles authentication with custom actions in `app/Actions/Fortify/`. The app includes user registration, login, password reset, email verification, and two-factor authentication.

### Frontend Structure

Inertia.js connects the Laravel backend to the React frontend without building an API.

**Key Directories:**
- `resources/js/pages/` - Inertia page components (map to routes)
  - `auth/` - Authentication pages
  - `settings/` - Settings pages
  - `dashboard.tsx`, `welcome.tsx` - Main pages
- `resources/js/layouts/` - Layout components
  - `app/` - Authenticated app layouts
  - `auth/` - Authentication layouts
  - `settings/` - Settings layouts
- `resources/js/components/` - Reusable React components
  - `ui/` - Shadcn-style UI primitives (button, dialog, etc.)
  - Application-specific components (header, sidebar, etc.)
- `resources/js/actions/` - Generated Wayfinder route functions (auto-generated, do not edit manually)
- `resources/js/routes/` - Generated Wayfinder named routes (auto-generated, do not edit manually)
- `resources/js/types/` - TypeScript type definitions
- `resources/js/lib/` - Utility functions
- `resources/js/hooks/` - Custom React hooks

**Type-Safe Routing (Wayfinder):**
Wayfinder generates TypeScript functions for Laravel routes. Import controller methods or named routes:
```typescript
import { update } from '@/actions/App/Http/Controllers/Settings/ProfileController'
import { dashboard } from '@/routes/dashboard'

// Use with Inertia Form component
<Form {...update.form()}>
```

**UI Components:**
The app uses Radix UI primitives with Tailwind CSS styling, organized in `components/ui/`. Check existing components before creating new ones.

**State Management:**
- Shared data is passed via Inertia's `HandleInertiaRequests` middleware
- Forms use Inertia's `<Form>` component or `useForm` hook
- Appearance (dark/light mode) is managed via `HandleAppearance` middleware and cookies

### Middleware Configuration

Middleware is configured in `bootstrap/app.php`:
- Cookie encryption excludes `appearance` and `sidebar_state`
- Web middleware stack includes `HandleAppearance`, `HandleInertiaRequests`, and `AddLinkHeadersForPreloadedAssets`

### Database & Models

- Uses SQLite by default (`database/database.sqlite`)
- Models use Laravel 12's `casts()` method instead of `$casts` property (check existing models)
- Factories and seeders in `database/factories/` and `database/seeders/`

## Important Conventions

### PHP
- Use PHP 8 constructor property promotion
- Always use explicit return type declarations
- Always use curly braces for control structures
- Prefer PHPDoc blocks over inline comments
- Enum keys should be TitleCase

### Laravel
- Use `php artisan make:*` commands to create files (with `--no-interaction`)
- Prefer Eloquent relationships over raw queries
- Use Form Request classes for validation (never inline in controllers)
- Use `config('app.name')` instead of `env('APP_NAME')` outside config files
- Use named routes with `route()` helper for URL generation

### Inertia
- Page components go in `resources/js/pages/`
- Use `Inertia::render()` from controllers
- Use `<Form>` component or `useForm` hook for forms
- Leverage deferred props, prefetching, and infinite scrolling (Inertia v2 features)

### Frontend
- Use Wayfinder for type-safe routing (import from `@/actions/` or `@/routes/`)
- Check existing components in `components/ui/` before creating new ones
- Use Tailwind CSS v4 syntax (no `@tailwind` directives, use `@import "tailwindcss"`)
- Dark mode support required for all new components using `dark:` variants
- Use `gap` utilities for spacing, not margins

### Testing
- Every change must have tests (write new or update existing)
- Run minimum necessary tests using filters
- Use factories for test data
- Most tests should be feature tests (use `--phpunit` flag with `make:test`)

### Code Quality
- Run `vendor/bin/pint --dirty` before committing PHP changes
- Run `npm run lint` for TypeScript/React code
- Follow existing file conventions (check sibling files)

### Especificación  funcional Portal de Proveedores y Contratistas


Documento de Especificación Funcional: Portal de Proveedores y Contratistas
1.0 Introducción y Objetivos Estratégicos
1.1 Visión General del Proyecto
La creación de un portal de proveedores a medida, desarrollado en Laravel con React Inertia, no es solo una mejora operativa, sino una decisión estratégica para mitigar riesgos y obtener una ventaja competitiva en el complejo marco normativo chileno. En un sector agroindustrial donde la gestión de personal externo es intensiva y estacional, la dependencia de procesos manuales o soluciones genéricas representa una exposición inaceptable a contingencias legales y una barrera para la eficiencia.
El propósito central de este proyecto es consolidar la gestión de contratistas en una plataforma unificada, diseñada para automatizar el cumplimiento legal, optimizar la administración de personal externo y fortalecer la relación con los proveedores través de herramientas de valor añadido. El portal actuará como el único punto de contacto digital, garantizando que cada contratista que presta servicios cumpla con sus obligaciones laborales y previsionales, protegiendo así a la empresa mandante.
A continuación, se detalla la arquitectura técnica sobre la que se construirán estas funcionalidades, diseñada para operar con la misma eficacia en la oficina y en terreno.
1.2 Objetivos de Negocio y Justificación
La implementación del portal responde a objetivos estratégicos concretos, derivados de los desafíos operativos y regulatorios del sector.
•	Mitigación de Riesgos Legales El objetivo irrenunciable del portal es blindar a la empresa mandante, transformando su responsabilidad legal de solidaria a subsidiaria, conforme a la Ley N° 20.123. Esto se logrará mediante la ejecución sistemática y auditable de los derechos de información y retención, condicionando los pagos al cumplimiento íntegro de las obligaciones laborales y previsionales del contratista.
•	Eficiencia Operacional La automatización del enrolamiento de personal, la gestión documental centralizada y el control de asistencia digital reducirán drásticamente la carga administrativa para los departamentos de Operaciones y Recursos Humanos. Se eliminarán los errores de digitación, la dispersión de información y los procesos manuales que actualmente consumen un tiempo valioso, especialmente durante la temporada alta.
•	Garantía de Cumplimiento Normativo El sistema asegurará el cumplimiento con normativas críticas y de reciente implementación, como la Ley N° 21.561 (jornada de 40 horas) y los estrictos requerimientos de reporte del Libro de Remuneraciones Electrónico (LRE) ante la Dirección del Trabajo.
•	Fomento de la Adopción por Parte del Proveedor Más que una herramienta de fiscalización, el portal ofrecerá funcionalidades de valor para el contratista, como la visibilidad del estado de sus pagos y canales de comunicación directa. Esto incentivará su uso proactivo, mejorando la calidad y puntualidad de la información recibida y fortaleciendo la relación comercial.
La consecución de estos objetivos estratégicos depende de una base tecnológica sólida, definida por los siguientes requerimientos técnicos y no funcionales.
2.0 Arquitectura y Requerimientos No Funcionales
Una base técnica robusta es fundamental para soportar operaciones críticas y garantizar la continuidad del negocio, especialmente en entornos con conectividad limitada como el sector agrícola. La elección de una arquitectura moderna y resiliente asegura que el portal sea un activo a largo plazo y no una solución temporal.
Requerimiento	Descripción Detallada	Justificación Estratégica
Pila Tecnológica	El sistema será desarrollado utilizando Laravel para el backend, React con Inertia.js para el frontend, y MySQL como base de datos.	El ecosistema robusto de Laravel es idóneo para manejar la lógica de negocio compleja (ej. reglas de remuneraciones), mientras que React con Inertia.js permite entregar una experiencia de usuario rápida y responsiva, similar a una aplicación de escritorio, factor crucial para asegurar una alta adopción entre contratistas con distintos niveles de habilidad técnica.
Arquitectura Offline-First	La aplicación móvil para el registro de asistencia y enrolamiento de personal deberá funcionar sin conexión a internet, sincronizando los datos automáticamente al detectar una red estable.	Esto es crítico para operar en predios agrícolas o buses de acercamiento, garantizando la continuidad de procesos de alto valor como el enrolamiento de personal y el registro de tarjas, que frecuentemente ocurren en zonas con conectividad deficiente o nula. Se asegura así la captura de datos en el punto de origen, sin interrupciones.
Seguridad de Datos	Todos los datos personales y documentos deben ser almacenados de forma segura, con encriptación en tránsito y en reposo, cumpliendo con las normativas de protección de datos vigentes en Chile.	La protección de información sensible de los trabajadores y contratistas es una obligación legal y una prioridad para evitar brechas de seguridad que puedan derivar en sanciones y daño reputacional.
Escalabilidad	La arquitectura debe permitir un crecimiento lineal sin degradación del rendimiento, soportando un alto volumen de usuarios, documentos y transacciones durante la temporada alta.	El software a medida se constituye como un activo estratégico que garantiza la soberanía tecnológica. Esto permite a la empresa escalar sus operaciones sin estar sujeta a las limitaciones de licenciamiento por usuario, a los cambios de precios o a la hoja de ruta de un proveedor SaaS, asegurando que la tecnología evolucione al ritmo del negocio y no al de un tercero.
Estos requerimientos técnicos son la base sobre la cual se edificará el primer módulo funcional del sistema: el proceso de onboarding y validación de contratistas.
3.0 Módulo Central: Onboarding y Gestión de Contratistas
Este módulo es el punto de entrada al ecosistema digital de la empresa. Un proceso de onboarding eficiente, automatizado y validado es la primera línea de defensa para garantizar que solo los contratistas que cumplen con todos los requisitos legales y tributarios puedan iniciar operaciones.
3.1 Auto-Registro y Perfil del Contratista
El flujo de incorporación se inicia con un proceso de auto-gestión que minimiza la intervención manual del personal administrativo.
1.	El contratista accede a un formulario público y seguro desde el sitio web de la empresa para crear su cuenta.
2.	Ingresa los datos básicos de su empresa: Razón Social, RUT, dirección, y datos de contacto del administrador.
3.	Crea las credenciales de administrador (usuario y contraseña) para gestionar su cuenta en el portal.
3.2 Dashboard del Contratista
Al iniciar sesión, el contratista será recibido por un panel de control principal que le proporcionará una visión clara y accionable de su estado con la empresa.
•	Resumen de Estado: Un indicador visual prominente (ej: "Al día", "Documentación Pendiente", "Bloqueado para pago") que comunica de forma inmediata su nivel de cumplimiento.
•	Alertas y Notificaciones: Una sección dedicada a avisos importantes y tareas pendientes, como por ejemplo: "Su LRE de Octubre está pendiente de carga" o "El certificado F30 vencerá en 15 días".
•	Accesos Directos: Botones para las acciones más comunes, permitiendo una navegación rápida hacia funcionalidades clave como "Cargar Documentación", "Registrar Personal" y "Ver Asistencia".
Desde este panel central, el contratista podrá navegar hacia el módulo de gestión de sus trabajadores, el siguiente pilar funcional del sistema.
4.0 Módulo de Gestión de Personal de Contratistas
Este módulo aborda uno de los mayores desafíos operativos del sector: el enrolamiento masivo de trabajadores de temporada de forma rápida, precisa y sin errores, sentando las bases para una correcta gestión laboral y de remuneraciones.
4.1 Enrolamiento Masivo 
Para eliminar los cuellos de botella y los errores de digitación, el enrolamiento se realizará a través de subidas de planillas Excel o Ingreso manual 
1.	Datos: Los datos que el contratista deberá llenar en la planilla son :
o	ID : acá debe ir el RUT sin puntos ni digito verificador
o	Nombre: Nombre del contratado
o	Apellido: Apellidos del Contratado
o	Documento / Cédula: Rut del contratado sin puntos con digito verificador 
2.	Ingreso manual Operación Offline: Esta funcionalidad operará en modo offline, permitiendo el registro de personal directamente en buses de acercamiento o en predios agrícolas sin acceso a internet. Los datos se almacenarán de forma segura en el dispositivo y se sincronizarán automáticamente con el portal al recuperar la conexión.
4.2 Gestión de Nómina
Una vez enrolados, la gestión del ciclo de vida del trabajador se realiza de manera centralizada y digital.
•	Gestión de Nómina: Los trabajadores registrados aparecerán en una lista dentro del portal del contratista, donde se podrá gestionar su estado (activo/inactivo) y consultar su información básica.
4.3 Asignación a Faenas o Cuadrillas
Para facilitar la organización y el control operativo, el contratista podrá agrupar a su personal. Esta funcionalidad permite organizar a los trabajadores en cuadrillas o asignarlos a faenas específicas definidas por la empresa principal. Dicha asignación será la base para un control más granular de la asistencia, la productividad y los costos asociados.
La correcta gestión del personal es el primer paso; el siguiente es asegurar el cumplimiento de sus derechos laborales a través de la gestión documental.
5.0 Módulo de Cumplimiento Legal y Documentación
Este módulo constituye el núcleo del sistema de mitigación de riesgos. La gestión correcta, completa y puntual de la documentación laboral es el requisito indispensable para que la empresa mandante pueda invocar la responsabilidad subsidiaria y protegerse de contingencias financieras.
5.1 Carga y Gestión de Documentación Obligatoria
El portal definirá un conjunto de documentos  administrables que el contratista deberá subir mensualmente para acreditar su cumplimiento. La liberación de los estados de pago estará estrictamente condicionada a la carga y validación de esta documentación para el período correspondiente.
Documento Obligatorio	Propósito y Criterio de Aceptación
Certificado F30 o F30-1 emitido por la Dirección del Trabajo (DT)	Propósito: Acreditar el pago íntegro y oportuno de las cotizaciones de salud, pensiones y seguro de cesantía de todos sus trabajadores.
Criterio: El portal debe validar la autenticidad del documento emitido por la Dirección del Trabajo.
Libro de Remuneraciones Electrónico (LRE)	Propósito: Detallar las remuneraciones pagadas a cada trabajador, en conformidad con los requerimientos de la DT.
Criterio: El sistema debe aceptar únicamente archivos en formato CSV con delimitador punto y coma. Realizará una doble validación previa: una validación de forma (estructura, columnas) y una validación de fondo (cruces lógicos de datos) para minimizar el riesgo de rechazo en el portal Mi DT.
Planillas de Pago de Cotizaciones (Previred)	Propósito: Servir como respaldo documental adicional del pago efectivo de las cotizaciones declaradas en el certificado F30-1.
Criterio: Aceptar archivos en formato PDF que correspondan a la planilla de pago generada por la plataforma Previred.
5.2 Alertas y Ciclo de Vida de Documentos
Para facilitar una gestión proactiva, el portal implementará un sistema de notificaciones automáticas que mantendrá informado al contratista sobre el estado de su documentación. Se enviarán alertas por correo electrónico en los siguientes casos:
•	Documentación del período actual pendiente de carga.
•	Documentos rechazados por errores de formato o contenido.
•	Próximo vencimiento de documentos con validez temporal (ej. certificados).
Este control documental se complementa con el control dinámico de las operaciones diarias a través del módulo de asistencia.

6.0 Módulo de Operaciones y Control de Asistencia
Este módulo proporciona las herramientas para la validación del trabajo diario y el estricto cumplimiento de la jornada laboral, diseñado en conformidad con la normativa de la Dirección del Trabajo, específicamente la Resolución Exenta N° 38.
6.1 Registro Electrónico de Asistencia Diaria
La marcación de asistencia se realizará de forma digital, reemplazando cualquier método manual propenso a errores o adulteraciones.
•	Método de Registro: Los supervisores de cada contratista registrarán la entrada y salida de los trabajadores de sus cuadrillas a través de la aplicación móvil del portal.
•	Requisitos de Validez Legal: El sistema cumplirá con todas las exigencias técnicas para ser considerado un registro electrónico válido por la DT:
o	Identificación del trabajador: El registro estará vinculado de forma inequívoca al trabajador enrolado.
o	Inalterabilidad de la información: Una vez realizadas, las marcas de entrada y salida no podrán ser modificadas ni eliminadas.
o	Registro de fecha y hora precisos: Las marcas incluirán la fecha completa (dd/mm/aa) y la hora con segundos (hh:mm:ss).
o	Comprobante de marcación: El sistema generará un comprobante digital de cada marcación para el trabajador.
o	Disponibilidad para fiscalización: Los datos de asistencia estarán disponibles para ser consultados en tiempo real por un fiscalizador de la DT que lo requiera.
6.2 Reportes de Asistencia para Operaciones y RRHH
La información capturada en terreno se traducirá en valor para la gestión interna de la empresa principal. El personal de Operaciones y RRHH tendrá acceso a un panel centralizado para visualizar y analizar la asistencia de todos los contratistas.
El sistema generará los siguientes reportes clave:
•	Reporte de asistencia diaria por contratista, faena o cuadrilla.
•	Reporte consolidado de horas trabajadas por trabajador para un período determinado.
•	Reporte de ausentismo, distinguiendo entre ausencias justificadas e injustificadas.
Para que el portal sea verdaderamente integral, no solo debe cumplir con las exigencias de la empresa mandante, sino también ofrecer un valor tangible al proveedor.
7.0 Propuesta de Valor para el Proveedor
El éxito a largo plazo del portal depende directamente de su adopción por parte de los contratistas. Para lograrlo, las siguientes funcionalidades están diseñadas para convertir la plataforma de una mera obligación a una herramienta útil de gestión para su propio negocio.
7.1 Dashboard de Autogestión
Además de las alertas de cumplimiento, el panel de control del contratista ofrecerá métricas operativas que le permitirán tener un pulso de su propia gestión:
•	Total de personal activo y asignado a faenas.
•	Total de horas hombre trabajadas en el período actual.
•	Estado detallado y fecha de las últimas cargas de documentación.
7.2 Canales de Comunicación Simplificados
Se implementará un sistema de mensajería simple e integrado dentro del portal. Esta herramienta permitirá al contratista comunicarse directamente con el departamento de Operaciones para resolver dudas sobre faenas, personal o documentación, eliminando la dependencia de llamadas telefónicas o cadenas de correos electrónicos que se pierden con facilidad.
7.3 Visibilidad del Estado de Pagos
Para reducir la incertidumbre administrativa y mejorar la relación comercial, el portal incluirá una sección de "Estados de Pago". En ella, el contratista podrá ver en tiempo real el ciclo de vida de sus facturas o estados de pago, incluyendo:
•	Fecha de recepción del documento.
•	Estado actual (Ej: "Recibido", "En revisión", "Aprobado para pago", "Pagado").
•	Observaciones o motivos claros de retención (ej. "Pago retenido por LRE de Octubre pendiente").
Esta transparencia no solo reduce la carga de consultas administrativas, sino que también fomenta una relación de confianza y colaboración. En definitiva, este portal transforma una compleja obligación regulatoria en una ventaja competitiva sostenible. Al digitalizar la confianza y la transparencia en la cadena de suministro, la empresa no solo blinda su operación contra riesgos legales, sino que se posiciona como el socio preferente para los mejores contratistas del sector, asegurando la calidad y continuidad que exigen los mercados globales.

