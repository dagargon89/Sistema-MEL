import { describe, it, expect } from "vitest";
import { norm, coincide, paginar } from "./query";

describe("norm — normalización acento/caja-insensible (espejo de la dedup, QA2)", () => {
  it("iguala 'José Pérez' y 'JOSE PEREZ'", () => {
    expect(norm("José Pérez")).toBe(norm("JOSE PEREZ"));
  });
  it("recorta espacios y pasa a mayúsculas", () => {
    expect(norm("  María  ")).toBe("MARIA");
  });
  it("devuelve cadena vacía para null/undefined", () => {
    expect(norm(null)).toBe("");
    expect(norm(undefined)).toBe("");
  });
});

describe("coincide — búsqueda parcial insensible", () => {
  it("encuentra ignorando acentos y caja", () => {
    expect(coincide("Centro Comunitario Norte", "comunitario")).toBe(true);
  });
  it("no encuentra lo ausente", () => {
    expect(coincide("Centro", "xyz")).toBe(false);
  });
});

describe("paginar — meta del doc 05 §1.6", () => {
  it("calcula página, total y total_pages", () => {
    const rows = Array.from({ length: 23 }, (_, i) => i);
    const { data, meta } = paginar(rows, 2, 10);
    expect(data).toHaveLength(10);
    expect(meta).toMatchObject({ page: 2, per_page: 10, total: 23, total_pages: 3 });
  });
});
