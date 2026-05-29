<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Contribuyente;

class ApiController extends Controller
{
    /**
     * Helper para formatear montos en CLP (Peso Chileno).
     */
    private function formatCLP($value)
    {
        return '$' . number_format($value, 0, ',', '.');
    }

    /**
     * Obtener el desglose de Aporte Ciudadano y Destino de Impuestos.
     * Soporta consulta de contribuyente por RUT (búsqueda segura por hash).
     */
    public function getDestinoImpuestos(Request $request)
    {
        try {
            // 1. Obtener Metadatos Generales
            $metadata = DB::table('metadata')->first();
            if (!$metadata) {
                throw new \Exception("Base de datos vacía o no migrada.");
            }

            // 2. Obtener Recaudación por Ítem
            $recaudacionItems = DB::table('recaudacion_items')
                ->select('nombre', 'monto', 'porcentaje')
                ->get();

            // 3. Obtener Destino por Área y sus Sub-ítems
            $areas = DB::table('gasto_areas')->get();
            $destinoPorArea = [];
            foreach ($areas as $area) {
                $subItems = DB::table('gasto_subitems')
                    ->where('area_id', $area->id)
                    ->select('nombre', 'monto')
                    ->get();

                $destinoPorArea[] = [
                    'area' => $area->area,
                    'icono' => $area->icono,
                    'color' => $area->color,
                    'montoAsignado' => (int) $area->monto_asignado,
                    'porcentaje' => (float) $area->porcentaje,
                    'descripcion' => $area->descripcion,
                    'subItems' => $subItems->toArray()
                ];
            }

            // 4. Obtener Proyecciones Financieras
            $proyeccionesRaw = DB::table('proyecciones_areas')->get();
            $proyeccionPorArea = [];
            $anioProyectado = 2026;
            foreach ($proyeccionesRaw as $p) {
                $anioProyectado = $p->anio;
                $proyeccionPorArea[] = [
                    'area' => $p->area,
                    'montoProyectado' => (int) $p->monto_proyectado,
                    'variacion' => (float) $p->variacion
                ];
            }

            // 5. Cargar Contribuyente (por defecto el contribuyente semilla si no se especifica)
            $rutParam = $request->query('rut', '12.345.678-9');
            $rutHash = Contribuyente::hashRut($rutParam);

            // Buscar en base de datos usando el Hash SHA-256 (no reversible, alta velocidad y privacidad)
            $contribuyente = Contribuyente::where('rut_hash', $rutHash)->first();

            // Si no se encuentra en base de datos, usamos los datos del perfil semilla encriptado
            if (!$contribuyente) {
                // Caída elegante a un perfil por defecto seguro
                $contribuyente = Contribuyente::where('id', 1)->first();
            }

            $userResponse = [
                'nombre' => $contribuyente ? $contribuyente->nombre_encriptado : 'Usuario Simulado',
                'rut' => $contribuyente ? $contribuyente->rut_encriptado : '12.345.678-9',
                'recaudacionTotalUsuario' => $contribuyente ? ($contribuyente->aporte_contribucion + $contribuyente->aporte_circulacion + $contribuyente->aporte_aseo) : 728000,
                'detalles' => [
                    'contribucion' => $contribuyente ? (int) $contribuyente->aporte_contribucion : 485000,
                    'circulacion' => $contribuyente ? (int) $contribuyente->aporte_circulacion : 165000,
                    'aseo' => $contribuyente ? (int) $contribuyente->aporte_aseo : 78000
                ],
                'mensual' => $contribuyente ? $contribuyente->valores_mensuales : [58000,58000,62000,60000,65000,60000,62000,63000,58000,62000,60000,60000]
            ];

            return response()->json([
                'metadata' => [
                    'ultimaActualizacion' => $metadata->ultima_actualizacion,
                    'fuente' => $metadata->fuente,
                    'periodoInformado' => $metadata->periodo_informado,
                    'recaudacionTotal' => (int) $metadata->recaudacion_total,
                    'gastoTotal' => (int) $metadata->gasto_total,
                    'poblacionComuna' => (int) $metadata->poblacion_comuna
                ],
                'resumenRecaudacion' => [
                    'total' => (int) $metadata->recaudacion_total,
                    'items' => $recaudacionItems
                ],
                'destinoPorArea' => $destinoPorArea,
                'proyeccionesFinancieras' => [
                    'anioProyectado' => $anioProyectado,
                    'ingresoProyectado' => 13500000000,
                    'gastoProyectado' => 12800000000,
                    'proyeccionPorArea' => $proyeccionPorArea
                ],
                'contribuyenteInfo' => $userResponse
            ]);

        } catch (\Exception $e) {
            // Loguear el error interno pero no exponer detalles técnicos al cliente (ISO 27001)
            Log::warning("ApiController::getDestinoImpuestos - Fallback a archivo estático: " . $e->getMessage());

            // Caída elegante (Graceful Fallback) a archivos JSON locales
            $staticPath = public_path('data/destino-impuestos.json');
            if (file_exists($staticPath)) {
                $jsonData = json_decode(file_get_contents($staticPath), true);
                // Inyectar perfil de contribuyente por defecto simulado
                $jsonData['contribuyenteInfo'] = [
                    'nombre' => 'Alonso Alexander Maurel Murgas',
                    'rut' => '12.345.678-9',
                    'recaudacionTotalUsuario' => 728000,
                    'detalles' => [
                        'contribucion' => 485000,
                        'circulacion' => 165000,
                        'aseo' => 78000
                    ],
                    'mensual' => [58000,58000,62000,60000,65000,60000,62000,63000,58000,62000,60000,60000]
                ];
                return response()->json($jsonData);
            }

            return response()->json(['error' => 'No se pudo cargar la información.'], 500);
        }
    }

