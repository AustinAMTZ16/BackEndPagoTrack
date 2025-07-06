<?php
include_once 'app/models/Oficios/OficiosModel.php';

class OficiosController {
    private $model;

    public function __construct() {
        $this->model = new OficiosModel();
    }

    public function listarRegistroOficios() {
        return $this->model->listarRegistroOficios();
    }

    public function crearRegistroOficio($data){
        return $this->model->crearRegistroOficio($data);
    }

    public function actualizarRegistroOficio($data){
        return $this->model->actualizarRegistroOficio($data);
    }

    public function eliminarRegistroOficio($data){
        return $this->model->eliminarRegistroOficio($data);
    }  

    public function actualizarRegistroOficioArchivo($data, $archivos){
        return $this->model->actualizarRegistroOficioArchivo($data, $archivos);
    }   
}   
?>