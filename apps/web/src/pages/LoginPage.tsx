import { useState, type FormEvent } from "react";
import { useNavigate, useLocation, Navigate } from "react-router-dom";
import { FlaskConical } from "lucide-react";
import { useSession } from "@/store/session";
import { Button } from "@/components/ui/Button";
import { TextField } from "@/components/ui/TextField";
import type { RolClave } from "@/lib";

const DEMO: { email: string; nombre: string; rol: RolClave }[] = [
  { email: "capturista@demo.test", nombre: "Ana López", rol: "capturista" },
  { email: "coordinacion@demo.test", nombre: "Carlos Mendoza", rol: "coordinacion" },
  { email: "direccion@demo.test", nombre: "María F. Ríos", rol: "direccion" },
  { email: "admin@demo.test", nombre: "Admin Sistema", rol: "administrador" },
];

// Mock (Fase 1): cualquier contraseña sirve. API real (Fase 2): Shield exige la
// contraseña real; las cuentas demo usan la contraseña sembrada por el seeder.
const USA_MOCK = import.meta.env.VITE_USE_MOCK !== "false";
const CLAVE_DEMO = USA_MOCK ? "demo" : "MelDemo2026!";

export function LoginPage() {
  const { user, login, loading, error } = useSession();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const from = (location.state as { from?: string } | null)?.from ?? "/";
  if (user) return <Navigate to={from} replace />;

  async function entrar(e: FormEvent, correo = email, clave = password) {
    e.preventDefault();
    try {
      await login(correo, clave || CLAVE_DEMO);
      navigate(from, { replace: true });
    } catch {
      /* el error se muestra desde el store */
    }
  }

  return (
    <div className="flex min-h-dvh items-center justify-center bg-surface px-4">
      <div className="w-full max-w-sm">
        <div className="mb-6 text-center">
          <h1 className="text-2xl font-bold text-primary">Sistema MEL</h1>
          <p className="text-sm text-text-muted">Participa Juárez · Monitoreo, Evaluación y Aprendizaje</p>
        </div>

        <form onSubmit={(e) => entrar(e)} className="rounded-lg border border-border bg-bg p-6 shadow-sm">
          <TextField
            label="Correo"
            type="email"
            autoComplete="username"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="correo@cpj.org"
            required
          />
          <TextField
            label="Contraseña"
            type="password"
            autoComplete="current-password"
            className="mt-4"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
          {error && (
            <p role="alert" className="mt-3 rounded-md bg-error-soft px-3 py-2 text-sm text-error">
              {error}
            </p>
          )}
          <Button type="submit" loading={loading} className="mt-5 w-full">
            Iniciar sesión
          </Button>
        </form>

        <div className="mt-5 rounded-md border border-dashed border-border bg-bg p-4">
          <p className="mb-2 flex items-center gap-1.5 text-xs font-semibold text-accent-text">
            <FlaskConical className="size-3.5" aria-hidden="true" />
            {USA_MOCK ? "Cuentas demo (cualquier contraseña)" : "Cuentas demo (contraseña: MelDemo2026!)"}
          </p>
          <div className="grid grid-cols-1 gap-1.5">
            {DEMO.map((d) => (
              <button
                key={d.email}
                onClick={(e) => entrar(e, d.email, CLAVE_DEMO)}
                disabled={loading}
                className="flex items-center justify-between rounded-md border border-border px-3 py-1.5 text-left text-sm hover:bg-primary-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50"
              >
                <span className="font-medium text-text">{d.nombre}</span>
                <span className="text-xs text-text-muted">{d.rol}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
