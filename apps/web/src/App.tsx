import { Routes, Route } from "react-router-dom";
import { ProtectedRoute } from "@/components/layout/ProtectedRoute";
import { AppShell } from "@/components/layout/AppShell";

import { LoginPage } from "@/pages/LoginPage";
import { AccesoDenegadoPage } from "@/pages/AccesoDenegadoPage";
import { NotFoundPage } from "@/pages/NotFoundPage";
import { TableroPage } from "@/pages/TableroPage";
import { ProgramacionPage } from "@/pages/ProgramacionPage";
import { ProcesoDetailPage } from "@/pages/ProcesoDetailPage";
import { EjecucionesPage } from "@/pages/EjecucionesPage";
import { EjecucionDetailPage } from "@/pages/EjecucionDetailPage";
import { ParticipacionesPage } from "@/pages/ParticipacionesPage";
import { DuplicadosPage } from "@/pages/DuplicadosPage";
import { PersonasPage } from "@/pages/PersonasPage";
import { ProductosPage } from "@/pages/ProductosPage";
import { MetasPage } from "@/pages/MetasPage";
import { ResultadosPage } from "@/pages/ResultadosPage";
import { IncidenciaPage } from "@/pages/IncidenciaPage";
import { VerticalesPage } from "@/pages/VerticalesPage";
import { CatalogosPage } from "@/pages/CatalogosPage";
import { SolicitudesPage } from "@/pages/SolicitudesPage";
import { AuditoriaPage } from "@/pages/AuditoriaPage";

const CAP_COORD = ["capturista", "coordinacion"] as const;

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/acceso-denegado" element={<AccesoDenegadoPage />} />

      <Route
        element={
          <ProtectedRoute>
            <AppShell />
          </ProtectedRoute>
        }
      >
        <Route index element={<TableroPage />} />
        <Route path="programacion" element={<ProtectedRoute roles={[...CAP_COORD]}><ProgramacionPage /></ProtectedRoute>} />
        <Route path="procesos/:id" element={<ProtectedRoute roles={[...CAP_COORD]}><ProcesoDetailPage /></ProtectedRoute>} />
        <Route path="ejecuciones" element={<ProtectedRoute roles={[...CAP_COORD]}><EjecucionesPage /></ProtectedRoute>} />
        <Route path="ejecuciones/:id" element={<ProtectedRoute roles={[...CAP_COORD]}><EjecucionDetailPage /></ProtectedRoute>} />
        <Route path="ejecuciones/:id/participaciones" element={<ProtectedRoute roles={[...CAP_COORD]}><ParticipacionesPage /></ProtectedRoute>} />
        <Route path="duplicados" element={<ProtectedRoute roles={["coordinacion"]}><DuplicadosPage /></ProtectedRoute>} />
        <Route path="personas" element={<ProtectedRoute roles={["coordinacion"]}><PersonasPage /></ProtectedRoute>} />
        <Route path="productos" element={<ProtectedRoute roles={[...CAP_COORD]}><ProductosPage /></ProtectedRoute>} />
        <Route path="metas" element={<MetasPage />} />
        <Route path="resultados" element={<ProtectedRoute roles={["coordinacion"]}><ResultadosPage /></ProtectedRoute>} />
        <Route path="incidencia" element={<ProtectedRoute roles={[...CAP_COORD]}><IncidenciaPage /></ProtectedRoute>} />
        <Route path="verticales" element={<ProtectedRoute roles={[...CAP_COORD]}><VerticalesPage /></ProtectedRoute>} />
        <Route path="catalogos" element={<ProtectedRoute roles={["coordinacion", "administrador"]}><CatalogosPage /></ProtectedRoute>} />
        <Route path="solicitudes" element={<SolicitudesPage />} />
        <Route path="auditoria" element={<ProtectedRoute roles={["coordinacion", "direccion", "administrador"]}><AuditoriaPage /></ProtectedRoute>} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
