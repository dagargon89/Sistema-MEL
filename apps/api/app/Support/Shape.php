<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Da forma a las filas de BD (todo llega como string) según los tipos del contrato
 * congelado (`types.ts`): castea ids/enteros a number y normaliza nullables. Mantiene
 * la respuesta JSON fiel al mock para que la SPA no distinga origen (Demo-First v2 §4).
 */
final class Shape
{
    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function proceso(array $r): array
    {
        return [
            'id_proceso'                 => self::iReq($r, 'id_proceso'),
            'nombre'                     => self::sReq($r, 'nombre'),
            'tipo_programacion'          => self::sReq($r, 'tipo_programacion'),
            'id_actividad'               => self::sReq($r, 'id_actividad'),
            'fecha_inicio'               => self::s($r, 'fecha_inicio'),
            'fecha_fin'                  => self::s($r, 'fecha_fin'),
            'total_sesiones_programadas' => self::i($r, 'total_sesiones_programadas'),
            'responsable'                => self::s($r, 'responsable'),
            'contacto'                   => self::s($r, 'contacto'),
            'estatus'                    => self::sReq($r, 'estatus'),
            'observaciones'              => self::s($r, 'observaciones'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function evento(array $r): array
    {
        return [
            'id_evento_programado' => self::iReq($r, 'id_evento_programado'),
            'id_actividad'         => self::sReq($r, 'id_actividad'),
            'id_proceso'           => self::i($r, 'id_proceso'),
            'tipo_programacion'    => self::sReq($r, 'tipo_programacion'),
            'fecha_inicio'         => self::sReq($r, 'fecha_inicio'),
            'fecha_finalizacion'   => self::sReq($r, 'fecha_finalizacion'),
            'hora_inicio'          => self::s($r, 'hora_inicio'),
            'hora_finalizacion'    => self::s($r, 'hora_finalizacion'),
            'modalidad'            => self::s($r, 'modalidad'),
            'lugar'                => self::s($r, 'lugar'),
            'calle_y_numero'       => self::s($r, 'calle_y_numero'),
            'colonia'              => self::s($r, 'colonia'),
            'responsable'          => self::s($r, 'responsable'),
            'contacto'             => self::s($r, 'contacto'),
            'estatus'              => self::sReq($r, 'estatus'),
            'num_sesion'           => self::i($r, 'num_sesion'),
            'total_sesiones'       => self::i($r, 'total_sesiones'),
            'observaciones'        => self::s($r, 'observaciones'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function ejecucion(array $r): array
    {
        return [
            'id_ejecucion'                => self::iReq($r, 'id_ejecucion'),
            'id_evento_programado'        => self::iReq($r, 'id_evento_programado'),
            'fecha_ejecucion_real'        => self::s($r, 'fecha_ejecucion_real'),
            'hora_inicio_real'            => self::s($r, 'hora_inicio_real'),
            'hora_finalizacion_real'      => self::s($r, 'hora_finalizacion_real'),
            'lugar_real'                  => self::s($r, 'lugar_real'),
            'colonia_real'                => self::s($r, 'colonia_real'),
            'responsable_real'            => self::s($r, 'responsable_real'),
            'estatus_ejecucion'           => self::s($r, 'estatus_ejecucion'),
            'tipo_registro_participacion' => self::sReq($r, 'tipo_registro_participacion'),
            'total_participantes'         => self::i($r, 'total_participantes'),
            'evidencia_url'               => self::s($r, 'evidencia_url'),
            'nombre_archivo_evidencia'    => self::s($r, 'nombre_archivo_evidencia'),
            'resumen_narrativo'           => self::s($r, 'resumen_narrativo'),
            'control_registro'            => self::sReq($r, 'control_registro'),
            'observaciones'               => self::s($r, 'observaciones'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function participacion(array $r): array
    {
        return [
            'id_participacion'      => self::iReq($r, 'id_participacion'),
            'id_ejecucion'          => self::iReq($r, 'id_ejecucion'),
            'id_persona'            => self::s($r, 'id_persona'),
            'nombres'               => self::sReq($r, 'nombres'),
            'apellido_paterno'      => self::sReq($r, 'apellido_paterno'),
            'apellido_materno'      => self::s($r, 'apellido_materno'),
            'anio_nacimiento'       => self::i($r, 'anio_nacimiento'),
            'sexo'                  => self::sReq($r, 'sexo'),
            'telefono'              => self::sReq($r, 'telefono'),
            'correo'                => self::s($r, 'correo'),
            'colonia_persona'       => self::sReq($r, 'colonia_persona'),
            'id_datosbeneficiario'  => self::sReq($r, 'id_datosbeneficiario'),
            'alerta_duplicado'      => self::sReq($r, 'alerta_duplicado'),
            'fecha_participacion'   => self::s($r, 'fecha_participacion'),
            'control_registro'      => self::sReq($r, 'control_registro'),
            'control_automatico'    => self::s($r, 'control_automatico'),
            'decision_coordinacion' => self::s($r, 'decision_coordinacion'),
            'detalle_validacion'    => self::s($r, 'detalle_validacion'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function persona(array $r): array
    {
        return [
            'id_persona'            => self::sReq($r, 'id_persona'),
            'nombres'               => self::s($r, 'nombres'),
            'apellido_paterno'      => self::s($r, 'apellido_paterno'),
            'apellido_materno'      => self::s($r, 'apellido_materno'),
            'nombre_completo'       => self::s($r, 'nombre_completo'),
            'anio_nacimiento'       => self::i($r, 'anio_nacimiento'),
            'sexo'                  => self::s($r, 'sexo'),
            'telefono'              => self::s($r, 'telefono'),
            'correo'                => self::s($r, 'correo'),
            'colonia'               => self::s($r, 'colonia'),
            'id_datosbeneficiario'  => self::sReq($r, 'id_datosbeneficiario'),
            'primera_participacion' => self::s($r, 'primera_participacion'),
            'total_participaciones' => self::iReq($r, 'total_participaciones'),
            'control_registro'      => self::sReq($r, 'control_registro'),
            'decision_coordinacion' => self::s($r, 'decision_coordinacion'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function agregada(array $r): array
    {
        return [
            'id_participacion_agregada'   => self::iReq($r, 'id_participacion_agregada'),
            'id_ejecucion'                => self::iReq($r, 'id_ejecucion'),
            'tipo_registro_participacion' => self::sReq($r, 'tipo_registro_participacion'),
            'sexo_grupo'                  => self::s($r, 'sexo_grupo'),
            'grupo_edad_aprox'            => self::s($r, 'grupo_edad_aprox'),
            'cantidad_participantes'      => self::iReq($r, 'cantidad_participantes'),
            'motivo_no_nominal'           => self::s($r, 'motivo_no_nominal'),
            'fuente_conteo'               => self::s($r, 'fuente_conteo'),
            'periodo_corte'               => self::s($r, 'periodo_corte'),
            'evidencia_url'               => self::s($r, 'evidencia_url'),
            'control_registro'            => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function meta(array $r): array
    {
        return [
            'id_meta'           => self::iReq($r, 'id_meta'),
            'id_actividad'      => self::sReq($r, 'id_actividad'),
            'unidad_meta'       => self::s($r, 'unidad_meta'),
            'unidad_especifica' => self::s($r, 'unidad_especifica'),
            'meta_anual_total'  => self::f($r, 'meta_anual_total'),
            'observaciones'     => self::s($r, 'observaciones'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function producto(array $r): array
    {
        return [
            'id_producto'              => self::iReq($r, 'id_producto'),
            'id_actividad'             => self::sReq($r, 'id_actividad'),
            'nombre_producto'          => self::sReq($r, 'nombre_producto'),
            'tipo_producto'            => self::s($r, 'tipo_producto'),
            'fecha_inicio'             => self::s($r, 'fecha_inicio'),
            'fecha_entrega'            => self::s($r, 'fecha_entrega'),
            'responsable'              => self::s($r, 'responsable'),
            'cantidad'                 => self::i($r, 'cantidad'),
            'unidad_medida'            => self::s($r, 'unidad_medida'),
            'estatus'                  => self::sReq($r, 'estatus'),
            'descripcion'              => self::s($r, 'descripcion'),
            'evidencia_url'            => self::s($r, 'evidencia_url'),
            'nombre_archivo_evidencia' => self::s($r, 'nombre_archivo_evidencia'),
            'control_registro'         => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function propuestaIncidencia(array $r): array
    {
        return [
            'id_propuesta'                    => self::iReq($r, 'id_propuesta'),
            'nombre_propuesta'                => self::sReq($r, 'nombre_propuesta'),
            'promotor_colectivo'              => self::s($r, 'promotor_colectivo'),
            'tipo_actor'                      => self::s($r, 'tipo_actor'),
            'fecha_inicio_asesoria'           => self::s($r, 'fecha_inicio_asesoria'),
            'responsable_equipo'              => self::s($r, 'responsable_equipo'),
            'sesiones_documentadas'           => self::i($r, 'sesiones_documentadas'),
            'mejora_documentada'              => self::b($r, 'mejora_documentada'),
            'cambios_resultado_asesoria'      => self::s($r, 'cambios_resultado_asesoria'),
            'evidencia_principal'             => self::s($r, 'evidencia_principal'),
            'alineada_proyectos_estrategicos' => self::b($r, 'alineada_proyectos_estrategicos'),
            'criterios_alineacion_nota'       => self::s($r, 'criterios_alineacion_nota'),
            'estatus'                         => self::sReq($r, 'estatus'),
            'elegible_reporte'                => self::b($r, 'elegible_reporte'),
            'id_actividad'                    => self::sReq($r, 'id_actividad'),
            'periodo_reporte'                 => self::s($r, 'periodo_reporte'),
            'control_registro'                => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function procesoIncidencia(array $r): array
    {
        return [
            'id_proceso_incidencia'  => self::iReq($r, 'id_proceso_incidencia'),
            'nombre'                 => self::sReq($r, 'nombre'),
            'criterios_elegibilidad' => self::s($r, 'criterios_elegibilidad'),
            'ultimo_hito_resumen'    => self::s($r, 'ultimo_hito_resumen'),
            'control_registro'       => self::sReq($r, 'control_registro'),
            'id_actividad'           => self::sReq($r, 'id_actividad'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function compromiso(array $r): array
    {
        return [
            'id_compromiso'           => self::iReq($r, 'id_compromiso'),
            'id_proceso_incidencia'   => self::iReq($r, 'id_proceso_incidencia'),
            'identificacion'          => self::s($r, 'identificacion'),
            'seguimiento_documentado' => self::s($r, 'seguimiento_documentado'),
            'criterios_elegibilidad'  => self::s($r, 'criterios_elegibilidad'),
            'control_registro'        => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function alianza(array $r): array
    {
        return [
            'id_alianza'             => self::iReq($r, 'id_alianza'),
            'nombre_alianza'         => self::sReq($r, 'nombre_alianza'),
            'datos_alianza'          => self::s($r, 'datos_alianza'),
            'criterios_elegibilidad' => self::s($r, 'criterios_elegibilidad'),
            'id_actividad'           => self::sReq($r, 'id_actividad'),
            'control_registro'       => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function hito(array $r): array
    {
        return [
            'id_hito'                 => self::iReq($r, 'id_hito'),
            'id_proceso_incidencia'   => self::iReq($r, 'id_proceso_incidencia'),
            'fecha_hito'              => self::s($r, 'fecha_hito'),
            'tipo_hito'               => self::s($r, 'tipo_hito'),
            'descripcion_hito'        => self::s($r, 'descripcion_hito'),
            'evidencia_nombre_o_nota' => self::s($r, 'evidencia_nombre_o_nota'),
            'registrado_por'          => self::i($r, 'registrado_por'),
            'observaciones'           => self::s($r, 'observaciones'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function ocupacion(array $r): array
    {
        $cap = self::iReq($r, 'capacidad_instalada');
        $occ = self::iReq($r, 'ocupacion');

        return [
            'id_ocupacion'        => self::iReq($r, 'id_ocupacion'),
            'id_actividad'        => self::sReq($r, 'id_actividad'),
            'mes_periodo'         => self::sReq($r, 'mes_periodo'),
            'tipo_espacio'        => self::s($r, 'tipo_espacio'),
            'capacidad_instalada' => $cap,
            'ocupacion'           => $occ,
            'pct_ocupacion'       => $cap > 0 ? round($occ / $cap * 1000) / 10 : null,
            'fuente'              => self::s($r, 'fuente'),
            'control_registro'    => self::sReq($r, 'control_registro'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function sostenibilidad(array $r): array
    {
        $ingresos = self::fReq($r, 'ingresos_brutos');
        $cd       = self::fReq($r, 'costos_directos');
        $ci       = self::fReq($r, 'costos_indirectos');
        $efectivo = self::fReq($r, 'recursos_efectivo');
        $especie  = self::fReq($r, 'recursos_especie');
        $meta     = self::fReq($r, 'meta_anual');
        $pct      = $meta > 0 ? round($ingresos / $meta * 1000) / 10 : null;

        return [
            'id_registro'         => self::iReq($r, 'id_registro'),
            'id_actividad'        => self::sReq($r, 'id_actividad'),
            'mes_periodo'         => self::sReq($r, 'mes_periodo'),
            'ingresos_brutos'     => $ingresos,
            'costos_directos'     => $cd,
            'costos_indirectos'   => $ci,
            'recursos_efectivo'   => $efectivo,
            'recursos_especie'    => $especie,
            'fuente_datos'        => self::s($r, 'fuente_datos'),
            'meta_anual'          => $meta,
            'control_registro'    => self::sReq($r, 'control_registro'),
            'utilidad_neta_mes'   => $ingresos - $cd - $ci,
            'recursos_totales_mes' => $efectivo + $especie,
            'pct_avance_anual'    => $pct,
            'semaforo'            => self::semaforoFinanciero($meta, $pct),
        ];
    }

    /** Semáforo de sostenibilidad por % de avance anual (90/75), espejo del seguimiento. */
    private static function semaforoFinanciero(float $meta, ?float $pct): string
    {
        if ($meta <= 0.0 || $pct === null) {
            return 'SIN_META';
        }
        if ($pct >= 90.0) {
            return 'VERDE';
        }
        if ($pct >= 75.0) {
            return 'AMARILLO';
        }

        return 'ROJO';
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function resultado(array $r): array
    {
        return [
            'id_resultado'    => self::iReq($r, 'id_resultado'),
            'id_actividad'    => self::sReq($r, 'id_actividad'),
            'indicador'       => self::sReq($r, 'indicador'),
            'linea_base'      => self::f($r, 'linea_base'),
            'valor_medido'    => self::f($r, 'valor_medido'),
            'metodo_medicion' => self::s($r, 'metodo_medicion'),
            'fecha_medicion'  => self::s($r, 'fecha_medicion'),
            'evidencia_url'   => self::s($r, 'evidencia_url'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function solicitud(array $r): array
    {
        return [
            'id_solicitud'         => self::iReq($r, 'id_solicitud'),
            'fecha_solicitud'      => self::sReq($r, 'fecha_solicitud'),
            'id_solicitante'       => self::iReq($r, 'id_solicitante'),
            'rol_solicitante'      => self::s($r, 'rol_solicitante'),
            'entidad_afectada'     => self::s($r, 'entidad_afectada'),
            'descripcion'          => self::sReq($r, 'descripcion'),
            'tipo_solicitud'       => self::sReq($r, 'tipo_solicitud'),
            'nivel_criticidad'     => self::sReq($r, 'nivel_criticidad'),
            'impacto'              => self::s($r, 'impacto'),
            'estado'               => self::sReq($r, 'estado'),
            'responsable_atencion' => self::i($r, 'responsable_atencion'),
            'fecha_resolucion'     => self::s($r, 'fecha_resolucion'),
            'comentarios'          => self::s($r, 'comentarios'),
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    public static function auditoria(array $r): array
    {
        return [
            'id_evento'     => self::iReq($r, 'id_evento'),
            'fecha_hora'    => self::sReq($r, 'fecha_hora'),
            'id_usuario'    => self::i($r, 'id_usuario'),
            'entidad'       => self::sReq($r, 'entidad'),
            'id_registro'   => self::sReq($r, 'id_registro'),
            'accion'        => self::sReq($r, 'accion'),
            'valor_antes'   => self::j($r, 'valor_antes'),
            'valor_despues' => self::j($r, 'valor_despues'),
        ];
    }

    /**
     * Decodifica una columna JSON a objeto (o null). Usado por la auditoría.
     *
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>|null
     */
    private static function j(array $r, string $key): ?array
    {
        $v = $r[$key] ?? null;
        if (! is_string($v) || $v === '') {
            return null;
        }
        $d = json_decode($v, true);

        /** @var array<string, mixed>|null $out */
        $out = is_array($d) ? $d : null;

        return $out;
    }

    /** @param array<string, mixed> $r */
    private static function b(array $r, string $key): bool
    {
        $v = $r[$key] ?? null;

        return $v === true || $v === 1 || $v === '1';
    }

    /** @param array<string, mixed> $r */
    private static function fReq(array $r, string $key): float
    {
        return self::f($r, $key) ?? 0.0;
    }

    /** @param array<string, mixed> $r */
    private static function f(array $r, string $key): ?float
    {
        $v = $r[$key] ?? null;

        return is_numeric($v) ? (float) $v : null;
    }

    /** @param array<string, mixed> $r */
    private static function i(array $r, string $key): ?int
    {
        $v = $r[$key] ?? null;

        return is_numeric($v) ? (int) $v : null;
    }

    /** @param array<string, mixed> $r */
    private static function iReq(array $r, string $key): int
    {
        return self::i($r, $key) ?? 0;
    }

    /** @param array<string, mixed> $r */
    private static function s(array $r, string $key): ?string
    {
        $v = $r[$key] ?? null;
        if (is_string($v)) {
            return $v === '' ? null : $v;
        }

        return is_numeric($v) ? (string) $v : null;
    }

    /** @param array<string, mixed> $r */
    private static function sReq(array $r, string $key): string
    {
        return self::s($r, $key) ?? '';
    }
}
