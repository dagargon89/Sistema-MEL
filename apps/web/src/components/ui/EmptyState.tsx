import { Inbox } from "lucide-react";
import type { ReactNode } from "react";

export function EmptyState({
  title = "Sin registros",
  message,
  action,
}: {
  title?: string;
  message?: string;
  action?: ReactNode;
}) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-md border border-dashed border-border bg-surface px-6 py-12 text-center">
      <Inbox className="size-8 text-text-muted" aria-hidden="true" />
      <h3 className="text-base font-semibold text-text">{title}</h3>
      {message && <p className="max-w-sm text-sm text-text-muted">{message}</p>}
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}
