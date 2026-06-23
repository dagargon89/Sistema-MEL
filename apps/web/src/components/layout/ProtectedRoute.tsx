import { Navigate, useLocation } from "react-router-dom";
import type { ReactNode } from "react";
import { useSession } from "@/store/session";
import type { RolClave } from "@/lib";

export function ProtectedRoute({ children, roles }: { children: ReactNode; roles?: RolClave[] }) {
  const { user } = useSession();
  const location = useLocation();

  if (!user) return <Navigate to="/login" state={{ from: location.pathname }} replace />;
  // El administrador funciona como superadmin: acceso total, sin importar los
  // guards de rol por pantalla.
  if (user.rol !== "administrador" && roles && !roles.includes(user.rol))
    return <Navigate to="/acceso-denegado" replace />;

  return <>{children}</>;
}
