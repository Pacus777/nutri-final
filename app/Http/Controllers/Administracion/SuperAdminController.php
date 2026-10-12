<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SuperAdminController extends Controller
{
    /**
     * Renderiza la vista principal del Panel de SuperAdmin.
     */
    public function index(): Response
    {
        // Asegurar que la carpeta de respaldos existe
        if (!Storage::disk('local')->exists('backups')) {
            Storage::disk('local')->makeDirectory('backups');
        }

        return Inertia::render('Admin/PanelSuperAdmin', [
            'backups' => $this->listBackups(),
            'logs' => $this->getLogs(),
        ]);
    }

    /**
     * Genera un nuevo respaldo de la base de datos (SQL dump) utilizando PHP puro para asegurar portabilidad.
     */
    public function crearBackup()
    {
        try {
            if (!Storage::disk('local')->exists('backups')) {
                Storage::disk('local')->makeDirectory('backups');
            }

            $tables = [];
            $result = DB::select('SHOW TABLES');
            
            foreach ($result as $row) {
                $rowArray = (array)$row;
                $tables[] = array_values($rowArray)[0];
            }

            $dbName = config('database.connections.mysql.database');
            $sql = "-- Respaldo de Base de Datos para: {$dbName}\n";
            $sql .= "-- Generado por Antigravity SuperAdmin Panel\n";
            $sql .= "-- Fecha: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Obtener sentencia de creación de tabla
                $createTableResult = DB::select("SHOW CREATE TABLE `{$table}`");
                $createTableSql = ((array)$createTableResult[0])['Create Table'] ?? '';
                
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTableSql . ";\n\n";

                // Obtener registros de la tabla
                $rows = DB::table($table)->get();
                if ($rows->count() > 0) {
                    $sql .= "INSERT INTO `{$table}` VALUES \n";
                    $values = [];
                    foreach ($rows as $row) {
                        $rowArray = (array)$row;
                        $escapedValues = array_map(function($val) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return DB::getPdo()->quote($val);
                        }, $rowArray);
                        $values[] = "(" . implode(',', $escapedValues) . ")";
                    }
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = "backup-" . now()->format('Y-m-d-H-i-s') . ".sql";
            Storage::disk('local')->put("backups/" . $filename, $sql);

            return redirect()->back()->with('success', "Respaldo '{$filename}' creado correctamente.");
        } catch (\Throwable $e) {
            Log::error('Error al crear respaldo de base de datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['backup' => 'No se pudo crear el respaldo: ' . $e->getMessage()]);
        }
    }

    /**
     * Descarga un archivo de respaldo.
     */
    public function descargarBackup(string $filename)
    {
        $filePath = "backups/" . $filename;
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'El archivo de respaldo no existe.');
        }

        return Storage::disk('local')->download($filePath);
    }

    /**
     * Restaura la base de datos a partir del respaldo seleccionado.
     */
    public function restaurarBackup(string $filename)
    {
        $filePath = "backups/" . $filename;
        if (!Storage::disk('local')->exists($filePath)) {
            return redirect()->back()->withErrors(['backup' => 'El archivo de respaldo no existe.']);
        }

        try {
            $sql = Storage::disk('local')->get($filePath);

            // Desactivar restricciones, ejecutar sentencias SQL y reactivar
            DB::unprepared("SET FOREIGN_KEY_CHECKS=0;");
            DB::unprepared($sql);
            DB::unprepared("SET FOREIGN_KEY_CHECKS=1;");

            Log::warning("Base de datos restaurada por un SuperAdmin desde el archivo: {$filename}");

            return redirect()->back()->with('success', 'Base de datos restaurada correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al restaurar la base de datos', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->withErrors(['backup' => 'Error crítico al restaurar la base de datos: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina físicamente un archivo de respaldo.
     */
    public function eliminarBackup(string $filename)
    {
        $filePath = "backups/" . $filename;
        if (Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
            return redirect()->back()->with('success', 'Respaldo eliminado correctamente.');
        }

        return redirect()->back()->withErrors(['backup' => 'El archivo no existe o ya fue eliminado.']);
    }

    /**
     * Descarga el archivo laravel.log completo.
     */
    public function descargarLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            abort(404, 'No hay archivo de logs disponible.');
        }

        return response()->download($logPath);
    }


    /**
     * Retorna una lista estructurada de los respaldos disponibles.
     */
    private function listBackups(): array
    {
        $files = Storage::disk('local')->files('backups');
        $backups = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $filename = basename($file);
                $backups[] = [
                    'filename' => $filename,
                    'size' => $this->formatBytes(Storage::disk('local')->size($file)),
                    'created_at' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
                ];
            }
        }

        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * Lee y parsea las entradas del archivo laravel.log para presentarlas ordenadas por fecha descendente.
     */
    private function getLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return [];
        }

        $fileSize = filesize($logPath);
        if ($fileSize === 0) {
            return [];
        }

        $maxBytes = 2 * 1024 * 1024; // 2MB max
        if ($fileSize > $maxBytes) {
            $handle = fopen($logPath, 'r');
            fseek($handle, -$maxBytes, SEEK_END);
            $content = fread($handle, $maxBytes);
            fclose($handle);
            
            $pos = strpos($content, '[');
            if ($pos !== false) {
                $content = substr($content, $pos);
            }
        } else {
            $content = file_get_contents($logPath);
        }

        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/m';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $logs = [];
        $count = count($parts);
        
        for ($i = 1; $i < $count; $i += 2) {
            $timestamp = $parts[$i];
            $body = $parts[$i + 1] ?? '';
            
            $bodyPattern = '/^\s*([a-zA-Z0-9_-]+)\.([A-Z]+):\s*(.*?)(?=\s*\{|$)/s';
            
            $env = 'local';
            $level = 'INFO';
            $message = trim($body);
            $details = '';
            
            if (preg_match($bodyPattern, $body, $matches)) {
                $env = $matches[1];
                $level = $matches[2];
                $message = trim($matches[3]);
                
                $rest = substr($body, strlen($matches[0]));
                $details = trim($rest);
            }
            
            $logs[] = [
                'timestamp' => $timestamp,
                'environment' => $env,
                'level' => $level,
                'message' => $message,
                'details' => $details,
            ];
        }

        return array_reverse($logs);
    }

    /**
     * Renderiza el Dashboard de Documentación Técnica del Sistema.
     */
    public function documentacion(Request $request): Response
    {
        $documentos = $this->obtenerArticulosDocumentacion();

        return Inertia::render('Admin/DocumentacionDashboard', [
            'documentos' => $documentos,
            'totalDocumentos' => count($documentos),
        ]);
    }

    /**
     * Escanea dinámicamente la carpeta documentacion_sistema/ y extrae artículos y categorías.
     */
    private function obtenerArticulosDocumentacion(): array
    {
        $baseDir = base_path('documentacion_sistema');
        if (!is_dir($baseDir)) {
            return [];
        }

        $articulos = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $relativePath = str_replace([$baseDir . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file->getPathname());
                $parts = explode('/', $relativePath);
                
                // Categoría basada en la carpeta padre
                $categoriaCarpeta = count($parts) > 1 ? $parts[0] : 'General';
                $categoriaNombre = ucwords(str_replace(['_', '-'], ' ', preg_replace('/^\d+_/', '', $categoriaCarpeta)));

                $contenido = file_get_contents($file->getPathname());

                // Extraer primer título H1
                $titulo = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                if (preg_match('/^#\s+(.+)$/m', $contenido, $matches)) {
                    $titulo = trim($matches[1]);
                } else {
                    $titulo = ucwords(str_replace(['_', '-'], ' ', preg_replace('/^\d+_/', '', $titulo)));
                }

                $articulos[] = [
                    'id' => md5($relativePath),
                    'slug' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'ruta' => $relativePath,
                    'categoria' => $categoriaNombre,
                    'categoria_slug' => $categoriaCarpeta,
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'modificado' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Ordenar artículos por ruta para mantener orden lógico de numeración
        usort($articulos, fn ($a, $b) => strcmp($a['ruta'], $b['ruta']));

        return $articulos;
    }

    /**
     * Formatea bytes a formatos legibles.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

