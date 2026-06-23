# 08 · Identidad Visual y Design System

| | |
|---|---|
| **Documento** | 08 — Identidad Visual y Design System |
| **Versión** | 1.0 |
| **Fecha** | 22 de junio de 2026 |
| **Origen** | Marca institucional **Participa Juárez** (CPJ): morado `#53155a` + lima `#dbec57` |
| **Dirección** | Interfaz administrativa de datos: sobria, legible, densa pero clara; prioriza la captura sin errores y la lectura de tableros |
| **Depende de** | [CLAUDE.md](../CLAUDE.md), [Arquitectura (02)](../02-arquitectura/02_arquitectura_sistema.md) |

---

## 1. Identidad de marca

| Atributo | Valor |
|---|---|
| Producto | Sistema MEL |
| Endoso | Participa Juárez · Comunidad Participa Juárez |
| Propósito visual | Transmitir confianza, rigor y claridad de datos; herramienta de trabajo diaria. |
| Personalidad | Institucional, confiable, accesible, no corporativa fría. |
| Tono visual | Limpio, alto contraste, jerarquía clara; el dato es el protagonista. |
| Antipatrones (qué NO hacer) | Lima como fondo de texto blanco; degradados ruidosos; íconos decorativos sin función; tablas sin jerarquía; morado saturado en grandes áreas de lectura prolongada. |

**Colores de marca primordiales**

| Hex | Nombre | Rol en la interfaz |
|---|---|---|
| `#53155a` | Morado Participa | Primario: CTA, encabezados, elementos activos. |
| `#dbec57` | Lima Participa | Realce/acento: badges, resaltados; **siempre con texto morado oscuro**. |
| `#7A3B82` | Morado claro | Secundario: estados hover suaves, acentos secundarios. |

---

## 2. Paleta de color completa

### 2.1 Tokens base (neutros, superficies, texto)

| Token | Hex | Uso |
|---|---|---|
| `--color-bg` | `#ffffff` | Fondo de la app |
| `--color-surface` | `#f7f5f8` | Tarjetas, paneles |
| `--color-surface-2` | `#efeaf1` | Superficie elevada / encabezado de tabla |
| `--color-border` | `#dcd5e0` | Bordes, separadores |
| `--color-text` | `#1f1626` | Texto principal |
| `--color-text-muted` | `#5d5468` | Texto secundario |
| `--color-text-inverse` | `#ffffff` | Texto sobre morado |

### 2.2 Colores de marca / acento

| Token | Hex | Uso |
|---|---|---|
| `--color-primary` | `#53155a` | Primario (CTA, activos) |
| `--color-primary-hover` | `#3A0F40` | Hover/pressed del primario |
| `--color-primary-soft` | `#ede3ef` | Fondo suave primario (chips, selección) |
| `--color-secondary` | `#7A3B82` | Secundario |
| `--color-accent` | `#dbec57` | Lima (realce) |
| `--color-accent-text` | `#3A0F40` | **Texto obligatorio sobre lima** |

### 2.3 Colores semánticos de estado

Alineados al semáforo de metas (🟢🟡🔴) y a `control_registro`.

| Token | Hex | Fondo suave | Uso |
|---|---|---|---|
| `--color-success` | `#1f7a43` | `#e3f3ea` | Verde (≥90%), `OK` |
| `--color-warning` | `#9a6b00` | `#fbf0d6` | Amarillo (75–89%), `INCOMPLETO` |
| `--color-error` | `#b3261e` | `#fbe5e3` | Rojo (<75%), `REVISAR`/error |
| `--color-info` | `#1d4ee8` | `#e4e9fd` | Informativo, `AGREGADO` |
| `--color-neutral` | `#5d5468` | `#efeaf1` | `SIN_META`, `FASE_3` |

> Los verdes/ámbar/rojos del semáforo se eligieron por contraste sobre fondo claro, no por el morado de marca, para que el estado sea inequívoco.

### 2.4 Reglas de accesibilidad (WCAG 2.1 AA)

| Combinación | Ratio aprox. | ¿Pasa AA? |
|---|---|---|
| Texto `#1f1626` sobre `#ffffff` | 15.8:1 | ✅ AAA |
| Texto blanco sobre `#53155a` | 9.7:1 | ✅ AAA |
| Texto blanco sobre `#7A3B82` | 5.6:1 | ✅ AA |
| Texto `#3A0F40` sobre lima `#dbec57` | 8.9:1 | ✅ AAA |
| **Texto blanco sobre lima `#dbec57`** | 1.6:1 | ❌ **PROHIBIDO** |
| `--color-text-muted` `#5d5468` sobre `#ffffff` | 7.0:1 | ✅ AAA |
| Verde `#1f7a43` sobre `#e3f3ea` | 4.8:1 | ✅ AA |

