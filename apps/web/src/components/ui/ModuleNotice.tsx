import { Info } from "lucide-react";

export function ModuleNotice({
  fase,
  descripcion,
  items,
}: {
  fase: string;
  descripcion: string;
  items: string[];
}) {
  return (
    <div className="rounded-md border border-border bg-surface p-5">
      <div className="mb-2 flex items-center gap-2">
        <Info className="size-5 text-info" aria-hidden="true" />
        <span className="rounded-full bg-info-soft px-2.5 py-0.5 text-xs font-semibold text-info">{fase}</span>
      </div>
      <p className="text-sm text-text">{descripcion}</p>
      <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-text-muted">
        {items.map((it) => (
          <li key={it}>{it}</li>
        ))}
      </ul>
    </div>
  );
}
