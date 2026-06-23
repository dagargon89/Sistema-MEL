/** Mensaje legible de cualquier error (ApiError trae .message del doc 05 §1.3). */
export function errMsg(e: unknown): string {
  return e instanceof Error ? e.message : "Ocurrió un error inesperado.";
}
