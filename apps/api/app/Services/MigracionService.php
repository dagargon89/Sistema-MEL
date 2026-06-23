<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Migración y conciliación del Excel v1.9 (Sprint 4, doc 06 §4). Lee los CSV exportados,
 * limpia `#REF!`, descarta filas-plantilla, carga en orden de dependencias y **regenera
 * `personas` por deduplicación** (nunca las copia del Excel congelado, ADR-003). Devuelve
 * un reporte de conciliación para contrastar con la línea base (≈988/762/279/132).
 *
 * El comando `spark mel:import` es una capa delgada sobre este servicio.
 */
class MigracionService
{
    private DeduplicacionService $dedup;
    private int $refsLimpiados        = 0;
    private int $plantillasDescartadas = 0;

    public function __construct()
    {
        $this->dedup = new DeduplicacionService();
    }

    /**
     * Importa todos los CSV de `$dir` (los ausentes se omiten) en una sola transacción.
     *
     * @return array<string, mixed> reporte de conciliación
     */
    public function importar(string $dir): array
    {
        $db = db_connect();
        $db->transStart();

        // 1) Dimensiones y catálogo (orden de FK).
        $this->cargar($dir, 'ejes.csv', 'ejes', 'id_eje', ['id_eje', 'num_eje_original', 'clave_eje_corto', 'nombre', 'orden_visualizacion']);
        $this->cargar($dir, 'instituciones.csv', 'instituciones', 'id_institucion', ['id_institucion', 'num_institucion_original', 'nombre', 'estatus', 'orden_visualizacion']);
        $this->cargar($dir, 'lineas.csv', 'lineas', 'id_linea', ['id_linea', 'num_linea', 'clave_linea_corta', 'nombre', 'id_eje', 'orden_visualizacion', 'estatus']);
        $this->cargar($dir, 'componentes.csv', 'componentes', 'id_componente', ['id_componente', 'num_componente', 'clave_componente', 'nombre', 'id_institucion', 'orden_visualizacion', 'estatus']);
        $actividades = $this->cargarActividades($dir);

        // 2) Cadena MEL.
        $procesos = $this->cargar($dir, 'procesos.csv', 'procesos', 'id_proceso', ['id_proceso', 'nombre', 'tipo_programacion', 'id_actividad', 'fecha_inicio', 'fecha_fin', 'total_sesiones_programadas', 'responsable', 'contacto', 'estatus', 'observaciones']);
        $eventos  = $this->cargar($dir, 'eventos.csv', 'eventos_programados', 'id_evento_programado', ['id_evento_programado', 'id_actividad', 'id_proceso', 'tipo_programacion', 'fecha_inicio', 'fecha_finalizacion', 'hora_inicio', 'hora_finalizacion', 'modalidad', 'lugar', 'calle_y_numero', 'colonia', 'responsable', 'contacto', 'estatus', 'num_sesion', 'total_sesiones', 'observaciones']);
        $ejecuciones = $this->cargarEjecuciones($dir);
        $participaciones = $this->cargarParticipaciones($dir); // regenera personas (dedup)
        $agregadas = $this->cargarAgregadas($dir);

        $db->transComplete();

        return [
            'actividades'            => $actividades,
            'procesos'               => $procesos,
            'eventos_programados'    => $eventos,
            'ejecuciones'            => $ejecuciones,
            'participaciones'        => $participaciones['participaciones'],
            'personas'               => $participaciones['personas'],
            'cola_revisar'           => $participaciones['cola'],
            'agregadas_filas'        => $agregadas['filas'],
            'agregadas_suma'         => $agregadas['suma'],
            'cobertura_total'        => $participaciones['participaciones'] + $agregadas['suma'],
            'refs_limpiados'         => $this->refsLimpiados,
            'plantillas_descartadas' => $this->plantillasDescartadas,
        ];
    }