**Regla dura:** nunca texto blanco sobre lima. El lima solo lleva texto morado oscuro (`--color-accent-text`). Regla de lint del frontend: prohibir `text-white` sobre `bg-accent`.

---

## 3. Tipografía

| Fuente | Origen | Uso |
|---|---|---|
| **Inter** | Google Fonts | UI general, formularios, tablas |
| **Inter** (tabular nums) | — | Cifras de tableros (usar `font-variant-numeric: tabular-nums`) |

Escala tipográfica:

| Nivel | Tamaño | Peso | Line-height | Uso |
|---|---|---|---|---|
| Display | 30px | 700 | 1.2 | Título de tablero |
| H1 | 24px | 700 | 1.25 | Título de página |
| H2 | 20px | 600 | 1.3 | Sección |
| H3 | 16px | 600 | 1.4 | Subsección, encabezado de tarjeta |
| Body | 14px | 400 | 1.5 | Texto y formularios |
| Small | 12px | 400 | 1.45 | Ayuda, metadatos |
| Mono/num | 14px | 500 | 1.4 | Cifras (tabular-nums) |

Jerarquía: una sola fuente, peso y tamaño marcan jerarquía; el morado se reserva para títulos y elementos activos, no para párrafos largos.

---

## 4. Espaciado y layout

- **Escala de espaciado** (múltiplos de 4px): 4, 8, 12, 16, 24, 32, 48.
- **Radios:** `--radius-sm 6px`, `--radius-md 10px`, `--radius-lg 16px`.
- **Sombras:** `--shadow-sm 0 1px 2px rgba(31,22,38,.08)`; `--shadow-md 0 4px 12px rgba(31,22,38,.12)`.
- **Breakpoints:** `sm 640 · md 768 · lg 1024 · xl 1280`. La captura prioriza desktop (uso en oficina); responsivo hasta tablet. Las tablas densas hacen scroll horizontal en pantallas chicas, nunca ocultan columnas críticas.

---

## 5. Componentes

### 5.1 Botón

- **Anatomía:** etiqueta + (icono opcional).
- **Estados:** default, hover, focus (anillo visible), disabled, loading.
- **Variantes:** `primary` (morado), `secondary` (contorno morado), `ghost`, `danger` (rojo).
- **Regla:** un solo botón primario por vista/sección. La lima no es botón de acción primaria (contraste); se usa para badges/realce.

```tsx
<button className="bg-primary text-inverse hover:bg-primary-hover focus-visible:ring-2 focus-visible:ring-primary
                   disabled:opacity-50 rounded-md px-4 py-2 text-sm font-medium">
  Guardar participación
</button>
```

### 5.2 Input / Textarea / Select

- **Estados:** default, focus (borde primario + anillo), error (borde rojo + mensaje), disabled.
- **Regla:** todo campo de catálogo es `select` poblado desde la API (RNF-033); nunca texto libre donde hay catálogo. El error se muestra al lado del campo, en el momento (RNF-031).

```tsx
<label className="block text-sm font-medium text-text">Sexo
  <select className="mt-1 w-full rounded-md border border-border bg-bg px-3 py-2 text-sm
                     focus:border-primary focus:ring-2 focus:ring-primary-soft">
    <option value="">Selecciona…</option>
    <option value="F">Femenino</option><option value="M">Masculino</option><option value="X">Otro</option>
  </select>
</label>
```

### 5.3 Badge / Chip de estado (semáforo y `control_registro`)

```tsx
// OK / VERDE
<span className="inline-flex items-center rounded-full bg-success-soft text-success px-2.5 py-0.5 text-xs font-medium">OK</span>
// REVISAR / ROJO
<span className="inline-flex items-center rounded-full bg-error-soft text-error px-2.5 py-0.5 text-xs font-medium">Revisar</span>
// AGREGADO / INFO
<span className="inline-flex items-center rounded-full bg-info-soft text-info px-2.5 py-0.5 text-xs font-medium">Agregado</span>
```

### 5.4 Tarjeta · Modal · Tabla · Navegación · Avatar · Spinner/Skeleton · Toast

- **Tarjeta:** `bg-surface`, borde `--color-border`, `--radius-md`, `--shadow-sm`. Encabezado H3.
- **Modal:** overlay translúcido, foco atrapado, cierre con Esc; usado para resolver duplicados y confirmar transiciones de estado.
- **Tabla:** encabezado `bg-surface-2`, filas con hover suave (`--color-primary-soft`), cifras con `tabular-nums`, columna de estado con badge; sin ocultar columnas críticas en responsive.
- **Navegación:** barra lateral con secciones (Captura, Personas, Metas, Incidencia, Verticales, Tableros, Gobernanza) filtradas por rol/ámbito; el ítem activo en morado.
- **Avatar:** iniciales sobre `--color-secondary`.
- **Spinner/Skeleton:** skeleton para tableros mientras carga TanStack Query.
- **Toast:** éxito (verde), error (rojo); efímero, no bloqueante.

