<?php
include_once 'app/config/Database.php';

class KpiModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->conn;
    }

    // Función para obtener los KPI's
    public function obtenerKPI()
    {
        try {
            // Consulta de totales
            $stmt = $this->conn->prepare("SELECT 
                SUM(CASE WHEN DATE(FechaLimite) = CURDATE() THEN 1 ELSE 0 END) AS total_hoy,
                SUM(CASE WHEN DATE(FechaLimite) < CURDATE() THEN 1 ELSE 0 END) AS total_vencidos,
                SUM(CASE WHEN DATE(FechaLimite) > CURDATE() THEN 1 ELSE 0 END) AS total_futuros
            FROM ConsentradoGeneralTramites
            WHERE Estatus IN ('Turnado','Observaciones', 'Devuelto', 'DevueltoOrdenPago')");

            $stmt->execute();
            $totales = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["total_hoy" => 0, "total_vencidos" => 0, "total_futuros" => 0];

            // Función para obtener registros
            function obtenerRegistros($pdo, $condicion)
            {
                $sql = "SELECT ct.ID_CONTRATO,ct.Estatus,ct.FechaRecepcion,ct.FechaLimite,is2.NombreUser,is2.ApellidoUser,ct.Comentarios,ct.TipoTramite,ct.Dependencia,ct.Proveedor,ct.Importe,ct.Concepto
                        FROM ConsentradoGeneralTramites ct
                        INNER JOIN InicioSesion is2 
                        ON ct.AnalistaID  = is2.InicioSesionID 
                        WHERE DATE(FechaLimite) $condicion CURDATE()
                        AND Estatus IN ('Turnado', 'Observaciones', 'Devuelto', 'DevueltoOrdenPago')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            // Obtener el conteo de registros por estatus
            $stmt = $this->conn->prepare("
                 SELECT Estatus, COUNT(*) AS total_registros 
                FROM ConsentradoGeneralTramites 
                WHERE Mes = CASE MONTH(CURDATE())
                    WHEN 1 THEN 'Enero'
                    WHEN 2 THEN 'Febrero'
                    WHEN 3 THEN 'Marzo'
                    WHEN 4 THEN 'Abril'
                    WHEN 5 THEN 'Mayo'
                    WHEN 6 THEN 'Junio'
                    WHEN 7 THEN 'Julio'
                    WHEN 8 THEN 'Agosto'
                    WHEN 9 THEN 'Septiembre'
                    WHEN 10 THEN 'Octubre'
                    WHEN 11 THEN 'Noviembre'
                    WHEN 12 THEN 'Diciembre'
                END
                GROUP BY Estatus
            ");
            $stmt->execute();
            $conteo_estatus = $stmt->fetchAll(PDO::FETCH_ASSOC);


            // Obtener los registros según la condición
            $tramites_hoy = obtenerRegistros($this->conn, "=");
            $tramites_vencidos = obtenerRegistros($this->conn, "<");
            $tramites_futuros = obtenerRegistros($this->conn, ">");

            // Retorno en formato JSON
            return json_encode([
                "total_hoy" => (int) $totales["total_hoy"],
                "total_vencidos" => (int) $totales["total_vencidos"],
                "total_futuros" => (int) $totales["total_futuros"],
                "tramites_hoy" => $tramites_hoy,
                "tramites_vencidos" => $tramites_vencidos,
                "tramites_futuros" => $tramites_futuros,
                "conteo_estatus" => $conteo_estatus
            ]);
        } catch (PDOException $e) {
            // Respuesta de error en caso de fallo
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
}
