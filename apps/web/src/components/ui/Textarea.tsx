import { useId, type TextareaHTMLAttributes } from "react";
import { cn } from "./cn";

interface Props extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string;
  error?: string | null;
  hint?: string;
}

export function Textarea({ label, error, hint, id, className, required, rows = 3, ...rest }: Props) {
  const auto = useId();
  const fid = id ?? auto;
  const errId = `${fid}-err`;
  return (
    <div className={className}>
      <label htmlFor={fid} className="block text-sm font-medium text-text">
        {label}
        {required && <span className="text-error"> *</span>}
      </label>
      <textarea
        id={fid}
        rows={rows}
        required={required}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? errId : undefined}
        className={cn(
          "mt-1 w-full rounded-md border bg-bg px-3 py-2 text-sm text-text",
          "focus:outline-none focus:ring-2 focus:ring-primary-soft",
          error ? "border-error focus:border-error" : "border-border focus:border-primary",
        )}
        {...rest}
      />
      {hint && !error && <p className="mt-1 text-xs text-text-muted">{hint}</p>}
      {error && (
        <p id={errId} role="alert" className="mt-1 text-xs text-error">
          {error}
        </p>
      )}
    </div>
  );
}
