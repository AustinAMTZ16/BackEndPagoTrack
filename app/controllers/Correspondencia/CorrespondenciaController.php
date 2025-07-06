<?php
include_once 'app/models/Correspondencia/CorrespondenciaModel.php';

class CorrespondenciaController {
    private $model;

    public function __construct() {
        $this->model = new CorrespondenciaModel();
    }

    public function listarOficios() {
        return $this->model->listarOficios();
    }

    public function crearOficio($data){
        return $this->model->crearOficio($data);
    }

    public function actualizarOficio($data){
        return $this->model->actualizarOficio($data);
    }

    public function eliminarOficio($id){
        return $this->model->eliminarOficio($id);
    }  

    public function actualizarOficioArchivo($data, $archivos){
        return $this->model->actualizarOficioArchivo($data, $archivos);
    }  
    
    public function crearOficioArchivo($data, $Archivo){
        return $this->model->crearOficioArchivo($data, $Archivo);
    }
}   
?>