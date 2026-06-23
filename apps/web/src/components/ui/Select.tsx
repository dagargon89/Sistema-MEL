import { useId, type SelectHTMLAttributes, type ReactNode } from "react";
import { cn } from "./cn";

interface Props extends SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  error?: string | null;
  children: ReactNode;
}

// Todo campo de catálogo es <select> poblado desde la API (RNF-033, doc 08 §5.2).
export function Select({ label, error, id, className, children, required, ...rest }: Props) {
  const auto = useId();
  const fid = id ?? auto;
  const errId = `${fid}-err`;
  return (
    <div className={className}>
      <label htmlFor={fid} className="block text-sm font-medium text-text">
        {label}
        {required && <span className="text-error"> *</span>}
      </label>
      <select
        id={fid}
        required={required}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? errId : undefined}
        className={cn(
          "mt-1 w-full rounded-md border bg-bg px-3 py-2 text-sm text-text",
          "focus:outline-none focus:ring-2 focus:ring-primary-soft",
          "disabled:bg-surface disabled:text-text-muted",
          error ? "border-error focus:border-error" : "border-border focus:border-primary",
        )}
        {...rest}
      >
        {children}
      </select>
      {error && (
        <p id={errId} role="alert" className="mt-1 text-xs text-error">
          {error}
        </p>
      )}
    </div>
  );
}
