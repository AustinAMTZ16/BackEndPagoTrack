<?php
include_once 'app/models/KpiModel.php';

class KpiController {
    private $model;

    public function __construct() {
        $this->model = new KpiModel();
    }
    // Función para obtener los KPI's
    public function obtenerKPI() {
        $result = $this->model->obtenerKPI();
        return $result;
    }
}
?>