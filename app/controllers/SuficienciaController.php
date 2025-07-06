<?php
include_once 'app/models/SuficienciaModel.php';

class SuficienciaController {
    private $model;

    public function __construct() {
        $this->model = new SuficienciaModel();
    }
    // Función para obtener las Suficiencias
    public function getSuficiencias() {
        $result = $this->model->getSuficiencias();
        return $result;
    }
    // Función para registrar una Suficiencia
    public function registrarSuficiencia($data) {
        $result = $this->model->registrarSuficiencia($data);
        return $result;
    }
    // Función para actualizar una Suficiencia
    public function actualizarSuficiencia($data){
        $result = $this->model->actualizarSuficiencia($data);
        return $result;
    }
    // Función para elminar una Suficiencia
    public function eliminarSuficiencia($data){
        $result = $this->model->eliminarSuficiencia($data);
        return $result;
    }
}   
?>