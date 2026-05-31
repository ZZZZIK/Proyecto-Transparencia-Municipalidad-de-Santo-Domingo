<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Contribuyente;

class ApiController extends \Illuminate\Routing\Controller
{
    /**
     * Asegura la existencia de la tabla cargas_transparencia de manera automática sin requerir migración manual.
     */
    private function ensureAuditTableExists()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('cargas_transparencia')) {
            \Illuminate\Support\Facades\Schema::create('cargas_transparencia', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments('id');
                $table->string('admin_rut_hash', 64);
                $table->string('tipo_carga', 50);
                $table->string('nombre_archivo', 255);
                $table->integer('registros_procesados')->default(0);
                $table->integer('registros_actualizados')->default(0);
                $table->integer('registros_insertados')->default(0);
                $table->string('estado', 20)->default('exitoso');
                $table->text('detalle_error')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

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
            // 1. Obtener Metadatos Generales desde Base de Datos
            $metadata = DB::table('metadata')->first();
            if (!$metadata) {
                throw new \Exception("Base de datos vacía o no migrada.");
            }

            // Realizar cálculo dinámico centralizado
            $calc = $this->calcularPresupuestoDinamico(2000);
            $totalIngresos = $calc['ingresos']['total'];
            $totalGastos = $calc['gastos']['total'];

            // 2. Generar Recaudación por Ítem Dinámica y Proporcional
            $recaudacionItems = [
                [
                    'nombre' => 'Impuesto Territorial',
                    'monto' => $calc['escalado']['aporte_contribucion'],
                    'porcentaje' => (float) round(($calc['escalado']['aporte_contribucion'] / $totalIngresos) * 100, 2)
                ],
                [
                    'nombre' => 'Permisos de Circulación',
                    'monto' => $calc['escalado']['aporte_circulacion'],
                    'porcentaje' => (float) round(($calc['escalado']['aporte_circulacion'] / $totalIngresos) * 100, 2)
                ],
                [
                    'nombre' => 'Derechos de Aseo',
                    'monto' => $calc['escalado']['aporte_aseo'],
                    'porcentaje' => (float) round(($calc['escalado']['aporte_aseo'] / $totalIngresos) * 100, 2)
                ],
                [
                    'nombre' => 'Fondo Común Municipal',
                    'monto' => $calc['ingresos']['fcm'],
                    'porcentaje' => 45.00
                ],
                [
                    'nombre' => 'Patentes Municipales',
                    'monto' => $calc['ingresos']['patentes'],
                    'porcentaje' => 15.00
                ],
                [
                    'nombre' => 'Otros Ingresos',
                    'monto' => $calc['ingresos']['otros'],
                    'porcentaje' => 10.00
                ]
            ];

            // 3. Obtener Destino por Área y sus Sub-ítems (Escalados proporcionalmente)
            $areas = DB::table('gasto_areas')->get();
            $destinoPorArea = [];
            foreach ($areas as $area) {
                // Calcular asignado en base al porcentaje de ponderación predefinido del área
                $montoAsignadoArea = round($totalGastos * ($area->porcentaje / 100));

                $subItems = DB::table('gasto_subitems')
                    ->where('area_id', $area->id)
                    ->select('nombre', 'monto')
                    ->get();

                // Sumatoria de montos base de subitems para escalado proporcional cascada
                $subItemsTotalBase = $subItems->sum('monto');
                $scaledSubItems = [];

                foreach ($subItems as $sub) {
                    $scaledMonto = $subItemsTotalBase > 0 
                        ? (int) round($sub->monto * ($montoAsignadoArea / $subItemsTotalBase)) 
                        : 0;
                    $scaledSubItems[] = [
                        'nombre' => $sub->nombre,
                        'monto' => $scaledMonto
                    ];
                }

                $destinoPorArea[] = [
                    'area' => $area->area,
                    'icono' => $area->icono,
                    'color' => $area->color,
                    'montoAsignado' => (int) $montoAsignadoArea,
                    'porcentaje' => (float) $area->porcentaje,
                    'descripcion' => $area->descripcion,
                    'subItems' => $scaledSubItems
                ];
            }

            // 4. Obtener Proyecciones Financieras (Escaladas de acuerdo al nuevo presupuesto de gastos)
            $proyeccionesRaw = DB::table('proyecciones_areas')->get();
            $proyeccionPorArea = [];
            $anioProyectado = 2026;
            foreach ($proyeccionesRaw as $p) {
                $anioProyectado = $p->anio;
                // Escalar proyecciones en base al porcentaje y tasa de variación esperada
                // Para mantener coherencia, calculamos la proyeccion basándonos en el asignado actual del área
                $areaObj = $areas->firstWhere('area', $p->area);
                $asigActual = $areaObj ? round($totalGastos * ($areaObj->porcentaje / 100)) : 1000000000;
                $montoProy = round($asigActual * (1 + $p->variacion / 100));

                $proyeccionPorArea[] = [
                    'area' => $p->area,
                    'montoProyectado' => (int) $montoProy,
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
                'nombre' => $contribuyente ? $this->deserializarSiEsNecesario($contribuyente->nombre_encriptado) : 'Usuario Simulado',
                'rut' => $contribuyente ? $this->deserializarSiEsNecesario($contribuyente->rut_encriptado) : '12.345.678-9',
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
                    'recaudacionTotal' => (int) $totalIngresos,
                    'gastoTotal' => (int) $totalGastos,
                    'poblacionComuna' => (int) $metadata->poblacion_comuna
                ],
                'resumenRecaudacion' => [
                    'total' => (int) $totalIngresos,
                    'items' => $recaudacionItems
                ],
                'destinoPorArea' => $destinoPorArea,
                'proyeccionesFinancieras' => [
                    'anioProyectado' => $anioProyectado,
                    'ingresoProyectado' => (int) round($totalIngresos * 1.05), // +5% proyeccion realista
                    'gastoProyectado' => (int) round($totalGastos * 1.05),
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
            $calc = $this->calcularPresupuestoDinamico(2000);
            $totalGastos = $calc['gastos']['total'];

            $areas = DB::table('gasto_areas')->get();

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
                
                // Calcular el asignado en base al porcentaje de ponderación predefinido de cada área
                $asignado = round($totalGastos * ($a->porcentaje / 100));
                $ejecutado = round($asignado * ($pct / 100));
                
                $items[] = [
                    'area' => $a->area,
                    'asignado' => (int) $asignado,
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

            $proyectos = $query->get();
            $calc = $this->calcularPresupuestoDinamico(2000);
            $newGastoTotal = $calc['gastos']['total'];
            $scaleRatio = $newGastoTotal / 11920000000;

            foreach ($proyectos as $p) {
                $p->monto = (int) round($p->monto * $scaleRatio);
                $p->porcentaje = (float) round(($p->monto / $newGastoTotal) * 100, 2);
            }

            return response()->json($proyectos);

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

            $servicios = $query->get();
            $calc = $this->calcularPresupuestoDinamico(2000);
            $newGastoTotal = $calc['gastos']['total'];
            $scaleRatio = $newGastoTotal / 11920000000;

            foreach ($servicios as $s) {
                $s->monto = (int) round($s->monto * $scaleRatio);
                $s->porcentaje = (float) round(($s->monto / $newGastoTotal) * 100, 2);
            }

            return response()->json($servicios);

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
                    'nombre' => $this->deserializarSiEsNecesario($contribuyente->nombre_encriptado),
                    'rut' => $this->deserializarSiEsNecesario($contribuyente->rut_encriptado),
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

    // =========================================================================
    // RF11 — Carga Masiva de Datos de Transparencia (Administrador Municipal)
    // Cumplimiento ISO 27001 (Seguridad) e ISO 27701 (Privacidad de Datos)
    // =========================================================================

    /**
     * Columnas requeridas por cada tipo de carga CSV.
     */
    private function getRequiredColumns($tipo)
    {
        $schemas = [
            'recaudacion'     => ['nombre', 'monto', 'porcentaje'],
            'gastos'          => ['area', 'icono', 'color', 'monto_asignado', 'porcentaje', 'descripcion'],
            'proyectos'       => ['codigo', 'nombre', 'area', 'monto', 'porcentaje', 'estado'],
            'servicios'       => ['servicio', 'proveedor', 'monto', 'porcentaje'],
            'metadata'        => ['ultima_actualizacion', 'fuente', 'periodo_informado', 'recaudacion_total', 'gasto_total', 'poblacion_comuna'],
            'contribuyentes'  => ['rut', 'nombre', 'aporte_contribucion', 'aporte_circulacion', 'aporte_aseo'],
        ];
        return $schemas[$tipo] ?? null;
    }

    /**
     * Procesar carga masiva de archivo CSV con datos de transparencia.
     * Solo accesible para el perfil Administrador Municipal.
     *
     * Cumplimiento CP-11:
     *   - Valida formato del archivo y columnas requeridas
     *   - Usa transacción DB para atomicidad (rollback en caso de error)
     *   - Registra log de auditoría con fecha, hora y RUT del administrador
     *   - Los datos quedan visibles inmediatamente para los ciudadanos
     */
    public function uploadTransparencia(Request $request)
    {
        $this->ensureAuditTableExists();

        // 1. Validar inputs básicos
        try {
            $request->validate([
                'archivo'       => 'required|file|mimes:csv,txt|max:5120',
                'tipo_carga'    => 'required|string|in:recaudacion,gastos,proyectos,servicios,metadata,contribuyentes',
                'admin_rut_hash' => 'required|string|size:64',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'error'   => 'Datos de entrada inválidos.',
                'detalle' => $ve->errors()
            ], 422);
        }

        $tipo       = $request->input('tipo_carga');
        $adminHash  = $request->input('admin_rut_hash');
        $file       = $request->file('archivo');
        $fileName   = $file->getClientOriginalName();

        // 2. Verificar que el hash corresponde a un usuario con rol admin
        try {
            $admin = DB::table('contribuyentes')
                ->where('rut_hash', $adminHash)
                ->where('rol', 'admin')
                ->first();

            if (!$admin) {
                return response()->json([
                    'error' => 'Acceso denegado. Solo el Administrador Municipal puede cargar datos.'
                ], 403);
            }
        } catch (\Exception $e) {
            // Cumplimiento estricto ISO 27001 (Fail-Secure) - Evitar Bypass "Fail-Open"
            Log::error("uploadTransparencia - Error crítico de base de datos al verificar administrador: " . $e->getMessage());
            return response()->json([
                'error' => 'Error interno de autenticación: No se pudo verificar los privilegios de administrador en el servidor.'
            ], 500);
        }

        // 3. Parsear archivo CSV
        $requiredCols = $this->getRequiredColumns($tipo);
        if (!$requiredCols) {
            return response()->json(['error' => 'Tipo de carga no válido.'], 422);
        }

        try {
            $csvContent = file_get_contents($file->getRealPath());
            // Eliminar BOM UTF-8 si existe
            $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);
            $lines = array_filter(explode("\n", $csvContent), fn($l) => trim($l) !== '');

            if (count($lines) < 2) {
                return response()->json([
                    'error' => 'El archivo CSV debe contener al menos un encabezado y una fila de datos.'
                ], 422);
            }

            // Parsear encabezados
            $headers = array_map(function($h) {
                return strtolower(trim(str_replace('"', '', $h)));
            }, str_getcsv($lines[0]));

            // Validar columnas requeridas
            $missing = array_diff($requiredCols, $headers);
            if (!empty($missing)) {
                return response()->json([
                    'error'            => 'El archivo CSV no contiene todas las columnas requeridas.',
                    'columnas_faltantes' => array_values($missing),
                    'columnas_requeridas' => $requiredCols,
                    'columnas_encontradas' => $headers,
                ], 422);
            }

            // Parsear filas de datos
            $rows = [];
            for ($i = 1; $i < count($lines); $i++) {
                $values = str_getcsv($lines[$i]);
                if (count($values) < count($headers)) continue; // saltar filas incompletas

                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = isset($values[$idx]) ? trim($values[$idx]) : '';
                }
                $rows[] = $row;
            }

            if (empty($rows)) {
                return response()->json([
                    'error' => 'No se encontraron filas de datos válidas en el archivo CSV.'
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al parsear el archivo CSV.',
                'detalle' => $e->getMessage()
            ], 422);
        }

        // 4. Procesar carga con transacción atómica
        $registrosInsertados = 0;
        $registrosActualizados = 0;

        try {
            DB::beginTransaction();

            switch ($tipo) {
                case 'recaudacion':
                    DB::table('recaudacion_items')->truncate();
                    foreach ($rows as $row) {
                        DB::table('recaudacion_items')->insert([
                            'nombre'     => $row['nombre'],
                            'monto'      => $this->cleanInt($row['monto']),
                            'porcentaje' => (float) $row['porcentaje'],
                        ]);
                        $registrosInsertados++;
                    }
                    break;

                case 'gastos':
                    // Obtener las áreas que vienen en el CSV
                    $csvAreas = array_map(fn($row) => $row['area'], $rows);

                    // Borrar las áreas de la base de datos que ya no vienen en el CSV (esto borrará en cascada sus subitems)
                    DB::table('gasto_areas')->whereNotIn('area', $csvAreas)->delete();

                    foreach ($rows as $row) {
                        $existing = DB::table('gasto_areas')->where('area', $row['area'])->first();
                        
                        if ($existing) {
                            DB::table('gasto_areas')->where('id', $existing->id)->update([
                                'icono'          => $row['icono'],
                                'color'          => $row['color'],
                                'monto_asignado' => $this->cleanInt($row['monto_asignado']),
                                'porcentaje'     => (float) $row['porcentaje'],
                                'descripcion'    => $row['descripcion'],
                            ]);
                            $registrosActualizados++;
                        } else {
                            DB::table('gasto_areas')->insert([
                                'area'           => $row['area'],
                                'icono'          => $row['icono'],
                                'color'          => $row['color'],
                                'monto_asignado' => $this->cleanInt($row['monto_asignado']),
                                'porcentaje'     => (float) $row['porcentaje'],
                                'descripcion'    => $row['descripcion'],
                            ]);
                            $registrosInsertados++;
                        }
                    }
                    break;

                case 'proyectos':
                    DB::table('proyectos')->truncate();
                    foreach ($rows as $row) {
                        DB::table('proyectos')->insert([
                            'codigo'     => $row['codigo'],
                            'nombre'     => $row['nombre'],
                            'area'       => $row['area'],
                            'monto'      => $this->cleanInt($row['monto']),
                            'porcentaje' => (float) $row['porcentaje'],
                            'estado'     => $row['estado'],
                        ]);
                        $registrosInsertados++;
                    }
                    break;

                case 'servicios':
                    DB::table('servicios')->truncate();
                    foreach ($rows as $row) {
                        DB::table('servicios')->insert([
                            'servicio'   => $row['servicio'],
                            'proveedor'  => $row['proveedor'],
                            'monto'      => $this->cleanInt($row['monto']),
                            'porcentaje' => (float) $row['porcentaje'],
                        ]);
                        $registrosInsertados++;
                    }
                    break;

                case 'metadata':
                    $row = $rows[0]; // Solo un registro de metadata
                    $exists = DB::table('metadata')->where('id', 1)->exists();
                    if ($exists) {
                        DB::table('metadata')->where('id', 1)->update([
                            'ultima_actualizacion' => $row['ultima_actualizacion'],
                            'fuente'               => $row['fuente'],
                            'periodo_informado'    => $row['periodo_informado'],
                            'recaudacion_total'    => $this->cleanInt($row['recaudacion_total']),
                            'gasto_total'          => $this->cleanInt($row['gasto_total']),
                            'poblacion_comuna'     => $this->cleanInt($row['poblacion_comuna']),
                        ]);
                        $registrosActualizados = 1;
                    } else {
                        DB::table('metadata')->insert([
                            'ultima_actualizacion' => $row['ultima_actualizacion'],
                            'fuente'               => $row['fuente'],
                            'periodo_informado'    => $row['periodo_informado'],
                            'recaudacion_total'    => $this->cleanInt($row['recaudacion_total']),
                            'gasto_total'          => $this->cleanInt($row['gasto_total']),
                            'poblacion_comuna'     => $this->cleanInt($row['poblacion_comuna']),
                        ]);
                        $registrosInsertados = 1;
                    }
                    break;

                case 'contribuyentes':
                    // Upsert contribuyentes: actualizar aportes SIN tocar credenciales
                    foreach ($rows as $row) {
                        $rutClean = strtolower(str_replace(['.', '-', ' '], '', $row['rut']));
                        $rutHash = hash('sha256', $rutClean);

                        $existing = DB::table('contribuyentes')->where('rut_hash', $rutHash)->first();

                        $totalAporte = $this->cleanInt($row['aporte_contribucion']) + $this->cleanInt($row['aporte_circulacion']) + $this->cleanInt($row['aporte_aseo']);
                        $mensual = $totalAporte > 0 ? round($totalAporte / 12) : 0;
                        $valoresMensuales = json_encode(array_fill(0, 12, round($totalAporte / 12)));

                        if ($existing) {
                            // Actualizar SOLO aportes — NO tocar password_hash ni rol
                            $updateData = [
                                'aporte_contribucion' => $this->cleanInt($row['aporte_contribucion']),
                                'aporte_circulacion'  => $this->cleanInt($row['aporte_circulacion']),
                                'aporte_aseo'         => $this->cleanInt($row['aporte_aseo']),
                                'valores_mensuales'   => $valoresMensuales,
                            ];
                            // Actualizar nombre encriptado si el modelo usa casts
                            try {
                                $contrib = Contribuyente::where('rut_hash', $rutHash)->first();
                                if ($contrib) {
                                    $contrib->nombre_encriptado = $row['nombre'];
                                    $contrib->aporte_contribucion = $this->cleanInt($row['aporte_contribucion']);
                                    $contrib->aporte_circulacion = $this->cleanInt($row['aporte_circulacion']);
                                    $contrib->aporte_aseo = $this->cleanInt($row['aporte_aseo']);
                                    $contrib->valores_mensuales = array_fill(0, 12, round($totalAporte / 12));
                                    $contrib->save();
                                }
                            } catch (\Exception $modelEx) {
                                // Fallback: raw update sin encriptación
                                DB::table('contribuyentes')->where('rut_hash', $rutHash)->update($updateData);
                            }
                            $registrosActualizados++;
                        } else {
                            // Insertar nuevo contribuyente con contraseña por defecto
                            try {
                                $contrib = new Contribuyente();
                                $contrib->rut_hash = $rutHash;
                                $contrib->rut_encriptado = $row['rut'];
                                $contrib->nombre_encriptado = $row['nombre'];
                                $contrib->password_hash = \Illuminate\Support\Facades\Hash::make('Contribuyente@123');
                                $contrib->rol = 'ciudadano';
                                $contrib->aporte_contribucion = $this->cleanInt($row['aporte_contribucion']);
                                $contrib->aporte_circulacion = $this->cleanInt($row['aporte_circulacion']);
                                $contrib->aporte_aseo = $this->cleanInt($row['aporte_aseo']);
                                $contrib->valores_mensuales = array_fill(0, 12, round($totalAporte / 12));
                                $contrib->save();
                            } catch (\Exception $modelEx) {
                                DB::table('contribuyentes')->insert([
                                    'rut_hash'            => $rutHash,
                                    'rut_encriptado'      => $row['rut'],
                                    'nombre_encriptado'   => $row['nombre'],
                                    'password_hash'       => \Illuminate\Support\Facades\Hash::make('Contribuyente@123'),
                                    'rol'                 => 'ciudadano',
                                    'aporte_contribucion' => $this->cleanInt($row['aporte_contribucion']),
                                    'aporte_circulacion'  => $this->cleanInt($row['aporte_circulacion']),
                                    'aporte_aseo'         => $this->cleanInt($row['aporte_aseo']),
                                    'valores_mensuales'   => $valoresMensuales,
                                ]);
                            }
                            $registrosInsertados++;
                        }
                    }

                    // === Propagación en cascada: recalcular recaudación ===
                    $sumContrib = DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_contribucion');
                    $sumCirc    = DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_circulacion');
                    $sumAseo    = DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_aseo');

                    // Actualizar ítems de recaudación
                    DB::table('recaudacion_items')->where('nombre', 'Impuesto Territorial')->update([
                        'monto' => $sumContrib,
                    ]);
                    DB::table('recaudacion_items')->where('nombre', 'Permisos de Circulación')->update([
                        'monto' => $sumCirc,
                    ]);
                    DB::table('recaudacion_items')->where('nombre', 'Derechos de Aseo')->update([
                        'monto' => $sumAseo,
                    ]);

                    // Recalcular recaudación total y porcentajes
                    $newRecTotal = DB::table('recaudacion_items')->sum('monto');
                    if ($newRecTotal > 0) {
                        $allRecItems = DB::table('recaudacion_items')->get();
                        foreach ($allRecItems as $item) {
                            DB::table('recaudacion_items')->where('id', $item->id)->update([
                                'porcentaje' => round(($item->monto / $newRecTotal) * 100, 2),
                            ]);
                        }
                    }

                    // Actualizar metadata
                    DB::table('metadata')->where('id', 1)->update([
                        'recaudacion_total' => $newRecTotal,
                    ]);

                    break;
            }

            // 5. Registrar en tabla de auditoría
            DB::table('cargas_transparencia')->insert([
                'admin_rut_hash'        => $adminHash,
                'tipo_carga'            => $tipo,
                'nombre_archivo'        => $fileName,
                'registros_procesados'  => count($rows),
                'registros_actualizados' => $registrosActualizados,
                'registros_insertados'  => $registrosInsertados,
                'estado'                => 'exitoso',
            ]);

            DB::commit();

            Log::info("uploadTransparencia - Carga exitosa: tipo={$tipo}, archivo={$fileName}, registros=" . count($rows) . ", admin_hash={$adminHash}");

            return response()->json([
                'success' => true,
                'mensaje' => 'Datos de transparencia cargados correctamente.',
                'resumen' => [
                    'tipo'                  => $tipo,
                    'archivo'               => $fileName,
                    'registros_procesados'  => count($rows),
                    'registros_insertados'  => $registrosInsertados,
                    'registros_actualizados' => $registrosActualizados,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Registrar fallo en auditoría (fuera de la transacción revertida)
            try {
                DB::table('cargas_transparencia')->insert([
                    'admin_rut_hash'        => $adminHash,
                    'tipo_carga'            => $tipo,
                    'nombre_archivo'        => $fileName,
                    'registros_procesados'  => count($rows),
                    'registros_actualizados' => 0,
                    'registros_insertados'  => 0,
                    'estado'                => 'revertido',
                    'detalle_error'         => substr($e->getMessage(), 0, 500),
                ]);
            } catch (\Exception $logEx) {
                Log::error("uploadTransparencia - No se pudo registrar el fallo en auditoría: " . $logEx->getMessage());
            }

            Log::error("uploadTransparencia - Error en carga masiva (ROLLBACK): " . $e->getMessage());

            return response()->json([
                'error'   => 'Error al procesar los datos. La operación fue revertida y los datos anteriores se mantienen intactos.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de cargas de transparencia realizadas (Auditoría).
     */
    public function getHistorialCargas()
    {
        $this->ensureAuditTableExists();

        try {
            $cargas = DB::table('cargas_transparencia')
                ->select('id', 'tipo_carga', 'nombre_archivo', 'registros_procesados',
                         'registros_insertados', 'registros_actualizados', 'estado',
                         'detalle_error', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json($cargas);
        } catch (\Exception $e) {
            Log::warning("getHistorialCargas - Error: " . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Descargar plantilla CSV de ejemplo según el tipo de carga solicitado.
     * Incluye encabezados correctos y una fila de ejemplo para guiar al administrador.
     */
    public function descargarPlantilla($tipo)
    {
        $plantillas = [
            'recaudacion' => [
                'headers' => ['nombre', 'monto', 'porcentaje'],
                'ejemplo' => ['Impuesto Territorial', '4112000000', '32.00'],
            ],
            'gastos' => [
                'headers' => ['area', 'icono', 'color', 'monto_asignado', 'porcentaje', 'descripcion'],
                'ejemplo' => ['Educación', 'school', '#2E86AB', '3576000000', '30.00', 'Escuelas liceos e infraestructura educativa'],
            ],
            'proyectos' => [
                'headers' => ['codigo', 'nombre', 'area', 'monto', 'porcentaje', 'estado'],
                'ejemplo' => ['P-2025-001', 'Alumbrado Público Costanera', 'Seguridad', '185000000', '1.55', 'Completado'],
            ],
            'servicios' => [
                'headers' => ['servicio', 'proveedor', 'monto', 'porcentaje'],
                'ejemplo' => ['Recolección de Residuos', 'Servicios Ambientales SpA', '321000000', '2.69'],
            ],
            'metadata' => [
                'headers' => ['ultima_actualizacion', 'fuente', 'periodo_informado', 'recaudacion_total', 'gasto_total', 'poblacion_comuna'],
                'ejemplo' => ['2026-03-31', 'Dirección de Control y Finanzas', 'Año Fiscal 2025', '12850000000', '11920000000', '9800'],
            ],
            'contribuyentes' => [
                'headers' => ['rut', 'nombre', 'aporte_contribucion', 'aporte_circulacion', 'aporte_aseo'],
                'ejemplo' => ['12.345.678-9', 'Alonso Alexander Maurel Murgas', '485000', '165000', '78000'],
            ],
        ];

        if (!isset($plantillas[$tipo])) {
            return response()->json(['error' => 'Tipo de plantilla no válido.'], 404);
        }

        $plantilla = $plantillas[$tipo];
        $csv = "\xEF\xBB\xBF"; // BOM UTF-8 para compatibilidad con Excel
        $csv .= implode(',', $plantilla['headers']) . "\n";
        $csv .= implode(',', $plantilla['ejemplo']) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=plantilla_{$tipo}.csv",
        ]);
    }

    /**
     * Calcula dinámicamente el presupuesto municipal (ingresos y gastos)
     * basándose en las contribuciones reales de los vecinos escaladas.
     *
     * @param int $factorEscala
     * @return array
     */
    public function calcularPresupuestoDinamico($factorEscala = 2000)
    {
        // 1. Obtener la sumatoria de aportes de vecinos reales
        $sumContribReal = (int) DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_contribucion');
        $sumCircReal = (int) DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_circulacion');
        $sumAseoReal = (int) DB::table('contribuyentes')->where('rol', 'ciudadano')->sum('aporte_aseo');
        $totalVecinosReal = $sumContribReal + $sumCircReal + $sumAseoReal;
        $cantidadContribuyentes = (int) DB::table('contribuyentes')->where('rol', 'ciudadano')->count();

        // Si la base de datos está vacía, usamos valores semillas realistas de respaldo
        if ($totalVecinosReal === 0) {
            $sumContribReal = 485000 + 3500000;
            $sumCircReal = 165000 + 1200000;
            $sumAseoReal = 78000 + 300000;
            $totalVecinosReal = $sumContribReal + $sumCircReal + $sumAseoReal;
            $cantidadContribuyentes = 2;
        }

        // 2. Escalado Inicial Realista (Llevar a orden de magnitud de miles de millones)
        $aporteContribucionTotal = $sumContribReal * $factorEscala;
        $aporteCirculacionTotal = $sumCircReal * $factorEscala;
        $aporteAseoTotal = $sumAseoReal * $factorEscala;
        $totalVecinosEscalado = $totalVecinosReal * $factorEscala;

        // 3. Proporción de Ingresos Definida (Cálculo Inverso):
        // El aporte escalado de vecinos ($totalVecinosEscalado) representa exactamente el 30% del Presupuesto Total ($T)
        // T = V_escalado / 0.30
        $presupuestoTotalIngresos = (int) round($totalVecinosEscalado / 0.30);

        // El 70% restante se desglosa visualmente en rubros realistas:
        // Fondo Común Municipal (FCM): 45% del Presupuesto Total (T * 0.45)
        $fcmTotal = (int) round($presupuestoTotalIngresos * 0.45);
        // Patentes Comerciales e Industriales: 15% del Presupuesto Total (T * 0.15)
        $patentesTotal = (int) round($presupuestoTotalIngresos * 0.15);
        // Derechos de Concesión y Otros Ingresos: 10% del Presupuesto Total (T * 0.10)
        $otrosIngresosTotal = (int) round($presupuestoTotalIngresos * 0.10);

        // Ajuste fino para asegurar que la suma de ingresos sume exactamente el Presupuesto Total de Ingresos (debido a redondeos)
        $sumaIngresosCalculados = $totalVecinosEscalado + $fcmTotal + $patentesTotal + $otrosIngresosTotal;
        $diferenciaIngresos = $presupuestoTotalIngresos - $sumaIngresosCalculados;
        if ($diferenciaIngresos !== 0) {
            $fcmTotal += $diferenciaIngresos; // Absorber pequeñas diferencias de redondeo en el FCM
        }

        // 4. Coherencia Ingresos-Gastos (Presupuesto de Gastos Dinámico):
        // El total de gastos representa exactamente el 92% de los ingresos (dejando 8% de superávit)
        $presupuestoTotalGastos = (int) round($presupuestoTotalIngresos * 0.92);
        $superavit = $presupuestoTotalIngresos - $presupuestoTotalGastos;

        return [
            'real' => [
                'aporte_contribucion' => $sumContribReal,
                'aporte_circulacion' => $sumCircReal,
                'aporte_aseo' => $sumAseoReal,
                'total_vecinos' => $totalVecinosReal,
            ],
            'escalado' => [
                'aporte_contribucion' => $aporteContribucionTotal,
                'aporte_circulacion' => $aporteCirculacionTotal,
                'aporte_aseo' => $aporteAseoTotal,
                'total_vecinos' => $totalVecinosEscalado,
            ],
            'ingresos' => [
                'total' => $presupuestoTotalIngresos,
                'fcm' => $fcmTotal,
                'patentes' => $patentesTotal,
                'otros' => $otrosIngresosTotal,
            ],
            'gastos' => [
                'total' => $presupuestoTotalGastos,
                'superavit' => $superavit,
            ],
            'cantidad_contribuyentes' => $cantidadContribuyentes,
            'factor_escala' => $factorEscala,
        ];
    }

    /**
     * Obtener resumen de contribuciones totales de todos los vecinos.
     * Usado por el frontend para mostrar el total de contribuciones en el presupuesto municipal.
     */
    public function getResumenContribuyentes()
    {
        try {
            $calc = $this->calcularPresupuestoDinamico(2000);

            return response()->json([
                // Valores reales
                'aporte_contribucion_real' => $calc['real']['aporte_contribucion'],
                'aporte_circulacion_real'  => $calc['real']['aporte_circulacion'],
                'aporte_aseo_real'         => $calc['real']['aporte_aseo'],
                'total_vecinos_real'       => $calc['real']['total_vecinos'],

                // Configuración y factores
                'factor_escala'            => $calc['factor_escala'],

                // Valores escalados (requeridos para retrocompatibilidad con frontend o UI)
                'aporte_contribucion_total' => $calc['escalado']['aporte_contribucion'],
                'aporte_circulacion_total'  => $calc['escalado']['aporte_circulacion'],
                'aporte_aseo_total'         => $calc['escalado']['aporte_aseo'],
                'total_vecinos'             => $calc['escalado']['total_vecinos'],
                'cantidad_contribuyentes'   => $calc['cantidad_contribuyentes'],

                // Cálculos adicionales del presupuesto para transparencia y consistencia
                'presupuesto_total_ingresos' => $calc['ingresos']['total'],
                'fcm_total'                 => $calc['ingresos']['fcm'],
                'patentes_total'            => $calc['ingresos']['patentes'],
                'otros_ingresos_total'      => $calc['ingresos']['otros'],
                'presupuesto_total_gastos'  => $calc['gastos']['total'],
                'superavit'                 => $calc['gastos']['superavit'],
            ]);
        } catch (\Exception $e) {
            Log::warning("getResumenContribuyentes - Error: " . $e->getMessage());
            return response()->json([
                'aporte_contribucion_total' => 0,
                'aporte_circulacion_total'  => 0,
                'aporte_aseo_total'         => 0,
                'total_vecinos'             => 0,
                'cantidad_contribuyentes'   => 0,
            ]);
        }
    }

    /**
     * Limpia un valor desencriptado de posibles doble serializaciones.
     */
    private function deserializarSiEsNecesario($valor)
    {
        if (is_string($valor) && (strpos($valor, 's:') === 0 || strpos($valor, 'a:') === 0)) {
            try {
                $unserialized = @unserialize($valor);
                if ($unserialized !== false) {
                    return $unserialized;
                }
            } catch (\Exception $e) {
                // Continuar
            }
        }
        return $valor;
    }

    /**
     * Limpia y sanitiza un valor numérico en formato string (removiendo puntos, comas, etc.) antes de convertirlo a entero.
     */
    private function cleanInt($value)
    {
        return (int) preg_replace('/[^\d-]/', '', $value);
    }
}

