import type { ReactNode } from "react";
import { Skeleton } from "./Spinner";

export function KpiCard({
  label,
  value,
  hint,
  icon,
  loading = false,
}: {
  label: string;
  value: ReactNode;
  hint?: string;
  icon?: ReactNode;
  loading?: boolean;
}) {
  return (
    <div className="rounded-md border border-border bg-surface p-4 shadow-sm">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-text-muted">{label}</p>
        {icon && <span className="text-primary">{icon}</span>}
      </div>
      {loading ? (
        <Skeleton className="mt-2 h-8 w-24" />
      ) : (
        <p className="num mt-1 text-3xl font-bold text-text">{value}</p>
      )}
      {hint && !loading && <p className="mt-1 text-xs text-text-muted">{hint}</p>}
    </div>
  );
}