    /**
     * Obtener el estado del Presupuesto Municipal y la ejecución presupuestaria.
     */
    public function getPresupuesto(Request $request)
    {
        try {
            $areas = DB::table('gasto_areas')
                ->select('area', 'monto_asignado as asignado')
                ->get();

            if ($areas->isEmpty()) {
                throw new \Exception("Tabla gasto_areas vacía.");
            }

            // Simular nivel de ejecución de forma realista si no hay tabla de ejecución
            // (En un sistema avanzado, la tabla de ejecuciones se poblaría mensualmente)
            $pctEjecMap = [
                'Educación' => 92.6,
                'Salud' => 95.0,
                'Seguridad Ciudadana' => 95.0,
                'Obras Municipales' => 87.9,
                'Servicios Comunitarios' => 94.0,
                'Medio Ambiente' => 95.0,
                'Cultura y Deporte' => 89.9,
                'Administración' => 77.0
            ];

            $items = [];
            foreach ($areas as $a) {
                $pct = $pctEjecMap[$a->area] ?? 90.0;
                $ejecutado = round($a->asignado * ($pct / 100));
                $items[] = [
                    'area' => $a->area,
                    'asignado' => (int) $a->asignado,
                    'ejecutado' => (int) $ejecutado,
                    'pctEjec' => $pct
                ];
            }

            return response()->json($items);

        } catch (\Exception $e) {
            Log::warning("ApiController::getPresupuesto - Fallback a datos duros: " . $e->getMessage());

            // Datos estáticos de respaldo idénticos a los del frontend original
            $backupItems = [
                ['area' => 'Educación', 'asignado' => 3576000000, 'ejecutado' => 3312000000, 'pctEjec' => 92.6],
                ['area' => 'Salud', 'asignado' => 2384000000, 'ejecutado' => 2265000000, 'pctEjec' => 95.0],
                ['area' => 'Seguridad Ciudadana', 'asignado' => 1430000000, 'ejecutado' => 1358000000, 'pctEjec' => 95.0],
                ['area' => 'Obras Municipales', 'asignado' => 1192000000, 'ejecutado' => 1048000000, 'pctEjec' => 87.9],
                ['area' => 'Servicios Comunitarios', 'asignado' => 952000000, 'ejecutado' => 895000000, 'pctEjec' => 94.0],
                ['area' => 'Medio Ambiente', 'asignado' => 714000000, 'ejecutado' => 678000000, 'pctEjec' => 95.0],
                ['area' => 'Cultura y Deporte', 'asignado' => 595000000, 'ejecutado' => 535000000, 'pctEjec' => 89.9],
                ['area' => 'Administración', 'asignado' => 1077000000, 'ejecutado' => 829000000, 'pctEjec' => 77.0]
            ];
            return response()->json($backupItems);
        }
    }

