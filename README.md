# Guía de instalación

## Requisitos

- PHP 8.3 o superior compatible con `composer.lock`, con las extensiones requeridas por Composer y el controlador `pdo_mysql` habilitados.
- Composer.
- MySQL en ejecución y una base de datos vacía.
- Node.js 22.12 o superior y pnpm.
- Python 3.12 con pip y venv para ejecutar el componente analítico.
- Acceso a internet para descargar las dependencias.

Ejecutar los comandos siguientes desde la carpeta raíz del proyecto. Los comandos de copia y activación corresponden a PowerShell en Windows.

## 1. Instalar las dependencias

```powershell
composer install
composer check-platform-reqs
pnpm install --frozen-lockfile
```

Si Composer indica que falta una extensión de PHP, habilitarla en el `php.ini` utilizado por la terminal antes de continuar.

## 2. Configurar el entorno y la base de datos

Si todavía no existe `.env`, crearlo:

```powershell
Copy-Item .env.example .env
```

Crear una base de datos vacía en MySQL y ajustar estos valores en `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nutricional_local
DB_USERNAME=usuario_local
DB_PASSWORD=clave_local

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Reemplazar el nombre de la base, el usuario y la contraseña por los del entorno local. Esta instalación utiliza MySQL.

```powershell
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan storage:link
```

Para disponer de cuentas y datos de demostración, revisar y personalizar los usuarios de `database/seeders/` y luego ejecutar:

```powershell
php artisan db:seed
```

Las credenciales de acceso corresponden a las cuentas configuradas en esos seeders.

## 3. Preparar el componente analítico

```powershell
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r nutrition-analytics/requirements.txt
.\.venv\Scripts\python.exe nutrition-analytics/scripts/run_kmeans_experiments.py --k 4 --seed 42
.\.venv\Scripts\python.exe nutrition-analytics/scripts/train_decision_tree.py
php artisan ml:register-candidate
```

Los scripts generan los datasets sintéticos y los modelos necesarios; estos archivos no vienen incluidos en el repositorio. El último comando registra el modelo candidato de K-means y sus grupos en la base de datos. Para utilizar los grupos en el sistema, completar su revisión y asociación con patrones alimenticios en la sección de administración del aporte.

Agregar a `.env` la ruta absoluta del intérprete del entorno creado, reemplazando la carpeta por la ubicación real del proyecto:

```dotenv
PYTHON_BINARY="C:/ruta/al/proyecto/.venv/Scripts/python.exe"
```

Después de modificar `.env`, ejecutar `php artisan config:clear` y reiniciar los procesos de Laravel que estén ejecutándose.

## 4. Iniciar el sistema

Compilar la interfaz:

```powershell
pnpm run build
```

En una terminal, iniciar Laravel:

```powershell
php artisan serve
```

En otra terminal, iniciar el procesamiento de trabajos:

```powershell
php artisan queue:work
```

Abrir **http://127.0.0.1:8000** e ingresar con una cuenta creada por los seeders.

Para trabajar en la interfaz con actualización automática, ejecutar `pnpm run dev` en una tercera terminal mientras Laravel continúa en ejecución.

## 5. Configuración opcional de USDA

Para buscar o importar alimentos mediante USDA, agregar una clave válida a `.env`:

```dotenv
USDA_API_KEY=tu_clave_usda
```

Ejecutar `php artisan config:clear` y reiniciar el trabajador de colas si estaba en ejecución.
