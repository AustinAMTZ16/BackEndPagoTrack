<?php
include_once 'app/models/OrdenesPago/OrdenesPagoModel.php';

class OrdenesPagoController {
    private $model;

    public function __construct() {
        $this->model = new OrdenesPagoModel();
    }

    // Crear un nuevo trámite
    public function createTramite($data) {
        $result = $this->model->crearTramite($data);
        return $result;
    }

    // Obtener todos los trámites + nombre del analista
    public function getTramites() {
        $result = $this->model->obtenerTramitesConAnalista();
        return $result;
    }

    // Actualiza el estado de un trámite + comentarios
    public function updateTramite($data) {
        $result = $this->model->actualizarEstadoTramite($data);
        return $result;
    }

    // Eliminar un trámite + validación de ID_CONTRATO
    public function deleteTramite($data) {
        $result = $this->model->eliminarTramite($data);
        return $result;
    }

    // Tabla de seguimiento de trámites por analista
    public function getSeguimientoTramites() {
        $result = $this->model->obtenerResumenTramitesPorAnalista();
        return $result;
    }

    // Tabla de historico de trámites de los analistas por mes
    public function getHistoricoMes() {
        $result = $this->model->obtenerHistoricoTramitesPorMesActual();
        return $result;
    }

    // Conteo por estatus de trámites global
    public function getConteoEstatus() {
        $result = $this->model->obtenerConteoGlobalPorEstatus();
        return $result;
    }

    // Reporte de estatus de comentarios global
    public function getReporteEstatusComentarios() {
        $result = $this->model->obtenerReporteGlobalEstatusYComentarios();
        return $result;
    } 

    // Actualizacion de trámite completo
    public function updateTramiteCompleto($data) {
        $result = $this->model->actualizarTramiteCompleto($data);
        return $result;
    }

    // Obtiene el detalle de trámites en estatus 'Turnado' u 'Observaciones' asignados a un analista específico.
    public function getTallesTramites($data) {
        $result = $this->model->obtenerTramitesPendientesPorAnalista($data);
        return $result;
    }

    // Obtiene el historial completo de trámites asignados a un analista específico.
    public function getDetalleHistoricoMes($data) {
        $result = $this->model->obtenerHistorialTramitesPorAnalista($data);
        return $result;
    }

    // Reporte Prioridad Tramites Fecha Limite de Pago
    public function obtenerReportePrioridadTramites($data) {
        $result = $this->model->obtenerReportePrioridadTramites($data);
        return $result;
    }
}
?>