import type { ButtonHTMLAttributes, ReactNode } from "react";
import { Loader2 } from "lucide-react";
import { cn } from "./cn";

type Variant = "primary" | "secondary" | "ghost" | "danger";

interface Props extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant;
  loading?: boolean;
  icon?: ReactNode;
}

// Un solo botón primario por vista (doc 08 §5.1). La lima NO es botón de acción.
const styles: Record<Variant, string> = {
  primary: "bg-primary text-inverse hover:bg-primary-hover",
  secondary: "border border-primary text-primary bg-bg hover:bg-primary-soft",
  ghost: "text-primary hover:bg-primary-soft",
  danger: "bg-error text-inverse hover:brightness-110",
};

export function Button({
  variant = "primary",
  loading = false,
  icon,
  children,
  className,
  disabled,
  type = "button",
  ...rest
}: Props) {
  return (
    <button
      type={type}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      className={cn(
        "inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors duration-[120ms]",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1",
        "disabled:cursor-not-allowed disabled:opacity-50",
        styles[variant],
        className,
      )}
      {...rest}
    >
      {loading ? <Loader2 className="size-4 animate-spin" aria-hidden="true" /> : icon}
      {children}
    </button>
  );
}