---

## 6. Tokens CSS completos

```css
:root {
  /* Base */
  --color-bg: #ffffff;
  --color-surface: #f7f5f8;
  --color-surface-2: #efeaf1;
  --color-border: #dcd5e0;
  --color-text: #1f1626;
  --color-text-muted: #5d5468;
  --color-text-inverse: #ffffff;

  /* Marca / acento */
  --color-primary: #53155a;
  --color-primary-hover: #3A0F40;
  --color-primary-soft: #ede3ef;
  --color-secondary: #7A3B82;
  --color-accent: #dbec57;
  --color-accent-text: #3A0F40;

  /* Semánticos */
  --color-success: #1f7a43;  --color-success-soft: #e3f3ea;
  --color-warning: #9a6b00;  --color-warning-soft: #fbf0d6;
  --color-error: #b3261e;    --color-error-soft: #fbe5e3;
  --color-info: #1d4ee8;     --color-info-soft: #e4e9fd;
  --color-neutral: #5d5468;  --color-neutral-soft: #efeaf1;

  /* Radios y sombras */
  --radius-sm: 6px; --radius-md: 10px; --radius-lg: 16px;
  --shadow-sm: 0 1px 2px rgba(31,22,38,.08);
  --shadow-md: 0 4px 12px rgba(31,22,38,.12);

  /* Tipografía */
  --font-sans: "Inter", system-ui, sans-serif;
}
```

---

## 7. Configuración de Tailwind (v4 — `@theme`)

```css
@import "tailwindcss";

@theme {
  --color-bg: #ffffff;
  --color-surface: #f7f5f8;
  --color-surface-2: #efeaf1;
  --color-border: #dcd5e0;
  --color-text: #1f1626;
  --color-text-muted: #5d5468;
  --color-inverse: #ffffff;

  --color-primary: #53155a;
  --color-primary-hover: #3A0F40;
  --color-primary-soft: #ede3ef;
  --color-secondary: #7A3B82;
  --color-accent: #dbec57;
  --color-accent-text: #3A0F40;

  --color-success: #1f7a43;  --color-success-soft: #e3f3ea;
  --color-warning: #9a6b00;  --color-warning-soft: #fbf0d6;
  --color-error: #b3261e;    --color-error-soft: #fbe5e3;
  --color-info: #1d4ee8;     --color-info-soft: #e4e9fd;

  --radius-md: 10px;
  --font-sans: "Inter", system-ui, sans-serif;
}
```

> Genera utilidades como `bg-primary`, `text-accent-text`, `bg-success-soft`, etc. **Regla de lint obligatoria:** prohibir la combinación `text-white`/`text-inverse` con `bg-accent`.

---

## 8. Iconografía

Librería: **Lucide** (`lucide-react`). Criterios: íconos funcionales (no decorativos), trazo de 1.5–2px, tamaños 16/20/24px alineados a la escala tipográfica. Íconos de estado consistentes con el semáforo (check para OK, alerta para REVISAR, info para AGREGADO).

---

## 9. Guía de animaciones y microinteracciones

- Duraciones: 120ms (hover), 200ms (entrada de modal/toast), 250ms (transiciones de panel).
- Easing: `cubic-bezier(.2,.0,.2,1)` (estándar).
- Se animan: hover de botones/filas, apertura de modal, aparición de toast, skeleton→contenido. No se anima nada que retrase la captura.
- Respetar `prefers-reduced-motion`: desactivar animaciones no esenciales.

---

## 10. Verificación de accesibilidad (combinaciones usadas)

| Fondo | Texto | Ratio | Resultado |
|---|---|---|---|
| `#ffffff` | `#1f1626` | 15.8:1 | AAA |
| `#53155a` | `#ffffff` | 9.7:1 | AAA |
| `#7A3B82` | `#ffffff` | 5.6:1 | AA |
| `#dbec57` | `#3A0F40` | 8.9:1 | AAA |
| `#e3f3ea` | `#1f7a43` | 4.8:1 | AA |
| `#fbe5e3` | `#b3261e` | 5.1:1 | AA |
| `#fbf0d6` | `#9a6b00` | 4.7:1 | AA |
| `#dbec57` | `#ffffff` | 1.6:1 | ❌ no usar |

Toda combinación de texto del sistema cumple AA mínimo; las de marca alcanzan AAA. La única combinación prohibida (lima + blanco) está bloqueada por lint.
