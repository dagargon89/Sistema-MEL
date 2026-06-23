import { Link } from "react-router-dom";
import { ShieldX } from "lucide-react";

export function AccesoDenegadoPage() {
  return (
    <div className="flex min-h-dvh flex-col items-center justify-center gap-3 bg-surface px-4 text-center">
      <ShieldX className="size-12 text-error" aria-hidden="true" />
      <h1 className="text-2xl font-bold text-text">Acceso denegado</h1>
      <p className="max-w-md text-sm text-text-muted">
        Tu rol o ámbito de institución no permite ver esta sección. La autorización real la valida el
        servidor (RF-AUTH-003/004); ocultar el botón no es seguridad.
      </p>
      <Link
        to="/"
        className="mt-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverse hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
      >
        Volver al tablero
      </Link>
    </div>
  );
}
