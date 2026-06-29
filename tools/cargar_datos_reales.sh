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

echo "==> 4. Importar y conciliar"
( cd "$API" && php spark mel:import )

echo "==> 5. Sembrar usuarios reales (Shield)"
( cd "$API" && php spark db:seed UsuariosRealesSeeder )

echo "==> Listo. Login: admin@demo.test / MelDemo2026!"
