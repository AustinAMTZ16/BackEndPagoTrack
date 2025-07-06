<?php
include_once 'app/models/LoginModel.php';

class LoginController {
    private $model;

    public function __construct() {
        $this->model = new LoginModel();
    }

    // Iniciar sesión POST
    public function loginUser($data) {
        $result = $this->model->login($data);
        return $result;
    }

    // Registrar un nuevo usuario POST
    public function registerUser($data) {
        $result = $this->model->register($data);
        return $result;
    }

    // Modificar un usuario PATCH
    public function updateUser($data) {
        $result = $this->model->update($data);
        return $result;
    }

    // Eliminar un usuario DELETE
    public function deleteUser($data) {
        $result = $this->model->delete($data);
        return $result;
    }

    // Recuperar contraseña PATCH
    public function recoverPasswordUser($data) {
        $result = $this->model->recoverPassword($data);
        return $result;
    }

    // Verificar si el usuario existe GET
    public function checkUserExistsUser($data) {
        $result = $this->model->checkUserExists($data);
        return $result;
    }
}
?>