<?php
include_once 'app/models/TramitesModel.php';

class TramitesController {
    private $model;

    public function __construct() {
        $this->model = new TramitesModel();
    }

    // Crear un nuevo trámite
    public function createTramite($data) {
        $result = $this->model->create($data);
        return $result;
    }

    // Obtener todos los trámites
    public function getTramites() {
        $result = $this->model->getAll();
        return $result;
    }

    // Actualizar un trámite
    public function updateTramite($data) {
        $result = $this->model->update($data);
        return $result;
    }

    // Eliminar un trámite
    public function deleteTramite($data) {
        $result = $this->model->delete($data);
        return $result;
    }

    // Obtener el seguimiento de trámites
    public function getSeguimientoTramites() {
        $result = $this->model->getSeguimientoTramites();
        return $result;
    }

    // Tabla de historico de trámites por mes
    public function getHistoricoMes() {
        $result = $this->model->getHistoricoMes();
        return $result;
    }

    // Obtener el conteo de estatus
    public function getConteoEstatus() {
        $result = $this->model->getConteoEstatus();
        return $result;
    }

    // Obtener el reporte de estatus de comentarios
    public function getReporteEstatusComentarios() {
        $result = $this->model->getReporteEstatusComentarios();
        return $result;
    } 

    // Función Actualizar Tramite Completo  
    public function updateTramiteCompleto($data) {
        $result = $this->model->updateTramiteCompleto($data);
        return $result;
    }

    // Obtener lista de talles de trámites de getSeguimientoTramites por InicioSesionID
    public function getTallesTramites($data) {
        $result = $this->model->getTallesTramites($data);
        return $result;
    }

    // Obtener lista de talles de trámites de getSeguimientoTramites por InicioSesionID
    public function getDetalleHistoricoMes($data) {
        $result = $this->model->getDetalleHistoricoMes($data);
        return $result;
    }
}
?>