    /**
     * Obtener listado de Proyectos Municipales.
     * Permite búsquedas y filtrados dinámicos en el servidor.
     */
    public function getProyectos(Request $request)
    {
        try {
            $query = DB::table('proyectos');

            // Filtrar por área si se indica
            if ($request->has('area') && $request->input('area') !== '__all__') {
                $query->where('area', 'like', '%' . $request->input('area') . '%');
            }

            // Buscar por término libre
            if ($request->has('search') && !empty($request->input('search'))) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', '%' . $search . '%')
                      ->orWhere('codigo', 'like', '%' . $search . '%');
                });
            }

            return response()->json($query->get());

        } catch (\Exception $e) {
            Log::warning("ApiController::getProyectos - Fallback a datos duros: " . $e->getMessage());

            // Backup idéntico de proyectos
            $proyectos = [
                ['id' => 1, 'codigo' => 'P-2025-001', 'nombre' => 'Alumbrado Público Costanera', 'area' => 'Seguridad', 'monto' => 185000000, 'porcentaje' => 1.55, 'estado' => 'Completado'],
                ['id' => 2, 'codigo' => 'P-2025-002', 'nombre' => 'Multicancha Las Acacias', 'area' => 'Deporte', 'monto' => 320000000, 'porcentaje' => 2.42, 'estado' => 'En Ejecución'],
                ['id' => 3, 'codigo' => 'P-2025-003', 'nombre' => 'Pavimento Av. Principal', 'area' => 'Obras', 'monto' => 450000000, 'porcentaje' => 3.78, 'estado' => 'Completado'],
                ['id' => 4, 'codigo' => 'P-2025-004', 'nombre' => 'Ampliación CESFAM', 'area' => 'Salud', 'monto' => 680000000, 'porcentaje' => 4.28, 'estado' => 'En Ejecución'],
                ['id' => 5, 'codigo' => 'P-2025-005', 'nombre' => 'Televigilancia Centro', 'area' => 'Seguridad', 'monto' => 220000000, 'porcentaje' => 1.85, 'estado' => 'Completado'],
                ['id' => 6, 'codigo' => 'P-2025-006', 'nombre' => 'Restauración Escuela', 'area' => 'Educación', 'monto' => 280000000, 'porcentaje' => 1.64, 'estado' => 'En Ejecución'],
                ['id' => 7, 'codigo' => 'P-2025-007', 'nombre' => 'Reciclaje Comunal', 'area' => 'Medio Ambiente', 'monto' => 95000000, 'porcentaje' => 0.80, 'estado' => 'Completado'],
                ['id' => 8, 'codigo' => 'P-2025-008', 'nombre' => 'Centro Adulto Mayor', 'area' => 'Comunitario', 'monto' => 150000000, 'porcentaje' => 1.01, 'estado' => 'En Ejecución'],
                ['id' => 9, 'codigo' => 'P-2025-009', 'nombre' => 'Borde Costero', 'area' => 'Obras', 'monto' => 520000000, 'porcentaje' => 3.06, 'estado' => 'En Ejecución'],
                ['id' => 10, 'codigo' => 'P-2025-010', 'nombre' => 'Digitalización Municipal', 'area' => 'Admin', 'monto' => 75000000, 'porcentaje' => 0.63, 'estado' => 'Completado']
            ];

            return response()->json($proyectos);
        }
    }

    /**
     * Obtener listado de Servicios Municipales Contratados.
     */
    public function getServicios(Request $request)
    {
        try {
            $query = DB::table('servicios');

            if ($request->has('search') && !empty($request->input('search'))) {
                $search = $request->input('search');
                $query->where('servicio', 'like', '%' . $search . '%')
                      ->orWhere('proveedor', 'like', '%' . $search . '%');
            }

            return response()->json($query->get());

        } catch (\Exception $e) {
            Log::warning("ApiController::getServicios - Fallback a datos duros: " . $e->getMessage());

            $servicios = [
                ['servicio' => 'Recolección de Residuos', 'proveedor' => 'Servicios Ambientales SpA', 'monto' => 321000000, 'porcentaje' => 2.69],
                ['servicio' => 'Mantención Alumbrado', 'proveedor' => 'Enel Distribución', 'monto' => 180000000, 'porcentaje' => 1.51],
                ['servicio' => 'Seguridad Edificios', 'proveedor' => 'Securitas Chile S.A.', 'monto' => 96000000, 'porcentaje' => 0.81],
                ['servicio' => 'Transporte Escolar', 'proveedor' => 'Transportes Litoral', 'monto' => 72000000, 'porcentaje' => 0.60],
                ['servicio' => 'Mantención Áreas Verdes', 'proveedor' => 'Jardines del Pacífico', 'monto' => 145000000, 'porcentaje' => 1.22],
                ['servicio' => 'Conectividad Internet', 'proveedor' => 'Movistar Chile', 'monto' => 36000000, 'porcentaje' => 0.30],
                ['servicio' => 'Alimentación CESFAM', 'proveedor' => 'Catering Municipal', 'monto' => 48000000, 'porcentaje' => 0.40]
            ];

            return response()->json($servicios);
        }
    }

    /**
     * Procesar inicio de sesión seguro (RUT + Contraseña).
     * Valida credenciales contra base de datos usando Bcrypt.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'rut' => 'required|string',
                'password' => 'required|string',
            ]);

            $rut = $request->input('rut');
            $password = $request->input('password');

            // Calcular Hash SHA-256 del RUT normalizado
            $rutHash = Contribuyente::hashRut($rut);

            // Buscar en base de datos
            $contribuyente = Contribuyente::where('rut_hash', $rutHash)->first();

            if (!$contribuyente || !\Illuminate\Support\Facades\Hash::check($password, $contribuyente->password_hash)) {
                return response()->json(['error' => 'RUT o contraseña incorrectos.'], 401);
            }

            // Si es correcto, retornamos la información del contribuyente descifrada en memoria
            return response()->json([
                'success' => true,
                'token' => bin2hex(random_bytes(16)), // Generar un token de sesión seguro simulado
                'user' => [
                    'nombre' => $contribuyente->nombre_encriptado,
                    'rut' => $contribuyente->rut_encriptado,
                    'rol' => $contribuyente->rol,
                    'recaudacionTotalUsuario' => ($contribuyente->aporte_contribucion + $contribuyente->aporte_circulacion + $contribuyente->aporte_aseo),
                    'detalles' => [
                        'contribucion' => (int) $contribuyente->aporte_contribucion,
                        'circulacion' => (int) $contribuyente->aporte_circulacion,
                        'aseo' => (int) $contribuyente->aporte_aseo
                    ],
                    'mensual' => $contribuyente->valores_mensuales
                ]
            ]);

        } catch (\Exception $e) {
            Log::warning("ApiController::login - Fallback a simulación sin base de datos: " . $e->getMessage());
            
            // Simulación offline en caso de fallo de base de datos
            $rut = $request->input('rut');
            $password = $request->input('password');
            $cleanRut = strtolower(str_replace(['.', '-', ' '], '', $rut));
            
            if ($cleanRut === '123456789' && $password === 'Pb_123@01') {
                return response()->json([
                    'success' => true,
                    'token' => 'offline_token_a_12345',
                    'user' => [
                        'nombre' => 'Alonso Alexander Maurel Murgas',
                        'rut' => '12.345.678-9',
                        'rol' => 'ciudadano',
                        'recaudacionTotalUsuario' => 728000,
                        'detalles' => ['contribucion' => 485000, 'circulacion' => 165000, 'aseo' => 78000],
                        'mensual' => [58000,58000,62000,60000,65000,60000,62000,63000,58000,62000,60000,60000]
                    ]
                ]);
            } elseif ($cleanRut === '892342554' && $password === 'Pb_321@02') {
                return response()->json([
                    'success' => true,
                    'token' => 'offline_token_b_67890',
                    'user' => [
                        'nombre' => 'Sofía Elizabeth Álvarez Pérez',
                        'rut' => '89.234.255-4',
                        'rol' => 'ciudadano',
                        'recaudacionTotalUsuario' => 5000000,
                        'detalles' => ['contribucion' => 3500000, 'circulacion' => 1200000, 'aseo' => 300000],
                        'mensual' => [400000,420000,410000,430000,420000,410000,420000,430000,410000,420000,410000,420000]
                    ]
                ]);
            } elseif ($cleanRut === '87654321' && $password === 'Pb_123@03') {
                return response()->json([
                    'success' => true,
                    'token' => 'offline_token_admin_9999',
                    'user' => [
                        'nombre' => 'Administrador Municipal',
                        'rut' => '8.765.432-1',
                        'rol' => 'admin',
                        'recaudacionTotalUsuario' => 0,
                        'detalles' => ['contribucion' => 0, 'circulacion' => 0, 'aseo' => 0],
                        'mensual' => []
                    ]
                ]);
            }
            
            return response()->json(['error' => 'RUT o contraseña incorrectos.'], 401);
        }
    }

    /**
     * Obtener listado de todos los períodos de consulta de transparencia.
     */
    public function getPeriodos()
    {
        try {
            $periodos = DB::table('periodos_consulta')
                ->select('id', 'anio', 'mes', 'nombre_mes', 'habilitado')
                ->orderBy('anio', 'desc')
                ->orderBy(DB::raw("CASE WHEN mes = 'anual' THEN 0 ELSE CAST(mes AS UNSIGNED) END"), 'asc')
                ->get();
            return response()->json($periodos);
        } catch (\Exception $e) {
            Log::warning("ApiController::getPeriodos - Fallback a vacio por error de base de datos: " . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Activar o desactivar un período específico en la base de datos (control administrativo).
     */
    public function togglePeriodo(Request $request)
    {
        try {
            $request->validate([
                'anio' => 'required|integer',
                'mes' => 'required|string',
                'habilitado' => 'required|boolean'
            ]);

            $anio = $request->input('anio');
            $mes = $request->input('mes');
            $habilitado = $request->input('habilitado') ? 1 : 0;

            DB::table('periodos_consulta')
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->update(['habilitado' => $habilitado]);

            return response()->json([
                'success' => true,
                'mensaje' => 'Período de consulta actualizado correctamente en MySQL.'
            ]);
        } catch (\Exception $e) {
            Log::error("ApiController::togglePeriodo - Error: " . $e->getMessage());
            return response()->json([
                'error' => 'No se pudo actualizar el período en el servidor.'
            ], 500);
        }
    }
}
