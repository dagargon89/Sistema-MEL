"""Definición declarativa de las 10 hojas core: hoja Excel → CSV destino.

Cada entrada: nombre de hoja, archivo CSV, y `cols` como lista de tuplas
(columna_excel, columna_csv, transform). `transform` es el nombre de una
función de normalize aplicada a la celda ('' = limpiar_celda por defecto).
"""

# Mapas de enum del Excel → valores del esquema.
ESTATUS_DIM = ({"activo": "activo", "inactivo": "inactivo"}, ["activo", "inactivo"])

# Mapas de enum de la cadena.
TIPO_PROG = ({}, ["SESION_UNICA", "MULTI_SESION_PROGRAMADA", "PROCESO_CONTINUO"])
ESTATUS_PROC = ({}, ["activo", "concluido", "cancelado"])
ESTATUS_EVENTO = ({}, ["programado", "ejecutado", "cancelado", "reprogramado"])

# procesos y eventos llevan id propio (string→int) y FK a remapear.
PROCESOS = {
    "hoja": "🧭cat_procesos_programados",
    "clave_excel": "id_proceso",          # PROC_xxxxx → entero
    "cols": [
        ("nombre_proceso / grupo", "nombre", ""),
        ("tipo_actividad_calendario", "tipo_programacion", "tipo_prog"),
        ("id_actividad", "id_actividad", ""),
        ("fecha_inicio_proceso", "fecha_inicio", "solo_fecha"),
        ("fecha_fin_proceso", "fecha_fin", "solo_fecha"),
        ("total_sesiones_programadas", "total_sesiones_programadas", "a_entero"),
        ("responsable_actividad", "responsable", ""),
        ("contacto", "contacto", ""),
        ("estatus_proceso", "estatus", "estatus_proc"),
        ("observaciones", "observaciones", ""),
    ],
}

EVENTOS = {
    "hoja": "📅calendario_programacion",
    "clave_excel": "id_evento_programado",   # EVP_xxxxx → entero
    "fk_proceso_excel": "id_proceso",         # PROC_xxxxx → mapa_procesos
    "cols": [
        ("id_actividad", "id_actividad", ""),
        ("tipo_actividad_calendario", "tipo_programacion", "tipo_prog"),
        ("fecha_inicio", "fecha_inicio", "solo_fecha"),
        ("fecha_finalizacion", "fecha_finalizacion", "solo_fecha"),
        ("hora_inicio", "hora_inicio", ""),
        ("hora_finalizacion", "hora_finalizacion", ""),
        ("modalidad", "modalidad", ""),
        ("lugar_actividad", "lugar", ""),
        ("calle_y_numero", "calle_y_numero", ""),
        ("colonia", "colonia", ""),
        ("responsable_actividad", "responsable", ""),
        ("contacto", "contacto", ""),
        ("estatus", "estatus", "estatus_evento"),
        ("num_sesion", "num_sesion", "a_entero"),
        ("total_sesiones", "total_sesiones", "a_entero"),
        ("observaciones", "observaciones", ""),
    ],
}

DIMENSIONES = {
    "ejes": {
        "hoja": "🔵dim_ejes",
        "clave": "id_eje",
        "cols": [
            ("id_eje", "id_eje", ""),
            ("Num_eje_estrategico_original", "num_eje_original", "a_entero"),
            ("clave_eje_corto", "clave_eje_corto", ""),
            ("nom_eje_estrategico", "nombre", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
        ],
    },
    "instituciones": {
        "hoja": "🟢dim_instituciones",
        "clave": "id_institucion",
        "cols": [
            ("id_institucion", "id_institucion", ""),
            ("Num_institucion_original", "num_institucion_original", "a_entero"),
            ("nom_institucion", "nombre", ""),
            ("estatus_institucion", "estatus", "estatus_dim"),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
        ],
    },
    "lineas": {
        "hoja": "🟣dim_lineas",
        "clave": "id_linea",
        "cols": [
            ("id_linea", "id_linea", ""),
            ("Num_linea_de_accion", "num_linea", "a_entero"),
            ("clave_linea_corta", "clave_linea_corta", ""),
            ("nom_linea", "nombre", ""),
            ("id_eje", "id_eje", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
            ("estatus_linea", "estatus", "estatus_dim"),
        ],
    },
    "componentes": {
        "hoja": "🟡dim_componentes",
        "clave": "id_componente",
        "cols": [
            ("id_componente", "id_componente", ""),
            ("Num_componente", "num_componente", "a_entero"),
            ("clave_componente", "clave_componente", ""),
            ("nom_componente", "nombre", ""),
            ("id_institucion", "id_institucion", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
            ("estatus_componente", "estatus", "estatus_dim"),
        ],
    },
    "actividades": {
        "hoja": "🟠tabla_actividades",
        "clave": "id_actividad",
        "cols": [
            ("id_actividad", "id_actividad", ""),
            ("num_actividad", "num_actividad", "a_entero"),
            ("nom_actividad", "nombre", ""),
            ("id_eje", "id_eje", ""),
            ("id_linea", "id_linea", ""),
            ("id_componente", "id_componente", ""),
            ("id_institucion", "id_institucion", ""),
            ("tipo_registro_actividad", "tipo_registro", "tipo_per"),
            ("caso_excepcional", "caso_excepcional", ""),
        ],
    },
}