    /**
     * Carga genérica: filtra filas-plantilla (clave vacía) e inserta solo columnas no nulas
     * (deja que la BD aplique sus defaults). Devuelve el número de filas cargadas.
     *
     * @param list<string> $cols
     */
    private function cargar(string $dir, string $archivo, string $tabla, string $clave, array $cols): int
    {
        $n = 0;
        foreach ($this->leerCsv($dir . '/' . $archivo) as $row) {
            if (($row[$clave] ?? null) === null) {
                $this->plantillasDescartadas++;
                continue;
            }
            $this->insertarFila($tabla, $row, $cols);
            $n++;
        }

        return $n;
    }

    /** @return array{total:int, P:int, E:int, R:int} */
    private function cargarActividades(string $dir): array
    {
        $cols = ['id_actividad', 'num_actividad', 'nombre', 'id_eje', 'id_linea', 'id_componente', 'id_institucion', 'tipo_registro', 'caso_excepcional'];
        $p    = 0;
        $e    = 0;
        $r    = 0;
        foreach ($this->leerCsv($dir . '/actividades.csv') as $row) {
            $tipo = $row['tipo_registro'] ?? null;
            if (($row['id_actividad'] ?? null) === null || $tipo === null) {
                $this->plantillasDescartadas++;
                continue;
            }
            $this->insertarFila('actividades', $row, $cols);
            if ($tipo === 'P') {
                $p++;
            } elseif ($tipo === 'E') {
                $e++;
            } elseif ($tipo === 'R') {
                $r++;
            }
        }

        return ['total' => $p + $e + $r, 'P' => $p, 'E' => $e, 'R' => $r];
    }

    /** @return array{total:int, con_fecha:int} */
    private function cargarEjecuciones(string $dir): array
    {
        $cols = ['id_ejecucion', 'id_evento_programado', 'fecha_ejecucion_real', 'hora_inicio_real', 'hora_finalizacion_real', 'lugar_real', 'colonia_real', 'responsable_real', 'estatus_ejecucion', 'tipo_registro_participacion', 'total_participantes', 'evidencia_url', 'nombre_archivo_evidencia', 'resumen_narrativo', 'control_registro', 'observaciones'];
        $total    = 0;
        $conFecha = 0;
        foreach ($this->leerCsv($dir . '/ejecuciones.csv') as $row) {
            if (($row['id_ejecucion'] ?? null) === null) {
                $this->plantillasDescartadas++;
                continue;
            }
            $this->insertarFila('ejecuciones', $row, $cols);
            $total++;
            if (($row['fecha_ejecucion_real'] ?? null) !== null) {
                $conFecha++;
            }
        }

        return ['total' => $total, 'con_fecha' => $conFecha];
    }

    /**
     * Carga participaciones regenerando `personas` por deduplicación (el núcleo compartido
     * con la captura nominal). Los sospechosos por teléfono entran a la cola (control=REVISAR).
     *
     * @return array{participaciones:int, personas:int, cola:int}
     */
    private function cargarParticipaciones(string $dir): array
    {
        $n    = 0;
        $cola = 0;
        foreach ($this->leerCsv($dir . '/participaciones.csv') as $row) {
            $idEjec  = is_numeric($row['id_ejecucion'] ?? null) ? (int) $row['id_ejecucion'] : null;
            $nombres = $row['nombres'] ?? null;
            $apPat   = $row['apellido_paterno'] ?? null;
            if ($idEjec === null || $nombres === null || $apPat === null) {
                $this->plantillasDescartadas++;
                continue;
            }

            $datos = [
                'nombres'             => $nombres,
                'apellido_paterno'    => $apPat,
                'apellido_materno'    => $row['apellido_materno'] ?? null,
                'anio_nacimiento'     => is_numeric($row['anio_nacimiento'] ?? null) ? (int) $row['anio_nacimiento'] : null,
                'sexo'                => $row['sexo'] ?? '',
                'telefono'            => $row['telefono'] ?? '',
                'correo'              => $row['correo'] ?? null,
                'colonia'             => $row['colonia_persona'] ?? null,
                'fecha_participacion' => $row['fecha_participacion'] ?? null,
            ];
            $res         = $this->dedup->resolverPersona($datos);
            $controlAuto = $res['control'] === 'REVISAR' ? 'REVISAR' : 'OK';

            db_connect()->table('participaciones')->insert([
                'id_ejecucion'          => $idEjec,
                'id_persona'            => $res['id_persona'],
                'nombres'               => $nombres,
                'apellido_paterno'      => $apPat,
                'apellido_materno'      => $datos['apellido_materno'],
                'anio_nacimiento'       => $datos['anio_nacimiento'],
                'sexo'                  => $datos['sexo'],
                'telefono'              => $datos['telefono'],
                'correo'                => $datos['correo'],
                'colonia_persona'       => $row['colonia_persona'] ?? '',
                'id_datosbeneficiario'  => $res['clave'],
                'alerta_duplicado'      => $res['alerta'],
                'fecha_participacion'   => $datos['fecha_participacion'],
                'control_registro'      => $res['control'],
                'control_automatico'    => $controlAuto,
                'decision_coordinacion' => null,
                'detalle_validacion'    => $res['detalle'],
            ]);
            $n++;
            if ($res['control'] === 'REVISAR') {
                $cola++;
            }
        }

        $personas = (int) db_connect()->table('personas')->countAllResults();

        return ['participaciones' => $n, 'personas' => $personas, 'cola' => $cola];
    }

