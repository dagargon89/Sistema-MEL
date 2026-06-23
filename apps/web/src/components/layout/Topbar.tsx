import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQueryClient } from "@tanstack/react-query";
import { Menu, FlaskConical, LogOut, ChevronDown } from "lucide-react";
import { useSession } from "@/store/session";
import { USING_MOCK } from "@/lib";
import { RoleBadge } from "@/components/ui/RoleBadge";
import type { RolClave } from "@/lib";

// Cuentas demo (db.json). El selector re-invoca login() para cambiar rol/ámbito (solo demo).
const DEMO_USERS: { email: string; label: string; rol: RolClave }[] = [
  { email: "capturista@demo.test", label: "Ana López", rol: "capturista" },
  { email: "coordinacion@demo.test", label: "Carlos Mendoza", rol: "coordinacion" },
  { email: "direccion@demo.test", label: "María F. Ríos", rol: "direccion" },
  { email: "admin@demo.test", label: "Admin Sistema", rol: "administrador" },
];

export function Topbar({ onOpenMenu }: { onOpenMenu: () => void }) {
  const { user, ambito, login, logout } = useSession();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [switching, setSwitching] = useState(false);

  async function cambiarUsuario(email: string) {
    if (!email) return;
    setSwitching(true);
    try {
      await login(email, "demo");
      qc.clear(); // refresca todas las consultas con el nuevo ámbito
      navigate("/");
    } finally {
      setSwitching(false);
    }
  }

  async function salir() {
    await logout();
    qc.clear();
    navigate("/login");
  }

  return (
    <header className="flex items-center gap-3 border-b border-border bg-bg px-4 py-2.5">
      <button
        onClick={onOpenMenu}
        aria-label="Abrir menú"
        className="rounded-md p-1.5 text-text hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary lg:hidden"
      >
        <Menu className="size-5" aria-hidden="true" />
      </button>

      {USING_MOCK && (
        <span
          className="inline-flex items-center gap-1.5 rounded-full bg-accent px-2.5 py-1 text-xs font-semibold text-accent-text"
          title="Los datos son simulados y no se persisten (Demo-First v2)"
        >
          <FlaskConical className="size-3.5" aria-hidden="true" />
          Datos simulados
        </span>
      )}

      <div className="ml-auto flex items-center gap-3">
        {/* Selector de rol/ámbito — solo demo */}
        <label className="hidden items-center gap-1.5 text-xs text-text-muted sm:flex">
          <span className="sr-only">Cambiar usuario demo</span>
          <ChevronDown className="size-3.5" aria-hidden="true" />
          <select
            value={DEMO_USERS.find((u) => u.rol === user?.rol)?.email ?? ""}
            disabled={switching}
            onChange={(e) => cambiarUsuario(e.target.value)}
            className="rounded-md border border-border bg-bg px-2 py-1 text-xs text-text focus:outline-none focus:ring-2 focus:ring-primary-soft"
          >
            {DEMO_USERS.map((u) => (
              <option key={u.email} value={u.email}>
                {u.label} · {u.rol}
              </option>
            ))}
          </select>
        </label>

        {user && (
          <div className="flex items-center gap-2">
            <div className="hidden text-right sm:block">
              <p className="text-sm font-medium text-text">{user.nombre}</p>
              <p className="text-xs text-text-muted">Ámbito: {ambito.length} institución(es)</p>
            </div>
            <RoleBadge rol={user.rol} />
            <button
              onClick={salir}
              aria-label="Cerrar sesión"
              className="rounded-md p-1.5 text-text-muted hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
              <LogOut className="size-4" aria-hidden="true" />
            </button>
          </div>
        )}
      </div>
    </header>
  );
}
