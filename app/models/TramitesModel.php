<?php
include_once 'app/config/Database.php';

class TramitesModel
{
    private $conn;
    public function __construct()
    {
        $this->conn = (new Database())->conn;
    }
    // Crear un nuevo trámite
    public function create($data)
    {
        // Establecer la zona horaria globalmente (idealmente se debe hacer una vez en bootstrap)
        date_default_timezone_set('America/Mexico_City');
        $fechaActual = date('Y-m-d H:i:s');

        try {
            // Validar que TipoTramite esté en la lista permitida
            $tiposPermitidos = ['OC', 'OP', 'SRF', 'CRF', 'JA', 'IPS', 'OCO', 'OPO', 'Obra'];
            if (!in_array($data['TipoTramite'], $tiposPermitidos)) {
                throw new Exception("TipoTramite inválido.");
            }

            // Validar que Fondo sea JSON válido
            json_decode($data['Fondo']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Formato de Fondo inválido.");
            }

            // Determinar el query según estatus
            if ($data['Estatus'] === 'Turnado') {
                $query = "INSERT INTO ConsentradoGeneralTramites 
                (Mes, TipoTramite, Dependencia, Proveedor, Concepto, Importe, Estatus, Fondo, FechaLimite, AnalistaID, FechaTurnado, FechaRecepcion ,OfPeticion, NoTramite, DoctacionAnexo, FK_SRF, FechaLimitePago, Analista) 
                VALUES (:Mes, :TipoTramite, :Dependencia, :Proveedor, :Concepto, :Importe, :Estatus, :Fondo, :FechaLimite, :AnalistaID, :FechaTurnado, :FechaRecepcion ,:OfPeticion, :NoTramite, :DoctacionAnexo, :FK_SRF, :FechaLimitePago, :Analista)";
            } else {
                $query = "INSERT INTO ConsentradoGeneralTramites 
                (Mes, TipoTramite, Dependencia, Proveedor, Concepto, Importe, Estatus, Fondo, FechaLimite, AnalistaID, FechaRecepcion, OfPeticion, NoTramite, DoctacionAnexo, FK_SRF, FechaLimitePago, Analista) 
                VALUES (:Mes, :TipoTramite, :Dependencia, :Proveedor, :Concepto, :Importe, :Estatus, :Fondo, :FechaLimite, :AnalistaID, :FechaRecepcion,:OfPeticion, :NoTramite, :DoctacionAnexo, :FK_SRF, :FechaLimitePago, :Analista)";
            }

            $stmt = $this->conn->prepare($query);

            // Binding general
            $stmt->bindParam(':Mes', $data['Mes']);
            $stmt->bindParam(':TipoTramite', $data['TipoTramite']);
            $stmt->bindParam(':Dependencia', $data['Dependencia']);
            $stmt->bindParam(':Proveedor', $data['Proveedor']);
            $stmt->bindParam(':Concepto', $data['Concepto']);
            $stmt->bindValue(':Importe', floatval($data['Importe']));
            $stmt->bindParam(':Estatus', $data['Estatus']);
            $stmt->bindParam(':Fondo', $data['Fondo']);
            $stmt->bindParam(':FechaLimite', $data['FechaLimite']);
            $stmt->bindParam(':AnalistaID', $data['AnalistaID']);
            $stmt->bindParam(':FechaRecepcion', $fechaActual);
            $stmt->bindParam(':OfPeticion', $data['OfPeticion']);
            $stmt->bindParam(':NoTramite', $data['NoTramite']);
            $stmt->bindParam(':DoctacionAnexo', $data['DoctacionAnexo']);
            $stmt->bindValue(':FK_SRF', !empty($data['FK_SRF']) ? $data['FK_SRF'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':FechaLimitePago', !empty($data['FechaLimitePago']) ? $data['FechaLimitePago'] : null, PDO::PARAM_STR);
            $stmt->bindParam(':Analista', $data['Analista']); // Nuevo campo para guardar el nombre del analista

            // Solo si el estatus es Turnado
            if ($data['Estatus'] === 'Turnado') {
                $stmt->bindParam(':FechaTurnado', $fechaActual);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar el trámite.");
            }

            $idContrato = $this->conn->lastInsertId();

            // Construir historial de comentarios
            $comentariosArray = [];
            if (!empty($data['Comentarios'])) {
                $comentarioInicial = [
                    "ID_CONTRATO" => $idContrato,
                    "Modificado_Por" => $data['Analista'],
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estatus'],
                    "Comentario" => $data['Comentarios']
                ];
                $comentariosArray[] = $comentarioInicial;
            }

            $comentariosJSON = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // Actualizar comentarios
            $queryUpdate = "UPDATE ConsentradoGeneralTramites SET Comentarios = :Comentarios WHERE ID_CONTRATO = :ID_CONTRATO";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->bindParam(':Comentarios', $comentariosJSON);
            $stmtUpdate->bindParam(':ID_CONTRATO', $idContrato, PDO::PARAM_INT);

            if (!$stmtUpdate->execute()) {
                throw new Exception("Error al actualizar los comentarios del trámite.");
            }

            return $idContrato;
        } catch (PDOException $e) {
            throw new Exception("Error al registrar el trámite: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Validación o lógica fallida: " . $e->getMessage());
        }
    }
    // Obtener todos los trámites
    public function getAll()
    {
        $query = "SELECT  ISS.NombreUser, ISS.ApellidoUser, CT.* FROM ConsentradoGeneralTramites CT
                    INNER JOIN InicioSesion ISS 
                    ON CT.AnalistaID = ISS.InicioSesionID 
                    ORDER BY CT.FechaRecepcion DESC;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Actualizar un trámite
    public function update($data)
    {
        date_default_timezone_set('America/Mexico_City');
        $fechaActual = date('Y-m-d H:i:s');

        try {
            $querySelect = "SELECT Estatus, Comentarios, AnalistaID FROM ConsentradoGeneralTramites WHERE ID_CONTRATO = :ID_CONTRATO";
            $stmtSelect = $this->conn->prepare($querySelect);
            $stmtSelect->bindParam(':ID_CONTRATO', $data['ID_CONTRATO'], PDO::PARAM_INT);
            $stmtSelect->execute();
            $currentData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if (!$currentData) {
                return false;
            }

            $estatus = !empty($data['Estatus']) ? $data['Estatus'] : $currentData['Estatus'];
            $AnalistaID = !empty($data['AnalistaID']) ? $data['AnalistaID'] : $currentData['AnalistaID'];

            $nuevoComentario = !empty($data['Comentarios']) ? json_encode([
                "ID_CONTRATO" => $data['ID_CONTRATO'],
                "Modificado_Por" => $data['Analista'],
                "Fecha" => $fechaActual,
                "Estatus" => $data['Estatus'],
                "Comentario" => $data['Comentarios']
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';

            $comentariosArray = !empty($currentData['Comentarios']) ? json_decode($currentData['Comentarios'], true) : [];

            if (!empty($nuevoComentario)) {
                $comentariosArray[] = json_decode($nuevoComentario, true);
            }

            $comentariosActualizados = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $queryUpdate = "UPDATE ConsentradoGeneralTramites 
                        SET Estatus = :Estatus, 
                            Comentarios = :Comentarios,  
                            AnalistaID = :AnalistaID";

            // Variables auxiliares
            $docSAP = null;
            $integraSAP = null;

            if ($estatus === 'Devuelto') {
                $queryUpdate .= ", FechaDevuelto = :FechaDevuelto";
            }
            if ($estatus === 'Turnado') {
                $queryUpdate .= ", FechaTurnado = :FechaTurnado";
            }
            if (in_array($estatus, ['RegistradoSAP', 'JuntasAuxiliares', 'Inspectoria'])) {
                $queryUpdate .= ", FechaTurnadoEntrega = :FechaTurnadoEntrega,
                             RemesaNumero = :RemesaNumero,
                             DocSAP = :DocSAP,
                             IntegraSAP = :IntegraSAP";

                // Convertir "0" o 0 a null
                $docSAP = ($data['DocSAP'] === "0" || $data['DocSAP'] === 0) ? null : $data['DocSAP'];
                $integraSAP = ($data['IntegraSAP'] === "0" || $data['IntegraSAP'] === 0) ? null : $data['IntegraSAP'];
            }

            $queryUpdate .= " WHERE ID_CONTRATO = :ID_CONTRATO";

            $stmtUpdate = $this->conn->prepare($queryUpdate);

            $stmtUpdate->bindParam(':ID_CONTRATO', $data['ID_CONTRATO'], PDO::PARAM_INT);
            $stmtUpdate->bindParam(':Estatus', $estatus);
            $stmtUpdate->bindParam(':Comentarios', $comentariosActualizados);
            $stmtUpdate->bindParam(':AnalistaID', $AnalistaID);

            if ($estatus === 'Devuelto') {
                $stmtUpdate->bindParam(':FechaDevuelto', $fechaActual, PDO::PARAM_STR);
            }
            if ($estatus === 'Turnado') {
                $stmtUpdate->bindParam(':FechaTurnado', $fechaActual, PDO::PARAM_STR);
            }
            if (in_array($estatus, ['RegistradoSAP', 'JuntasAuxiliares', 'Inspectoria'])) {
                $stmtUpdate->bindParam(':FechaTurnadoEntrega', $fechaActual, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':RemesaNumero', $data['RemesaNumero']);
                $stmtUpdate->bindValue(':DocSAP', $docSAP, $docSAP === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmtUpdate->bindValue(':IntegraSAP', $integraSAP, $integraSAP === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }

            return $stmtUpdate->execute();
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar el trámite: " . $e->getMessage());
        }
    }
    // Eliminar un trámite
    public function delete($data)
    {
        $query = "DELETE FROM ConsentradoGeneralTramites WHERE ID_CONTRATO = :ID_CONTRATO";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ID_CONTRATO', $data['ID_CONTRATO']);
        $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        if ($filasAfectadas > 0) {
            return true;
        } else {
            return false;
        }
    }
    // Tabla de seguimiento de trámites
    public function getSeguimientoTramites()
    {
        $query = "SELECT 
                    ISN.InicioSesionID,
                    ISN.NombreUser AS Analista,
                    ISN.ApellidoUser AS Apellido,
                    SUM(CASE WHEN CGT.TipoTramite = 'OC' THEN 1 ELSE 0 END) AS OC,
                    SUM(CASE WHEN CGT.TipoTramite = 'OP' THEN 1 ELSE 0 END) AS OP,
                    SUM(CASE WHEN CGT.TipoTramite = 'SRF' THEN 1 ELSE 0 END) AS SRF,
                    SUM(CASE WHEN CGT.TipoTramite = 'CRF' THEN 1 ELSE 0 END) AS CRF,
                    SUM(CASE WHEN CGT.TipoTramite = 'JA' THEN 1 ELSE 0 END) AS JA,
                    SUM(CASE WHEN CGT.TipoTramite = 'IPS' THEN 1 ELSE 0 END) AS IPS,
                    SUM(CASE WHEN CGT.TipoTramite = 'Obra' THEN 1 ELSE 0 END) AS Obra,
                    SUM(CASE WHEN CGT.TipoTramite = 'OCO' THEN 1 ELSE 0 END) AS OCO,
                    SUM(CASE WHEN CGT.TipoTramite = 'OPO' THEN 1 ELSE 0 END) AS OPO,
                    COUNT(*) AS Total
                FROM ConsentradoGeneralTramites CGT
                INNER JOIN InicioSesion ISN ON CGT.AnalistaID = ISN.InicioSesionID
                WHERE CGT.Estatus IN ('Turnado', 'Observaciones')  -- Filtra solo los estatus requeridos
                GROUP BY ISN.InicioSesionID, ISN.NombreUser, ISN.ApellidoUser
                ORDER BY Total DESC;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Tabla de historico de trámites por mes
    public function getHistoricoMes()
    {
        // Array de traducción de meses en inglés a español
        $meses = [
            'January' => 'Enero',
            'February' => 'Febrero',
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];

        // Obtener el mes actual en inglés
        $mesIngles = date('F');

        // Traducir al español
        $mesActual = $meses[$mesIngles];

        // Consulta
        $query = "SELECT 
                ISN.InicioSesionID,
                ISN.NombreUser AS Analista,
                ISN.ApellidoUser AS Apellido,
                SUM(CASE WHEN CGT.TipoTramite = 'OC' THEN 1 ELSE 0 END) AS OC,
                SUM(CASE WHEN CGT.TipoTramite = 'OP' THEN 1 ELSE 0 END) AS OP,
                SUM(CASE WHEN CGT.TipoTramite = 'SRF' THEN 1 ELSE 0 END) AS SRF,
                SUM(CASE WHEN CGT.TipoTramite = 'CRF' THEN 1 ELSE 0 END) AS CRF,
                SUM(CASE WHEN CGT.TipoTramite = 'JA' THEN 1 ELSE 0 END) AS JA,
                SUM(CASE WHEN CGT.TipoTramite = 'IPS' THEN 1 ELSE 0 END) AS IPS,
                SUM(CASE WHEN CGT.TipoTramite = 'Obra' THEN 1 ELSE 0 END) AS Obra,
                SUM(CASE WHEN CGT.TipoTramite = 'OCO' THEN 1 ELSE 0 END) AS OCO,
                SUM(CASE WHEN CGT.TipoTramite = 'OPO' THEN 1 ELSE 0 END) AS OPO,
                COUNT(*) AS Total
            FROM ConsentradoGeneralTramites CGT
            INNER JOIN InicioSesion ISN ON CGT.AnalistaID = ISN.InicioSesionID
            WHERE CGT.Mes = :mesActual
            GROUP BY ISN.InicioSesionID, ISN.NombreUser, ISN.ApellidoUser
            ORDER BY Total DESC;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mesActual', $mesActual);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Conteo por estatus
    public function getConteoEstatus()
    {
        $query = "SELECT Estatus, COUNT(*) AS Total FROM ConsentradoGeneralTramites GROUP BY Estatus;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //Reporte de estatus de comentarios
    public function getReporteEstatusComentarios()
    {
        $query = "SELECT Estatus, Comentarios, is2.NombreUser, FechaTurnado, COUNT(*) AS total_registros
                    FROM ConsentradoGeneralTramites ct
                    INNER JOIN InicioSesion is2 
                    ON ct.AnalistaID = is2.InicioSesionID 
                    GROUP BY Estatus, Comentarios, is2.NombreUser, FechaTurnado 
                    ORDER BY FechaTurnado DESC;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Actualizar un trámite completo
    public function updateTramiteCompleto($data)
    {
        date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
        $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL

        // Validar que el ID del contrato esté presente
        if (!isset($data['ID_CONTRATO']) || empty($data['ID_CONTRATO'])) {
            return ["error" => "ID_CONTRATO es obligatorio"];
        }

        $id_contrato = $data['ID_CONTRATO'];

        // 1️⃣ Consultar el registro actual antes de actualizar
        $query = "SELECT * FROM ConsentradoGeneralTramites WHERE ID_CONTRATO = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return ["error" => "Error en la preparación de la consulta: " . implode(" - ", $this->conn->errorInfo())];
        }
        $stmt->bindParam(1, $id_contrato, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return ["error" => "No se encontró el trámite con ID $id_contrato"];
        }

        // 2️⃣ Manejo de comentarios
        $comentariosArray = [];

        if (!empty($resultado['Comentarios'])) {
            $comentariosArray = json_decode($resultado['Comentarios'], true);
            if (!is_array($comentariosArray)) {
                $comentariosArray = [];
            }
        }

        // Agregar nuevo comentario si `MotivoModificacion` está presente
        if (!empty($data['MotivoModificacion'])) {
            $nuevoComentario = [
                "ID_CONTRATO" => $resultado['ID_CONTRATO'],
                "Modificado_Por" => $data['Analista'],
                "Fecha" => $fechaActual,
                "Estatus" => $data['Estatus'], // Tomar el estatus actual del trámite
                "Comentario" => $data['MotivoModificacion']
            ];
            $comentariosArray[] = $nuevoComentario;
        }

        // Convertir el array de comentarios nuevamente a JSON
        $comentariosActualizados = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 3️⃣ Verificar qué datos han cambiado
        $campos_actualizar = [];
        $parametros = [];

        // Campos que permiten NULL
        $camposNullables = ['RemesaNumero', 'DocSAP', 'IntegraSAP'];

        foreach ($data as $campo => $valor) {
            if ($campo === "ID_CONTRATO" || $campo === "MotivoModificacion") continue;

            // Convertir vacíos a NULL para campos específicos
            if (in_array($campo, $camposNullables)) {
                $valor = ($valor === '' || $valor === null) ? null : trim($valor);
            } else {
                $valor = is_string($valor) ? trim($valor) : $valor;
            }

            $valor_actual = $resultado[$campo] ?? null;

            // Comparación considerando NULLs
            if ($valor !== $valor_actual) {
                $campos_actualizar[] = "$campo = ?";

                // Determinar tipo de parámetro
                $tipo = PDO::PARAM_STR;
                if ($valor === null) {
                    $tipo = PDO::PARAM_NULL;
                } elseif (is_int($valor)) {
                    $tipo = PDO::PARAM_INT;
                } elseif (is_float($valor)) {
                    $tipo = PDO::PARAM_STR; // PDO no tiene para float, se envía como string
                }

                $parametros[] = [
                    "valor" => $valor,
                    "tipo" => $tipo
                ];
            }
        }

        // 4️⃣ Asegurar que `Comentarios` se actualiza con los nuevos comentarios
        $campos_actualizar[] = "Comentarios = ?";
        $parametros[] = [
            "valor" => $comentariosActualizados,
            "tipo" => PDO::PARAM_STR
        ];

        // 5️⃣ Si hay cambios, construir y ejecutar la consulta UPDATE
        if (!empty($campos_actualizar)) {
            // Verificamos si el estatus es "Remesa" y, si es así, agregamos el campo FechaRemesa
            if ($data['Estatus'] === 'Remesa') {
                // Agregar FechaRemesa al final de los campos a actualizar
                $campos_actualizar[] = "FechaRemesa = ?";
                // Añadir la fecha actual a los parámetros
                $parametros[] = [
                    "valor" => $fechaActual,
                    "tipo" => PDO::PARAM_STR
                ];
            }

            // Construir la consulta UPDATE
            $sql_update = "UPDATE ConsentradoGeneralTramites SET " . implode(", ", $campos_actualizar) . " WHERE ID_CONTRATO = ?";
            $stmt_update = $this->conn->prepare($sql_update);

            if (!$stmt_update) {
                return ["error" => "Error en la preparación de la consulta: " . implode(" - ", $this->conn->errorInfo())];
            }

            // Binding de parámetros
            foreach ($parametros as $index => $param) {
                $stmt_update->bindValue($index + 1, $param["valor"], $param["tipo"]);
            }

            // Agregar ID_CONTRATO al final
            $stmt_update->bindValue(count($parametros) + 1, $id_contrato, PDO::PARAM_INT);

            // Ejecutar la consulta
            if ($stmt_update->execute()) {
                // 🔄 6️⃣ Volver a consultar el registro actualizado
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $id_contrato, PDO::PARAM_INT);
                $stmt->execute();
                $registro_actualizado = $stmt->fetch(PDO::FETCH_ASSOC);

                return [
                    $registro_actualizado
                ];
            } else {
                return ["error" => "Error al actualizar: " . implode(" - ", $stmt_update->errorInfo())];
            }
        } else {
            return ["message" => "No hubo cambios en el trámite."];
        }
    }
    // Obtener lista de talles de trámites de getSeguimientoTramites por InicioSesionID
    public function getTallesTramites($data)
    {
        $query = "
        			SELECT 
                        CGT.ID_CONTRATO,
                        CGT.NoTramite,
                        CGT.TipoTramite,
                        CGT.Dependencia,
                        CGT.Proveedor,
                        CGT.Concepto,
                        CGT.Importe,
                        CGT.Estatus,
                        CGT.FechaRecepcion,
                        CGT.FechaLimite,
                        CGT.FechaTurnado,
                        CGT.Comentarios,
                        CGT.DocSAP,
                        CGT.IntegraSAP,
                        CGT.OfPeticion,
                        CGT.Comentarios
                    FROM ConsentradoGeneralTramites CGT
                    INNER JOIN InicioSesion ISN ON CGT.AnalistaID = ISN.InicioSesionID
                    WHERE CGT.Estatus IN ('Turnado', 'Observaciones')
                    AND ISN.InicioSesionID = :InicioSesionID
                    ORDER BY CGT.FechaRecepcion DESC;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':InicioSesionID', $data['InicioSesionID'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Obtener lista de talles de trámites de historico de trámites
    public function getDetalleHistoricoMes($data)
    {
        $query = "
        			SELECT 
                        CGT.ID_CONTRATO,
                        CGT.NoTramite,
                        CGT.TipoTramite,
                        CGT.Dependencia,
                        CGT.Proveedor,
                        CGT.Concepto,
                        CGT.Importe,
                        CGT.Estatus,
                        CGT.FechaRecepcion,
                        CGT.FechaLimite,
                        CGT.FechaTurnado,
                        CGT.Comentarios,
                        CGT.DocSAP,
                        CGT.IntegraSAP,
                        CGT.OfPeticion,
                        CGT.Comentarios
                    FROM ConsentradoGeneralTramites CGT
                    INNER JOIN InicioSesion ISN ON CGT.AnalistaID = ISN.InicioSesionID
                    WHERE ISN.InicioSesionID = :InicioSesionID
                    ORDER BY CGT.FechaRecepcion DESC;
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':InicioSesionID', $data['InicioSesionID'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