    /** @return array{filas:int, suma:int} */
    private function cargarAgregadas(string $dir): array
    {
        $cols  = ['id_ejecucion', 'tipo_registro_participacion', 'sexo_grupo', 'grupo_edad_aprox', 'cantidad_participantes', 'motivo_no_nominal', 'fuente_conteo', 'periodo_corte', 'evidencia_url', 'control_registro'];
        $filas = 0;
        $suma  = 0;
        foreach ($this->leerCsv($dir . '/agregadas.csv') as $row) {
            if (($row['id_ejecucion'] ?? null) === null) {
                $this->plantillasDescartadas++;
                continue;
            }
            $this->insertarFila('participaciones_agregadas', $row, $cols);
            $filas++;
            $suma += is_numeric($row['cantidad_participantes'] ?? null) ? (int) $row['cantidad_participantes'] : 0;
        }

        return ['filas' => $filas, 'suma' => $suma];
    }

    /**
     * Inserta una fila tomando solo las columnas indicadas y no nulas (los nulos dejan que
     * la BD aplique su default y evitan violar NOT NULL en columnas con default).
     *
     * @param array<string, string|null> $row
     * @param list<string>                $cols
     */
    private function insertarFila(string $tabla, array $row, array $cols): void
    {
        $data = [];
        foreach ($cols as $c) {
            $v = $row[$c] ?? null;
            if ($v !== null) {
                $data[$c] = $v;
            }
        }
        if ($data !== []) {
            db_connect()->table($tabla)->insert($data);
        }
    }

    /**
     * Lee un CSV con cabecera; limpia cada celda (`#REF!`/errores → null, vacío → null) y
     * omite líneas totalmente vacías. Si el archivo no existe, devuelve [].
     *
     * @return list<array<string, string|null>>
     */
    private function leerCsv(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [];
        }
        $cols = array_map(static fn ($c): string => is_string($c) ? trim($c) : '', $header);

        $out = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row     = [];
            $vacios  = 0;
            foreach ($cols as $i => $name) {
                $valor      = $this->limpiar($data[$i] ?? null);
                $row[$name] = $valor;
                if ($valor === null) {
                    $vacios++;
                }
            }
            if ($vacios === count($cols)) {
                continue; // línea totalmente vacía: no es plantilla, se ignora
            }
            $out[] = $row;
        }
        fclose($handle);

        return $out;
    }

    /** Limpia una celda: errores de Excel (`#REF!`, `#N/A`, `#VALUE!`) y vacíos → null. */
    private function limpiar(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim($v);
        if ($t === '') {
            return null;
        }
        if (stripos($t, '#REF!') !== false || $t === '#N/A' || $t === '#VALUE!' || $t === '#¡REF!') {
            $this->refsLimpiados++;

            return null;
        }

        return $t;
    }
}
