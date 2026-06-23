import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { StatusBadge } from "./StatusBadge";

describe("StatusBadge — control_registro con texto (no solo color, doc 08 §10)", () => {
  it("muestra 'OK'", () => {
    render(<StatusBadge control="OK" />);
    expect(screen.getByText("OK")).toBeTruthy();
  });

  it("muestra 'Revisar' para REVISAR", () => {
    render(<StatusBadge control="REVISAR" />);
    expect(screen.getByText("Revisar")).toBeTruthy();
  });

  it("cae a 'Capturado' ante un valor desconocido", () => {
    render(<StatusBadge control="LO_QUE_SEA" />);
    expect(screen.getByText("Capturado")).toBeTruthy();
  });
});
