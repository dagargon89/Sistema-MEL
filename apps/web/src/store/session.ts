import { create } from "zustand";
import { api } from "@/lib";
import type { RolClave } from "@/lib";

export interface SesionUser {
  id: number;
  nombre: string;
  rol: RolClave;
}

interface SessionState {
  user: SesionUser | null;
  ambito: string[];
  token: string | null;
  loading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

/**
 * Sesión en memoria (no se persiste). El mock reinicia su estado al recargar
 * —orden seguro de la metodología (doc 09 §1)— así que la sesión vive solo
 * durante la pestaña. El selector de rol del demo re-invoca login().
 */
export const useSession = create<SessionState>((set) => ({
  user: null,
  ambito: [],
  token: null,
  loading: false,
  error: null,

  async login(email, password) {
    set({ loading: true, error: null });
    try {
      const r = await api.login({ email, password });
      sessionStorage.setItem("mel.token", r.token);
      set({ user: r.user, ambito: r.ambito, token: r.token, loading: false });
    } catch (e) {
      const msg = e instanceof Error ? e.message : "No se pudo iniciar sesión.";
      set({ loading: false, error: msg });
      throw e;
    }
  },

  async logout() {
    try {
      await api.logout();
    } finally {
      sessionStorage.removeItem("mel.token");
      set({ user: null, ambito: [], token: null, error: null });
    }
  },
}));
