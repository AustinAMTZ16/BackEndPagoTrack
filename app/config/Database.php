<?php
class Database {
    // private $host = 'localhost';
    // private $user = 'root';
    // private $password = '';

    private $host = '24.144.89.231';
    private $user = 'engranetmx';
    private $password = 'huaweiP20!';

    // private $dbname = 'system_recepcion_pagos_dependencias';
    private $dbname = 'QAPagoTrack';
    public $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }
}
?>
