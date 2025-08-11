<?php
// 1. Incluir el autoloader de Composer.
// La ruta debe ser relativa desde este archivo (app/config/Database.php)
// hasta la carpeta raíz del proyecto. '__DIR__ . /../../'` significa "dos carpetas arriba".
require_once __DIR__ . '/../../vendor/autoload.php';
class Database {
    // La propiedad para guardar la única instancia de la clase.
    private static $instance = null;
    
    // La propiedad de la conexión sigue siendo pública.
    public $conn;

    // El constructor es 'private' para forzar el uso de getInstance().
    private function __construct() {
        try {
            // 2. Cargar las variables de entorno desde el archivo .env
            // Esto busca el archivo .env en la carpeta raíz del proyecto.
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();

            // 3. Leer las credenciales desde las variables de entorno.
            $host = $_ENV['DB_HOST'];
            $dbname = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASS'];
            
            // Validar que las variables se cargaron
            if (!$host || !$dbname || !$user) { // La contraseña puede estar vacía
                throw new Exception("Las variables de entorno de la base de datos no están configuradas correctamente.");
            }

            // 4. Crear la conexión PDO con las variables cargadas.
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(Exception $e) {
            error_log("Error de configuración o conexión a la BBDD: " . $e->getMessage());
            die(json_encode(['error' => true, 'message' => 'Error crítico de configuración del servidor.']));
        }
    }

    // El método estático para obtener la única instancia.
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Prevenir la clonación de la instancia.
    private function __clone() { }
}
?>