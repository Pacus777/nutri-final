# NUTRICIONAL-DILAN-SISTEMA-FINAL

Sistema web para gestión de nutricionistas, pacientes y asignaciones, desarrollado con Laravel + Vue + Inertia.

## Descripción

Este proyecto está orientado a la gestión de una clínica o centro de nutrición. El sistema está construido por módulos y actualmente contempla, en distintos niveles de avance:

- Seguridad, acceso y roles
- Gestión de usuarios
- Gestión de nutricionistas
- Gestión de pacientes
- Asignación nutricionista–paciente
- Base para evaluación inicial y contexto del paciente

La arquitectura sigue una lógica de negocio real:

- `usuarios` es la tabla base común
- `nutricionistas` y `pacientes` son perfiles especializados
- los datos personales comunes viven en `usuarios`
- los datos clínicos o profesionales viven en sus tablas específicas

---

## Stack tecnológico

### Backend
- PHP 8.3.6
- Laravel 13.5.0
- Composer
- MySQL

### Frontend
- Vue 3
- Inertia.js
- Vite 8
- Tailwind CSS
- Laravel Breeze

### Entorno usado durante el desarrollo
- Laragon
- MySQL en puerto `3360` en algunas configuraciones locales
- Navegador con servidor de desarrollo Vite

---

## Versiones usadas o detectadas durante el desarrollo

Estas son las versiones que se han usado o detectado en el proyecto durante el desarrollo:

- PHP: `8.3.6`
- Laravel: `13.5.0`
- Vue: `^3.4.0`
- Inertia Vue: `^2.0.0`
- Vite: `^8.0.0`
- Laravel Vite Plugin: `^3.0.0`
- Tailwind CSS: `^3.2.1`
- Node.js: se recomienda `22.22.2`
- npm: se detectó uso de `8.18` en Laragon, pero para evitar errores se recomienda usar la terminal donde Node 22 esté realmente activo

> Recomendación práctica: usa siempre la terminal donde `node -v` te devuelva la versión moderna correcta. Durante el desarrollo hubo problemas cuando Laragon seguía usando Node 18.

---

## Requisitos previos

Antes de clonar o ejecutar el proyecto, asegúrate de tener instalado:

- PHP 8.3 o superior
- Composer
- Node.js 22 recomendado
- npm
- MySQL
- Git

Verifica con:

```bash
php -v
composer -V
node -v
npm -v
git --version
```

---

## Clonar el proyecto

```bash
git clone <URL_DEL_REPOSITORIO>
cd NUTRICIONAL-DILAN-SISTEMA-FINAL
```

Si el proyecto ya existe localmente y solo quieres actualizarlo:

```bash
git pull origin main
```

> Cambia `main` por `master` si tu rama principal tiene ese nombre.

---

## Instalación desde cero

### 1. Instalar dependencias PHP

```bash
composer install
```

### 2. Instalar dependencias JS

```bash
npm install
```

### 3. Copiar archivo de entorno

```bash
cp .env.example .env
```

En Windows PowerShell puedes hacerlo manualmente si `cp` no funciona.

### 4. Generar clave de aplicación

```bash
php artisan key:generate
```

### 5. Configurar base de datos en `.env`

Ejemplo:

```env
APP_NAME="NUTRICIONAL-DILAN-SISTEMA-FINAL"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3360
DB_DATABASE=nutricional-dilan-sistema-final
DB_USERNAME=root
DB_PASSWORD=
```

> Ajusta `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` según tu entorno.
> Si en tu Laragon usas el puerto por defecto de MySQL, posiblemente sea `3306` y no `3360`.

### 6. Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

Esto recrea toda la base de datos y carga los datos iniciales.

### 7. Crear enlace de almacenamiento para fotos

```bash
php artisan storage:link
```

Esto es necesario para que las fotos cargadas en `storage/app/public` puedan verse desde el navegador.

### 8. Compilar assets

Para desarrollo:

```bash
npm run dev
```

