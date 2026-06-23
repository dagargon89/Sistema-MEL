import { AlertTriangle } from "lucide-react";
import { Button } from "./Button";

export function ErrorState({
  title = "No se pudo cargar",
  message,
  onRetry,
}: {
  title?: string;
  message?: string;
  onRetry?: () => void;
}) {
  return (
    <div
      role="alert"
      className="flex flex-col items-center justify-center gap-2 rounded-md border border-error-soft bg-error-soft px-6 py-12 text-center"
    >
      <AlertTriangle className="size-8 text-error" aria-hidden="true" />
      <h3 className="text-base font-semibold text-text">{title}</h3>
      {message && <p className="max-w-sm text-sm text-text-muted">{message}</p>}
      {onRetry && (
        <Button variant="secondary" onClick={onRetry} className="mt-2">
          Reintentar
        </Button>
      )}
    </div>
  );
}
