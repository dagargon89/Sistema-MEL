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
