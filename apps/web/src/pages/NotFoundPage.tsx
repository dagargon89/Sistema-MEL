import { Link } from "react-router-dom";

export function NotFoundPage() {
  return (
    <div className="flex min-h-dvh flex-col items-center justify-center gap-3 bg-surface px-4 text-center">
      <p className="text-5xl font-bold text-primary">404</p>
      <h1 className="text-xl font-semibold text-text">Página no encontrada</h1>
      <Link
        to="/"
        className="mt-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverse hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
      >
        Volver al tablero
      </Link>
    </div>
  );
}
