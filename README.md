# Laravel Template - 365Soft

Sistema base de gestión desarrollado con Laravel 12, Vue 3, Inertia.js y PrimeVue. Este template incluye autenticación, manejo de roles, y una interfaz de usuario moderna con soporte para modo oscuro.

## Características Principales

### Autenticación y Seguridad
- Sistema de autenticación con Laravel Fortify
- Autenticación de dos factores (2FA)
- Gestión de roles y permisos
- Middleware de roles para protección de rutas
- Verificación de estado de usuario activo/inactivo

### Frontend
- Vue 3 con Composition API
- Inertia.js para SPA sin API
- PrimeVue 4 como librería de componentes UI
- TailwindCSS 4 para estilos
- Modo oscuro/claro dinámico
- Diseño responsive
- TypeScript para type safety

### Funcionalidades de Usuario
- Dashboard principal
- Gestión de roles
- Gestión de accesos
- Perfil de usuario
- Configuración de apariencia
- Cambio de contraseña

## Requisitos del Sistema

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- NPM o Yarn
- Base de datos MySQL, PostgreSQL o SQLite
- Extensiones PHP requeridas:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

## Instalación

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio>
cd Template_365Soft
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Configurar el Entorno

Copia el archivo de ejemplo de entorno:

```bash
cp .env.example .env
```

Edita el archivo `.env` con tu configuración:

```env
APP_NAME="Template 365Soft"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 4. Generar la Clave de la Aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar las Migraciones

```bash
php artisan migrate
```

Este comando creará las siguientes tablas:
- `roles`: Roles del sistema
- `users`: Usuarios del sistema
- Tablas de autenticación (sessions, password_reset_tokens, etc.)

### 6. Instalar Dependencias de Node

```bash
npm install
```

### 7. Compilar Assets

Para desarrollo:
```bash
npm run dev
```

Para producción:
```bash
npm run build
```

### 8. Optimizar la Configuración (Opcional)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Credenciales por Defecto

El sistema incluye un usuario administrador por defecto:

- **Email:** administrador@gmail.com
- **Contraseña:** superadmin
- **Rol:** Administrador


## Comandos Útiles

### Desarrollo

Ejecutar servidor de desarrollo con hot reload:
```bash
composer run dev
```

Este comando ejecuta simultáneamente:
- Servidor Laravel en http://localhost:8000
- Queue worker
- Vite dev server

### Base de Datos

Crear una nueva migración:
```bash
php artisan make:migration nombre_de_la_migracion
```

Ejecutar migraciones:
```bash
php artisan migrate
```

Revertir última migración:
```bash
php artisan migrate:rollback
```

### Testing

Ejecutar tests:
```bash
php artisan test
```

Ejecutar tests con Pest:
```bash
./vendor/bin/pest
```

### Optimización

Limpiar caché:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Optimizar para producción:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Sistema de Roles

El sistema incluye un sistema de roles flexible:

### Estructura

- Los usuarios tienen un rol asignado
- Los roles tienen permisos que determinan acceso a secciones
- Middleware `role:admin` protege rutas administrativas

### Crear un Nuevo Rol

1. Crear una migración para el nuevo rol
2. Actualizar el middleware `RoleMiddleware`
3. Agregar el rol a la navegación en `resources/js/config/navigation.ts`


## Personalización

### Cambiar el Tema de Colores

Los estilos principales están en `resources/css/app.css`. Puedes modificar las variables CSS para personalizar los colores.

### Agregar Nuevas Páginas

1. Crear componente Vue en `resources/js/pages/`
2. Definir ruta en el archivo correspondiente de `routes/`
3. Agregar entrada en navegación (si aplica) en `resources/js/config/navigation.ts`

### Agregar Nuevos Controladores

```bash
php artisan make:controller NombreController
```

## Tecnologías Utilizadas

### Backend
- **Laravel 12**: Framework PHP
- **Laravel Fortify**: Autenticación
- **Inertia.js**: Bridge entre Laravel y Vue

### Frontend
- **Vue 3**: Framework JavaScript
- **PrimeVue 4**: Componentes UI
- **TailwindCSS 4**: Framework CSS
- **TypeScript**: Tipado estático
- **Vite**: Build tool

### Testing
- **Pest**: Framework de testing
- **PHPUnit**: Testing PHP

## Seguridad

- Las contraseñas se almacenan con hash bcrypt
- Middleware CSRF habilitado
- Validación de entrada en todos los formularios
- Protección contra rate limiting
- Sanitización de datos de entrada

## Changelog

### Versión 1.0.0
- Sistema base con autenticación
- Gestión de roles y permisos
- Dashboard administrativo
- Gestión de usuarios
- Modo oscuro/claro
- Autenticación de dos factores