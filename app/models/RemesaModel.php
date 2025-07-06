<?php
include_once 'app/config/Database.php';

class RemesaModel
{
    private $conn;
    public function __construct()
    {
        $this->conn = (new Database())->conn;
    }

    // Obtener lista de remesas
    public function getListaRemesas()
    {
        $query = "SELECT 
                        t.Grupo,
                        t.TotalRegistros,
                        r.Estatus
                    FROM (
                        SELECT 
                            SUBSTRING_INDEX(RemesaNumero, '-', 2) AS Grupo,
                            COUNT(*) AS TotalRegistros
                        FROM ConsentradoGeneralTramites
                        WHERE RemesaNumero IS NOT NULL
                        GROUP BY Grupo
                    ) AS t
                    LEFT JOIN Remesas r ON r.FKNumeroRemesa = t.Grupo
                    ORDER BY STR_TO_DATE(SUBSTRING_INDEX(t.Grupo, '-', 1), '%d%m%y') DESC;";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Obtener detalle de remesas
    public function getDetalleRemesas($data)
    {
        $consecutivo = $data['consecutivo'];
        $query = "SELECT 
                        t.Grupo,
                        t.RemesaNumero,
                        t.ID_CONTRATO,
                        t.OfPeticion,
                        t.FechaRecepcion,
                        t.IntegraSAP,
                        t.DocSAP,
                        t.TipoTramite,
                        t.NoTramite,
                        t.Dependencia,
                        t.Proveedor,
                        t.Concepto,
                        t.Importe,
                        t.DoctacionAnexo,
                        t.Fondo,
                        CONCAT(i.NombreUser, ' ', i.ApellidoUser) AS Analista,
                        t.Estatus,
                        r.FechaRemesa,
                        r.Comentarios 
                    FROM (
                        SELECT 
                            CG.*,
                            SUBSTRING_INDEX(CG.RemesaNumero, '-', 2) AS Grupo
                        FROM ConsentradoGeneralTramites CG
                        WHERE CG.RemesaNumero IS NOT NULL
                    ) t
                            INNER JOIN InicioSesion i ON t.AnalistaID = i.InicioSesionID
                            LEFT JOIN Remesas r ON r.FKNumeroRemesa = '$consecutivo'
                            WHERE t.Grupo = '$consecutivo'
                            ORDER BY t.Grupo, t.RemesaNumero;
                 ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Crear una nueva remesa
    public function createRemesa($data)
    {
        try {
            // Validar si ya existe la remesa
            $sqlValidacionRemesa = "SELECT * FROM Remesas WHERE FKNumeroRemesa = :FKNumeroRemesa;";
            $stmtValidacionRemesa = $this->conn->prepare($sqlValidacionRemesa);
            $stmtValidacionRemesa->bindParam(':FKNumeroRemesa', $data['FKNumeroRemesa']);
            $stmtValidacionRemesa->execute();

            $existeRemesaDB = $stmtValidacionRemesa->fetchAll(PDO::FETCH_ASSOC);

            if ($existeRemesaDB == true) {
                http_response_code(200); // Código HTTP OK
                header('Content-Type: application/json');

                echo json_encode([
                    'success' => true,
                    'message' => 'La remesa ya existe en el sistema.',
                    'data' => $existeRemesaDB
                ], JSON_UNESCAPED_UNICODE);

                exit; // Muy importante para detener la ejecución del resto del código
            }

            // Iniciar transacción
            $this->conn->beginTransaction();

            // Valores con defaults
            $departamento = (!isset($data['DepartamentoTurnado']) || empty($data['DepartamentoTurnado']))
                ? 'GLOSA'
                : $data['DepartamentoTurnado'];

            $estatus = (!isset($data['Estatus']) || empty($data['Estatus']))
                ? 'Pendiente'
                : $data['Estatus'];

            $comentarios = (!isset($data['Comentarios']) || empty($data['Comentarios']))
                ? ''
                : $data['Comentarios'];

            // Insertar en Remesas
            $query = "INSERT INTO Remesas (DepartamentoTurnado, FKNumeroRemesa, Estatus, Comentarios) 
                      VALUES (:DepartamentoTurnado, :FKNumeroRemesa, :Estatus, :Comentarios)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':DepartamentoTurnado', $departamento);
            $stmt->bindParam(':FKNumeroRemesa', $data['FKNumeroRemesa']);
            $stmt->bindParam(':Estatus', $estatus);
            $stmt->bindParam(':Comentarios', $comentarios);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la remesa.");
            }

            // Obtener ID insertado
            $lastInsertId = $this->conn->lastInsertId();

            // Obtener trámites que coincidan con el FKNumeroRemesa
            $selectTramites = "SELECT ID_CONTRATO, Comentarios FROM ConsentradoGeneralTramites WHERE RemesaNumero LIKE :FKNumeroRemesa";
            $stmtSelect = $this->conn->prepare($selectTramites);
            $fkRemesaLike = $data['FKNumeroRemesa'] . '%';
            $stmtSelect->bindParam(':FKNumeroRemesa', $fkRemesaLike);
            $stmtSelect->execute();
            $tramites = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

            // Definir zona horaria de México
            date_default_timezone_set('America/Mexico_City');
            // Obtener la fecha y hora actual en formato MySQL
            $fechaActual = date('Y-m-d H:i:s');

            // Recorrer los trámites para actualizar estatus y comentarios
            foreach ($tramites as $tramite) {
                $comentariosAnteriores = !empty($tramite['Comentarios']) ? json_decode($tramite['Comentarios'], true) : [];

                $nuevoComentario = [
                    "ID_CONTRATO" => $tramite['ID_CONTRATO'],
                    "Modificado_Por" => $data['Analista'],
                    "Fecha" => $fechaActual,
                    "Estatus" => 'Remesa',
                    "Comentario" => 'Remesa creada'
                ];

                $comentariosAnteriores[] = $nuevoComentario;
                $comentariosActualizados = json_encode($comentariosAnteriores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                // Actualizar cada trámite
                $updateTramite = "UPDATE ConsentradoGeneralTramites 
                                  SET Estatus = 'Remesa', Comentarios = :Comentarios, FechaRemesa = :FechaRemesa
                                  WHERE ID_CONTRATO = :ID_CONTRATO";
                $stmtUpdate = $this->conn->prepare($updateTramite);
                $stmtUpdate->bindParam(':Comentarios', $comentariosActualizados);
                $stmtUpdate->bindParam(':FechaRemesa', $fechaActual);
                $stmtUpdate->bindParam(':ID_CONTRATO', $tramite['ID_CONTRATO'], PDO::PARAM_INT);

                if (!$stmtUpdate->execute()) {
                    throw new Exception("Error al actualizar trámite con ID_CONTRATO " . $tramite['ID_CONTRATO']);
                }
            }

            // Confirmar todo
            $this->conn->commit();

            return $lastInsertId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    // Actualizar una remesa
    public function updateRemesa($data)
    {
        try {
            // Actualizar la remesa con los nuevos comentarios acumulativos en JSON
            $queryUpdate = "UPDATE Remesas SET Estatus = :Estatus, Comentarios = :Comentarios, DepartamentoTurnado = :DepartamentoTurnado, FechaPago = :FechaPago WHERE ID_REMESA = :ID_REMESA";
            $stmtUpdate = $this->conn->prepare($queryUpdate);
            $stmtUpdate->bindParam(':Estatus', $data['Estatus']);
            $stmtUpdate->bindParam(':Comentarios', $data['Comentarios']);
            $stmtUpdate->bindParam(':DepartamentoTurnado', $data['DepartamentoTurnado']);
            $stmtUpdate->bindParam(':FechaPago', $data['FechaPago']);
            $stmtUpdate->bindParam(':ID_REMESA', $data['ID_REMESA']);

            $stmtUpdate->execute();

            // Validar si se actualizó al menos una fila
            if ($stmtUpdate->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Remesa actualizada correctamente.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se realizaron cambios (la remesa ya tenía estos valores o el ID no existe).'
                ];
            }
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar la remesa: " . $e->getMessage());
        }
    }
    // Actualizar trámites de una remesa
    public function updateRemesaTramites($data)
    {
        // data : {
        //     "ID_CONTRATO": "1379, 1365, 1368, 1040, 1378",
        //     "FKNumeroRemesa" : "240325-1",
        //     "estatus": "RemesaAprobada"
        // }

        // Convertir string a array si viene como texto
        if (isset($data['ID_CONTRATO']) && is_string($data['ID_CONTRATO'])) {
            $data['ID_CONTRATO'] = array_map('trim', explode(',', $data['ID_CONTRATO']));
        }

        // Validar que sea un array válido
        if (empty($data['ID_CONTRATO']) || !is_array($data['ID_CONTRATO'])) {
            throw new Exception("Se requiere una lista válida de IDs de contrato.");
        }

        // Zona horaria y fecha actual
        date_default_timezone_set('America/Mexico_City');
        $fechaActual = date('Y-m-d H:i:s');

        // Iniciar transacción
        $this->conn->beginTransaction();

        try {
            // Obtener los trámites existentes
            $placeholders = implode(',', array_fill(0, count($data['ID_CONTRATO']), '?'));
            $selectQuery = "SELECT ID_CONTRATO, Comentarios FROM ConsentradoGeneralTramites WHERE ID_CONTRATO IN ($placeholders)";
            $stmtSelect = $this->conn->prepare($selectQuery);
            foreach ($data['ID_CONTRATO'] as $i => $id) {
                $stmtSelect->bindValue($i + 1, $id, PDO::PARAM_INT);
            }
            $stmtSelect->execute();
            $tramites = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

            // Recorrer y actualizar uno a uno con comentarios acumulativos
            foreach ($tramites as $tramite) {
                $comentariosAnteriores = !empty($tramite['Comentarios']) ? json_decode($tramite['Comentarios'], true) : [];

                $nuevoComentario = [
                    "ID_CONTRATO" => $tramite['ID_CONTRATO'],
                    "Modificado_Por" => $data['Analista'],
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estatus'],
                    "Comentario" => 'La remesa fue aprobada'
                ];

                $comentariosAnteriores[] = $nuevoComentario;
                $comentariosActualizados = json_encode($comentariosAnteriores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                $updateQuery = "UPDATE ConsentradoGeneralTramites 
                            SET Estatus = :Estatus, 
                                FechaRemesaAprobada = :FechaRemesaAprobada, 
                                Comentarios = :Comentarios 
                            WHERE ID_CONTRATO = :ID_CONTRATO";
                $stmtUpdate = $this->conn->prepare($updateQuery);
                $stmtUpdate->bindParam(':FechaRemesaAprobada', $fechaActual);
                $stmtUpdate->bindParam(':Comentarios', $comentariosActualizados);
                $stmtUpdate->bindParam(':Estatus', $data['Estatus']);
                $stmtUpdate->bindParam(':ID_CONTRATO', $tramite['ID_CONTRATO'], PDO::PARAM_INT);

                if (!$stmtUpdate->execute()) {
                    throw new Exception("Error al actualizar trámite con ID_CONTRATO " . $tramite['ID_CONTRATO']);
                }
            }

            // Actualizar la remesa
            // Actualizar la remesa
            $estatusRemesa = 'Aprobado';
            $queryUpdateRemesa = "UPDATE Remesas SET Estatus = :Estatus WHERE FKNumeroRemesa = :FKNumeroRemesa";
            $stmtUpdateRemesa = $this->conn->prepare($queryUpdateRemesa);
            $stmtUpdateRemesa->bindParam(':Estatus', $data['Estatus']);
            $stmtUpdateRemesa->bindParam(':FKNumeroRemesa', $data['FKNumeroRemesa']);
            $stmtUpdateRemesa->execute();


            // Confirmar transacción
            $this->conn->commit();

            return count($tramites); // Cantidad de trámites actualizados

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }















































    // Obtener todas las remesas
    public function getAll()
    {
        $query = "SELECT * FROM Remesas";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Eliminar una remesa
    public function delete($data)
    {
        $query = "DELETE FROM Remesas WHERE ID_REMESA = :ID_REMESA";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ID_REMESA', $data['ID_REMESA']);
        $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        if ($filasAfectadas > 0) {
            return true;
        } else {
            return false;
        }
    }
    //Lista de remesas con tramites para el seguimiento de ordenes de pago
    public function getRemesasWithTramites()
    {
        $query = "SELECT 
                    cgt.ID_CONTRATO,
                    cgt.Mes,
                    cgt.FechaRecepcion,
                    cgt.TipoTramite,
                    cgt.Dependencia,
                    cgt.Proveedor,
                    cgt.Concepto,
                    cgt.Importe,
                    cgt.Estatus,
                    cgt.Comentarios,
                    cgt.Fondo,
                    cgt.FechaLimite,
                    cgt.FechaTurnado,
                    cgt.FechaTurnadoEntrega,
                    cgt.AnalistaID,
                    r.ID_REMESA,
                    r.DepartamentoTurnado,
                    r.FechaRemesa,
                    r.NumeroRemesa,
                    r.NumeroConsecutivo,
                    r.FolioIntegra,
                    r.OficioPeticion,
                    r.Beneficiario,
                    r.FechaPago,
                    r.FuenteFinanciamiento,
                    r.Documento,
                    r.Estatus AS EstatusRemesa,
                    r.Comentarios AS ComentariosRemesa,
                    r.FK_CONTRATO
                FROM 
                    ConsentradoGeneralTramites cgt
                LEFT JOIN 
                    Remesas r ON cgt.ID_CONTRATO = r.FK_CONTRATO
                    WHERE cgt.Estatus = 'Remesa'
                    OR cgt.Estatus = 'DevueltoOrdenesPago'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //Actualizar tramite y remesa
    public function updateTramiteRemesa($data)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria
            $fechaActual = date('Y-m-d H:i:s'); // Formato de fecha estándar

            // Iniciar transacción
            $this->conn->beginTransaction();

            // Obtener los comentarios anteriores de ConsentradoGeneralTramites
            $querySelect1 = "SELECT Comentarios FROM ConsentradoGeneralTramites WHERE ID_CONTRATO = :ID_CONTRATO";
            $stmtSelect1 = $this->conn->prepare($querySelect1);
            $stmtSelect1->bindParam(':ID_CONTRATO', $data['ID_CONTRATO']);
            $stmtSelect1->execute();
            $comentariosAnteriores1 = $stmtSelect1->fetchColumn();

            // Obtener los comentarios anteriores de Remesas
            $querySelect2 = "SELECT Comentarios FROM Remesas WHERE FK_CONTRATO = :ID_CONTRATO";
            $stmtSelect2 = $this->conn->prepare($querySelect2);
            $stmtSelect2->bindParam(':ID_CONTRATO', $data['ID_CONTRATO']);
            $stmtSelect2->execute();
            $comentariosAnteriores2 = $stmtSelect2->fetchColumn();

            // Crear el nuevo comentario en formato JSON
            $nuevoComentario = [
                "Fecha" => $fechaActual,
                "Modificado_Por" => $data['Analista'],
                "Estatus" => $data['Estatus'],
                "Comentario" => $data['Comentarios']
            ];

            // Acumular comentarios en formato JSON
            $comentariosArray1 = !empty($comentariosAnteriores1) ? json_decode($comentariosAnteriores1, true) : [];
            if (!is_array($comentariosArray1)) {
                $comentariosArray1 = [];
            }
            $comentariosArray1[] = $nuevoComentario;
            $comentariosActualizados1 = json_encode($comentariosArray1, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $comentariosArray2 = !empty($comentariosAnteriores2) ? json_decode($comentariosAnteriores2, true) : [];
            if (!is_array($comentariosArray2)) {
                $comentariosArray2 = [];
            }
            $comentariosArray2[] = $nuevoComentario;
            $comentariosActualizados2 = json_encode($comentariosArray2, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            // Actualizar ConsentradoGeneralTramites
            $query1 = "UPDATE ConsentradoGeneralTramites SET Estatus = :Estatus, Comentarios = :Comentarios WHERE ID_CONTRATO = :ID_CONTRATO";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->bindParam(':Estatus', $data['Estatus']);
            $stmt1->bindParam(':Comentarios', $comentariosActualizados1);
            $stmt1->bindParam(':ID_CONTRATO', $data['ID_CONTRATO']);
            $stmt1->execute();

            // Actualizar Remesas
            $query2 = "UPDATE Remesas SET Estatus = :Estatus, Comentarios = :Comentarios WHERE FK_CONTRATO = :ID_CONTRATO";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->bindParam(':Estatus', $data['Estatus']);
            $stmt2->bindParam(':Comentarios', $comentariosActualizados2);
            $stmt2->bindParam(':ID_CONTRATO', $data['ID_CONTRATO']);
            $stmt2->execute();

            // Confirmar transacción
            $this->conn->commit();

            return $stmt1->rowCount() + $stmt2->rowCount();
        } catch (PDOException $e) {
            // Revertir transacción en caso de error
            $this->conn->rollBack();
            throw new Exception("Error al actualizar trámite y remesa: " . $e->getMessage());
        }
    }
}
