<?php
include_once 'app/config/Database.php';
    // InicioSesionID INT AUTO_INCREMENT PRIMARY KEY, -- Llave primaria con incremento automático
    // NickUser VARCHAR(50) NOT NULL, -- Nombre de usuario o apodo
    // NombreUser VARCHAR(50) NOT NULL, -- Nombre del usuario
    // ApellidoUser VARCHAR(50) NOT NULL, -- Apellido del usuario
    // CorreoUser VARCHAR(50) NOT NULL UNIQUE, -- Correo electrónico (único)
    // ClaveUser VARCHAR(50) NOT NULL, -- Contraseña (se recomienda usar un hash)
    // FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación con valor por defecto
    // RolUser VARCHAR(50) NOT NULL, -- Rol del usuario (e.g., admin, user)
    // DepartamentoUser VARCHAR(50) NOT NULL -- Departamento asociado al usuario


class LoginModel {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->conn;
    }
    // Iniciar sesión
    public function login($data) {
        $query = "SELECT InicioSesionID, NickUser, NombreUser, ApellidoUser, CorreoUser, RolUser, DepartamentoUser FROM InicioSesion WHERE CorreoUser = :CorreoUser AND ClaveUser = :ClaveUser";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':CorreoUser', $data['CorreoUser']);
        $stmt->bindParam(':ClaveUser', $data['ClaveUser']);
        // Ejecuta la consulta
        if ($stmt->execute()) {
            // Devuelve el resultado (puedes ajustar esto según tus necesidades)
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Error al iniciar sesión.");
        }
    }
    // Registrar un nuevo usuario
    public function register($data) {
        $query = "INSERT INTO InicioSesion (NickUser, NombreUser, ApellidoUser, CorreoUser, ClaveUser, RolUser, DepartamentoUser) VALUES (:NickUser, :NombreUser, :ApellidoUser, :CorreoUser, :ClaveUser, :RolUser, :DepartamentoUser)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':NickUser', $data['NickUser']);
        $stmt->bindParam(':NombreUser', $data['NombreUser']);
        $stmt->bindParam(':ApellidoUser', $data['ApellidoUser']);
        $stmt->bindParam(':CorreoUser', $data['CorreoUser']);
        $stmt->bindParam(':ClaveUser', $data['ClaveUser']);
        $stmt->bindParam(':RolUser', $data['RolUser']);
        $stmt->bindParam(':DepartamentoUser', $data['DepartamentoUser']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        } else {
            throw new Exception("Error al registrar el usuario.");
        }
    }
    // Modificar un usuario
    public function update($data) {
        $query = "UPDATE InicioSesion SET NickUser = :NickUser, NombreUser = :NombreUser, ApellidoUser = :ApellidoUser, CorreoUser = :CorreoUser WHERE InicioSesionID = :InicioSesionID";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':InicioSesionID', $data['InicioSesionID']);
        $stmt->bindParam(':NickUser', $data['NickUser']);
        $stmt->bindParam(':NombreUser', $data['NombreUser']);
        $stmt->bindParam(':ApellidoUser', $data['ApellidoUser']);
        $stmt->bindParam(':CorreoUser', $data['CorreoUser']);
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
    // Eliminar un usuario
    public function delete($data) {
        $query = "DELETE FROM InicioSesion WHERE InicioSesionID = :InicioSesionID";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':InicioSesionID', $data['InicioSesionID']);
        $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        if ($filasAfectadas > 0) {
            return true;
        } else {
            return false;
        }
    }
    // Recuperar contraseña
    public function recoverPassword($data) {
        $query = "UPDATE InicioSesion SET ClaveUser = :ClaveUser WHERE CorreoUser = :CorreoUser";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':ClaveUser', $data['ClaveUser']);
        $stmt->bindParam(':CorreoUser', $data['CorreoUser']);
        $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        if ($filasAfectadas > 0) {
            return true;
        } else {
            return false;
        }
    }
    // Verificar si el usuario existe
    public function checkUserExists($data) {
        $query = "SELECT * FROM InicioSesion WHERE CorreoUser = :CorreoUser";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':CorreoUser', $data['CorreoUser']);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>