Para compilación de producción:

```bash
npm run build
```

### 9. Levantar el servidor Laravel

```bash
php artisan serve
```

Por defecto quedará en:

```txt
http://127.0.0.1:8000
```

---

## Flujo normal de ejecución en desarrollo

Usualmente tendrás dos terminales abiertas:

### Terminal 1
```bash
php artisan serve
```

### Terminal 2
```bash
npm run dev
```

Si solo quieres verificar compilación final:

```bash
npm run build
```

---

## Usuario inicial del sistema

El proyecto usa seeders para crear el usuario base del sistema. Revisa tu `SuperAdminSeeder` para ver:

- correo
- contraseña
- CI
- fecha de nacimiento
- demás datos iniciales

Si olvidaste la contraseña o quieres cambiar el usuario semilla, modifica el seeder y ejecuta:

```bash
php artisan migrate:fresh --seed
```

---

## Estructura lógica del sistema

### Base común
- `usuarios`
- `roles`
- `usuario_rol`

### Perfiles especializados
- `nutricionistas`
- `pacientes`

### Relación operativa
- `asignacion_nutricionista_paciente`

### Próximos módulos
- `contextos_paciente`
- `evaluaciones_iniciales`

---

## Funcionalidades implementadas

### Módulo 1. Seguridad, acceso y roles
- autenticación usando tabla `usuarios`
- roles múltiples por usuario
- rol activo en sesión
- middleware de control de estado y rol

### Módulo 2. Gestión de nutricionistas
- registro rápido desde modal
- edición del perfil nutricionista
- edición de datos base del usuario desde el módulo
- control de estado lógico

### Módulo 3. Gestión de pacientes
- registro rápido desde modal
- edición del perfil del paciente
- edición de datos base del usuario desde el módulo
- control de estado lógico

### Módulo 4. Asignación nutricionista–paciente
- registro de asignaciones
- una sola asignación activa por paciente
- historial de asignaciones
- cierre o reasignación

---

## Subida del proyecto a GitHub

### 1. Inicializar Git si aún no existe

```bash
git init
```

### 2. Agregar archivos

```bash
git add .
```

### 3. Crear commit inicial

```bash
git commit -m "Primer commit del sistema nutricional"
```

### 4. Crear repositorio en GitHub

Crea el repositorio desde GitHub y copia la URL.

### 5. Conectar el repositorio remoto

```bash
git remote add origin <URL_DEL_REPOSITORIO>
```

### 6. Subir código

Si tu rama principal es `main`:

```bash
git branch -M main
git push -u origin main
```

Si usas `master`:

```bash
git branch -M master
git push -u origin master
```

---

## Archivos que NO debes subir

Asegúrate de que `.gitignore` cubra al menos esto:

```gitignore
/vendor
/node_modules
/public/build
/public/hot
/storage/*.key
/.env
/.idea
/.vscode
```

No subas:

- `.env`
- `vendor`
- `node_modules`
- archivos temporales de Laragon o IDE

---

## Comandos útiles

### Limpiar caché general

```bash
php artisan optimize:clear
```

### Limpiar caché de configuración

```bash
php artisan config:clear
```

### Limpiar caché de rutas

```bash
php artisan route:clear
```

### Ver rutas

```bash
php artisan route:list
```

### Volver a compilar frontend

```bash
npm run build
```

### Ejecutar Vite en desarrollo

```bash
npm run dev
```

---

## Errores comunes y cómo solucionarlos

### 1. Error: `There are no commands defined in the "breeze" namespace`

#### Causa
Laravel Breeze no estaba instalado todavía.

#### Solución

```bash
composer require laravel/breeze --dev
php artisan breeze:install vue
npm install
npm run build
```

---

### 2. Error: `Could not resolve './bootstrap' in resources/js/app.js`

#### Causa
Faltaba el archivo `resources/js/bootstrap.js` o el proyecto quedó incompleto tras instalar Breeze.

