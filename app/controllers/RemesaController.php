<?php
include_once 'app/models/RemesaModel.php';

class RemesaController {
    private $model;

    public function __construct() {
        $this->model = new RemesaModel();
    }

    // Obtener lista de remesas
    public function getListaRemesas() {
        $result = $this->model->getListaRemesas();
        return $result;
    }

    // Obtener detalle de remesas
    public function getDetalleRemesas($data) {
        $result = $this->model->getDetalleRemesas($data);
        return $result;
    }
    // Crear una nueva remesa
    public function createRemesa($data) {
        $result = $this->model->createRemesa($data);
        return $result;
    }
    // Actualizar una remesa
    public function updateRemesa($data) {
        $result = $this->model->updateRemesa($data);
        return $result;
    }
    // Actualizar trámites de una remesa
    public function updateRemesaTramites($data) {
        $result = $this->model->updateRemesaTramites($data);
        return $result;
    }
















    

    // Obtener todas las remesas
    public function getRemesas() {
        $result = $this->model->getAll();
        return $result;
    }

    

    // Eliminar una remesa
    public function deleteRemesa($data) {
        $result = $this->model->delete($data);
        return $result;
    }

    // Obtener las remesas con trámites
    public function getRemesasWithTramites() {
        $result = $this->model->getRemesasWithTramites();
        return $result;
    }   

    // Actualizar trámite y remesa
    public function updateTramiteRemesa($data) {
        $result = $this->model->updateTramiteRemesa($data);
        return $result;
    }   
}
?>