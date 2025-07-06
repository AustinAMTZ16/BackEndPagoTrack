<?php
include_once 'app/config/Database.php';

class SuficienciaModel {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->conn;
    }
    // Función para obtener las Suficiencias
    public function getSuficiencias(){
        try{
            // Consulta de totales
            $query = "SELECT SuficienciasID,PersonaQuienSolicita,Dependencia,CentroGestor,AreaFuncional,NoOficioSolicitud,FechaSolicitud,FechaRecepcion,NoOficioContestacion,Tipo,FechaContestacion,SolpedCompromisoGasto,ServicioSolicitado,FuenteFinanciamiento,PosPrecog,Requisito,Concepto,MontoSuficienciaPresupuestal2024,Conac,Cuenta,Folio,Foja,NoticiaAdministrativa,NoContabilizarConsecutivo,ConsecutivoMes,Mes,Observaciones FROM Suficiencias ORDER BY FechaRecepcion DESC;";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(PDOException $e){
            // Respuesta de error en caso de fallo
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para registrar una Suficiencia
    public function registrarSuficiencia($data){
        try{
            // Validar los datos de entrada si no vienen mandar null
            $data['PersonaQuienSolicita'] = isset($data['PersonaQuienSolicita']) && $data['PersonaQuienSolicita'] !== '' ? $data['PersonaQuienSolicita'] : NULL;
            $data['Dependencia'] = isset($data['Dependencia']) && $data['Dependencia'] !== '' ? $data['Dependencia'] : NULL;
            $data['CentroGestor'] = isset($data['CentroGestor']) && $data['CentroGestor'] !== '' ? $data['CentroGestor'] : NULL;
            $data['AreaFuncional'] = isset($data['AreaFuncional']) && $data['AreaFuncional'] !== '' ? $data['AreaFuncional'] : NULL;
            $data['NoOficioSolicitud'] = isset($data['NoOficioSolicitud']) && $data['NoOficioSolicitud'] !== '' ? $data['NoOficioSolicitud'] : NULL;
            $data['FechaSolicitud'] = isset($data['FechaSolicitud']) && $data['FechaSolicitud'] !== '' ? $data['FechaSolicitud'] : NULL;
            $data['FechaRecepcion'] = isset($data['FechaRecepcion']) && $data['FechaRecepcion'] !== '' ? $data['FechaRecepcion'] : NULL;
            $data['NoOficioContestacion'] = isset($data['NoOficioContestacion']) && $data['NoOficioContestacion'] !== '' ? $data['NoOficioContestacion'] : NULL;
            $data['Tipo'] = isset($data['Tipo']) && $data['Tipo'] !== '' ? $data['Tipo'] : NULL;
            $data['FechaContestacion'] = isset($data['FechaContestacion']) && $data['FechaContestacion'] !== '' ? $data['FechaContestacion'] : NULL;
            $data['SolpedCompromisoGasto'] = isset($data['SolpedCompromisoGasto']) && $data['SolpedCompromisoGasto'] !== '' ? $data['SolpedCompromisoGasto'] : NULL;    
            $data['ServicioSolicitado'] = isset($data['ServicioSolicitado']) && $data['ServicioSolicitado'] !== '' ? $data['ServicioSolicitado'] : NULL;
            $data['FuenteFinanciamiento'] = isset($data['FuenteFinanciamiento']) && $data['FuenteFinanciamiento'] !== '' ? $data['FuenteFinanciamiento'] : NULL;
            $data['PosPrecog'] = isset($data['PosPrecog']) && $data['PosPrecog'] !== '' ? $data['PosPrecog'] : NULL;
            $data['Requisito'] = isset($data['Requisito']) && $data['Requisito'] !== '' ? $data['Requisito'] : NULL;
            $data['Concepto'] = isset($data['Concepto']) && $data['Concepto'] !== '' ? $data['Concepto'] : NULL;
            $data['MontoSuficienciaPresupuestal2024'] = isset($data['MontoSuficienciaPresupuestal2024']) && $data['MontoSuficienciaPresupuestal2024'] !== '' ? $data['MontoSuficienciaPresupuestal2024'] : NULL;
            $data['Conac'] = isset($data['Conac']) && $data['Conac'] !== '' ? $data['Conac'] : NULL;
            $data['Cuenta'] = isset($data['Cuenta']) && $data['Cuenta'] !== '' ? $data['Cuenta'] : NULL;
            $data['Folio'] = isset($data['Folio']) && $data['Folio'] !== '' ? $data['Folio'] : NULL;
            $data['Foja'] = isset($data['Foja']) && $data['Foja'] !== '' ? $data['Foja'] : NULL;
            $data['NoticiaAdministrativa'] = isset($data['NoticiaAdministrativa']) && $data['NoticiaAdministrativa'] !== '' ? $data['NoticiaAdministrativa'] : NULL;
            $data['NoContabilizarConsecutivo'] = isset($data['NoContabilizarConsecutivo']) && $data['NoContabilizarConsecutivo'] !== '' ? $data['NoContabilizarConsecutivo'] : NULL;
            $data['ConsecutivoMes'] = isset($data['ConsecutivoMes']) && $data['ConsecutivoMes'] !== '' ? $data['ConsecutivoMes'] : NULL;
            $data['Mes'] = isset($data['Mes']) && $data['Mes'] !== '' ? $data['Mes'] : NULL;
            $data['Observaciones'] = isset($data['Observaciones']) && $data['Observaciones'] !== '' ? $data['Observaciones'] : NULL;

            // Consulta de inserción
            $query = "INSERT INTO Suficiencias (PersonaQuienSolicita,Dependencia,CentroGestor,AreaFuncional,NoOficioSolicitud,FechaSolicitud,FechaRecepcion,NoOficioContestacion,Tipo,FechaContestacion,SolpedCompromisoGasto,ServicioSolicitado,FuenteFinanciamiento,PosPrecog,Requisito,Concepto,MontoSuficienciaPresupuestal2024,Conac,Cuenta,Folio,Foja,NoticiaAdministrativa,NoContabilizarConsecutivo,ConsecutivoMes,Mes,Observaciones) 
            VALUES (:PersonaQuienSolicita,:Dependencia,:CentroGestor,:AreaFuncional,:NoOficioSolicitud,:FechaSolicitud,:FechaRecepcion,:NoOficioContestacion,:Tipo,:FechaContestacion,:SolpedCompromisoGasto,:ServicioSolicitado,:FuenteFinanciamiento,:PosPrecog,:Requisito,:Concepto,:MontoSuficienciaPresupuestal2024,:Conac,:Cuenta,:Folio,:Foja,:NoticiaAdministrativa,:NoContabilizarConsecutivo,:ConsecutivoMes,:Mes,:Observaciones);";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':PersonaQuienSolicita', $data['PersonaQuienSolicita']);
            $stmt->bindParam(':Dependencia', $data['Dependencia']);
            $stmt->bindParam(':CentroGestor', $data['CentroGestor']);
            $stmt->bindParam(':AreaFuncional', $data['AreaFuncional']);
            $stmt->bindParam(':NoOficioSolicitud', $data['NoOficioSolicitud']);
            $stmt->bindParam(':FechaSolicitud', $data['FechaSolicitud']);
            $stmt->bindParam(':FechaRecepcion', $data['FechaRecepcion']);
            $stmt->bindParam(':NoOficioContestacion', $data['NoOficioContestacion']);
            $stmt->bindParam(':Tipo', $data['Tipo']);
            $stmt->bindParam(':FechaContestacion', $data['FechaContestacion']);
            $stmt->bindParam(':SolpedCompromisoGasto', $data['SolpedCompromisoGasto']);
            $stmt->bindParam(':ServicioSolicitado', $data['ServicioSolicitado']);
            $stmt->bindParam(':FuenteFinanciamiento', $data['FuenteFinanciamiento']);   
            $stmt->bindParam(':PosPrecog', $data['PosPrecog']);
            $stmt->bindParam(':Requisito', $data['Requisito']);
            $stmt->bindParam(':Concepto', $data['Concepto']);
            $stmt->bindParam(':MontoSuficienciaPresupuestal2024', $data['MontoSuficienciaPresupuestal2024']);
            $stmt->bindParam(':Conac', $data['Conac']);
            $stmt->bindParam(':Cuenta', $data['Cuenta']);
            $stmt->bindParam(':Folio', $data['Folio']);
            $stmt->bindParam(':Foja', $data['Foja']);
            $stmt->bindParam(':NoticiaAdministrativa', $data['NoticiaAdministrativa']);
            $stmt->bindParam(':NoContabilizarConsecutivo', $data['NoContabilizarConsecutivo']);
            $stmt->bindParam(':ConsecutivoMes', $data['ConsecutivoMes']);
            $stmt->bindParam(':Mes', $data['Mes']);
            $stmt->bindParam(':Observaciones', $data['Observaciones']);

            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            } else {
                throw new Exception("Error al registrar el trámite.");
            }
        } catch (PDOException $e) {
            throw new Exception("Error al registrar el trámite: " . $e->getMessage());
        }
    }
    // Función para actulizar una suficiencia
    public function actualizarSuficiencia($data) {
        try {
            // Verificar si el registro existe
            $query = "SELECT * FROM Suficiencias WHERE SuficienciasID = :SuficienciasID";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':SuficienciasID', $data['SuficienciasID']);
            $stmt->execute();
            $registroActual = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$registroActual) {
                throw new Exception("No se encontró el registro con ID " . $data['SuficienciasID']);
            }
    
            // Depuración: Imprimir valores antes de la comparación
            error_log("Valores actuales: " . json_encode($registroActual));
            error_log("Valores nuevos: " . json_encode($data));
    
            // Comparar valores actuales con nuevos datos
            $camposActualizar = [];
            $parametros = [];
    
            foreach ($data as $campo => $valor) {
                if ($campo !== 'SuficienciasID' && array_key_exists($campo, $registroActual)) {
                    // Convertir a string para comparar correctamente
                    $valorActual = (string) ($registroActual[$campo] ?? '');
                    $valorNuevo = (string) ($valor ?? '');
    
                    if ($valorActual !== $valorNuevo) {  // Comparación más precisa
                        $camposActualizar[] = "$campo = :$campo";
                        $parametros[":$campo"] = $valor !== '' ? $valor : null;
                    }
                }
            }
    
            // Si no hay cambios detectados, mostrar valores y lanzar excepción
            if (empty($camposActualizar)) {
                throw new Exception("No hay cambios para actualizar.");
            }
    
            // Construcción del SQL dinámico
            $sql = "UPDATE Suficiencias SET " . implode(", ", $camposActualizar) . " WHERE SuficienciasID = :SuficienciasID";
            $parametros[':SuficienciasID'] = $data['SuficienciasID'];
    
            $stmt = $this->conn->prepare($sql);
            if ($stmt->execute($parametros)) {
                return "Registro actualizado correctamente.";
            } else {
                throw new Exception("Error al actualizar la suficiencia.");
            }
    
        } catch (PDOException $e) {
            throw new Exception("Error en la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    // Función para eliminar una suficiencia
    public function eliminarSuficiencia($data){
        $query = "DELETE FROM Suficiencias WHERE SuficienciasID = :SuficienciasID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':SuficienciasID', $data['SuficienciasID']);
        $stmt->execute();
        $filasAfectadas = $stmt->rowCount();
        if ($filasAfectadas > 0) {
            return true;
        } else {
            return false;
        }
    }
}
?>