#### Solución
Verifica que exista:

```txt
resources/js/bootstrap.js
```

Si no existe, recréalo correctamente o reinstala Breeze con cuidado.

---

### 3. Error: `Identifier 'Link' has already been declared`

#### Causa
Se importó `Link` dos veces en el mismo archivo Vue.

#### Solución
Revisa imports duplicados como:

```js
import { Link } from '@inertiajs/vue3';
import { Link, router, usePage } from '@inertiajs/vue3';
```

Deja solo una importación.

---

### 4. Error: `MethodNotAllowedHttpException - POST method is not supported for route usuarios/2. Supported methods: PUT`

#### Causa
Se envió un `POST` real a una ruta que esperaba `PUT`.

#### Solución
Cuando uses formularios con archivos en Vue + Inertia, usa este patrón:

```js
form
    .transform((data) => ({
        ...data,
        _method: 'put',
    }))
    .post(route('usuarios.update', id), {
        forceFormData: true,
    });
```

---

### 5. Error al migrar: campo nuevo sin valor por defecto

Ejemplo:

```txt
Field 'ape_usu' doesn't have a default value
```

#### Causa
Se normalizó la tabla `usuarios`, pero seeders o formularios seguían enviando solo campos viejos.

#### Solución
Actualizar:

- seeders
- `fillable` del modelo
- requests
- controllers
- formularios

para que envíen todos los campos nuevos obligatorios.

---

### 6. Error con Node en Laragon

#### Causa
Laragon seguía usando una versión vieja de Node aunque otra terminal mostraba Node 22.

#### Solución
Usa la terminal donde esto devuelva la versión correcta:

```bash
node -v
npm -v
```

Si Laragon usa Node viejo, ejecuta `npm install`, `npm run dev` y `npm run build` desde otra terminal del sistema donde sí tengas Node actualizado.

---

### 7. Error con imágenes no visibles

#### Causa
No existe el enlace simbólico de `storage`.

#### Solución

```bash
php artisan storage:link
```

Y asegúrate de guardar las fotos en el disco `public`.

---

### 8. Error de redirección o bucles al elegir rol activo

#### Causa
Lógica de middleware o redirección forzada al selector de rol mal configurada.

#### Solución
Revisar:

- middleware `verificar.rol.activo`
- middleware `check.estado`
- rutas de selección de rol
- sesiones activas

Luego limpiar cachés:

```bash
php artisan optimize:clear
```

---

### 9. Error en Vue con `??` y `||`

#### Causa
Se mezcló el operador nullish coalescing `??` con `||` sin paréntesis.

#### Solución
Usar funciones helper en `<script setup>` o separar la lógica.

Ejemplo recomendado:

```js
const nombreCompleto = (usuario) => {
    if (usuario?.nombre_completo) return usuario.nombre_completo;

    const nombre = `${usuario?.nom_usu ?? ''} ${usuario?.ape_usu ?? ''}`.trim();
    return nombre || 'Sin usuario';
};
```

---

## Recomendaciones al clonar en otra máquina

Después de clonar, ejecuta siempre este orden:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm run build
php artisan serve
```

Y para desarrollo:

```bash
npm run dev
```

---

## Recomendación final de trabajo

Cada vez que hagas cambios grandes:

```bash
git add .
git commit -m "Descripción clara del avance"
git push
```

Ejemplos:

```bash
git commit -m "Normalización de usuarios y pacientes"
git commit -m "Modales rápidos de nutricionistas y pacientes"
git commit -m "Edición completa desde módulos especializados"
```

---

## Estado actual del proyecto

El proyecto está en una fase avanzada de base funcional. La estructura central ya permite continuar con módulos clínicos como:

- contexto del paciente
- evaluación inicial
- seguimiento nutricional
- historial clínico nutricional
- objetivos y planes

---

## Autor

Proyecto desarrollado como sistema académico-profesional de gestión nutricional, con enfoque formativo y arquitectura modular escalable.
