#!/usr/bin/env bash
# Carga los datos reales del Excel v1.9 en sistema_mel (cadena MEL core).
# Pre-requisito: Docker mel-db arriba, venv en /tmp/melenv con openpyxl.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
API="$ROOT/apps/api"

echo "==> 1. Recrear sistema_mel vacía"
docker exec mel-db mysql -uroot -proot_secret -e \
  "DROP DATABASE IF EXISTS sistema_mel; CREATE DATABASE sistema_mel CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; \
   GRANT ALL PRIVILEGES ON sistema_mel.* TO 'mel'@'%'; FLUSH PRIVILEGES;"

echo "==> 2. Migrar esquema (App + Shield + Settings)"
( cd "$API" && php spark migrate --all )

echo "==> 3. Extraer Excel -> 10 CSV"
/tmp/melenv/bin/python "$ROOT/tools/excel_to_csv.py" --out "$API/data/excel"

echo "==> 3b. Normalizar y limpiar CSVs (coherencia FK del Excel v1.9)"
# El Excel v1.9 tiene inconsistencias de datos que se resuelven aquí, antes de importar,
# sin modificar el extractor (Tasks 1-4) ni MigracionService.
python3 -c "
import csv, os, re

EXCEL_DIR = os.path.join('$API', 'data', 'excel')

def read_csv(filename):
    path = os.path.join(EXCEL_DIR, filename)
    with open(path, newline='', encoding='utf-8') as f:
        rows = list(csv.DictReader(f))
    return rows

def write_csv(filename, rows):
    path = os.path.join(EXCEL_DIR, filename)
    if not rows:
        return
    with open(path, 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=list(rows[0].keys()))
        w.writeheader()
        w.writerows(rows)

# --- Paso 1: Normalizar id_componente en actividades.csv ---
# dim_componentes usa COMP_NNN, tabla_actividades usa COM_NNN para algunas filas
act_rows = read_csv('actividades.csv')
fixed = 0
for row in act_rows:
    cid = row.get('id_componente', '')
    if re.match(r'^COM_\d+$', cid):
        row['id_componente'] = 'COMP_' + cid[4:]
        fixed += 1
write_csv('actividades.csv', act_rows)
act_ids = {r['id_actividad'] for r in act_rows if r.get('id_actividad')}
print(f'  actividades.csv: {len(act_ids)} filas OK, {fixed} IDs componente normalizados')

# --- Paso 2: Filtrar procesos sin id_actividad (FK procesos_id_actividad_foreign) ---
proc_rows = read_csv('procesos.csv')
proc_kept = [r for r in proc_rows if r.get('id_proceso') and r.get('id_actividad') and r['id_actividad'] in act_ids]
write_csv('procesos.csv', proc_kept)
proc_ids = {r['id_proceso'] for r in proc_kept}
print(f'  procesos.csv: {len(proc_kept)} filas OK, {len(proc_rows)-len(proc_kept)} descartadas')

# --- Paso 3: Filtrar eventos sin id_actividad (FK eventos_programados_id_actividad_foreign) ---
evt_rows = read_csv('eventos.csv')
evt_kept = [r for r in evt_rows if r.get('id_evento_programado') and r.get('id_actividad') and r['id_actividad'] in act_ids]
write_csv('eventos.csv', evt_kept)
evt_ids = {r['id_evento_programado'] for r in evt_kept}
print(f'  eventos.csv: {len(evt_kept)} filas OK, {len(evt_rows)-len(evt_kept)} descartadas')

# --- Paso 4: Filtrar ejecuciones sin id_evento_programado válido ---
ejec_rows = read_csv('ejecuciones.csv')
ejec_kept = [r for r in ejec_rows if r.get('id_ejecucion') and r.get('id_evento_programado') and r['id_evento_programado'] in evt_ids]
write_csv('ejecuciones.csv', ejec_kept)
ejec_ids = {r['id_ejecucion'] for r in ejec_kept}
print(f'  ejecuciones.csv: {len(ejec_kept)} filas OK, {len(ejec_rows)-len(ejec_kept)} descartadas')

# --- Paso 5: Filtrar participaciones y agregadas con ejecucion inválida ---
part_rows = read_csv('participaciones.csv')
part_kept = [r for r in part_rows if r.get('id_ejecucion') and r['id_ejecucion'] in ejec_ids]
write_csv('participaciones.csv', part_kept)
print(f'  participaciones.csv: {len(part_kept)} filas OK, {len(part_rows)-len(part_kept)} descartadas')

agg_rows = read_csv('agregadas.csv')
agg_kept = [r for r in agg_rows if r.get('id_ejecucion') and r['id_ejecucion'] in ejec_ids]
write_csv('agregadas.csv', agg_kept)
print(f'  agregadas.csv: {len(agg_kept)} filas OK, {len(agg_rows)-len(agg_kept)} descartadas')
"

echo "==> 4. Importar y conciliar"
( cd "$API" && php spark mel:import )

echo "==> 5. Sembrar usuarios reales (Shield)"
( cd "$API" && php spark db:seed UsuariosRealesSeeder )

echo "==> Listo. Login: admin@demo.test / MelDemo2026!"
