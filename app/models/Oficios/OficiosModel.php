<?php
include_once 'app/config/Database.php';

class OficiosModel
{
    // Tablas RegistroOficios
    // 'TipoOficio',
    // 'NumeroOficio',
    // 'FechaRetroactivo',
    // 'DirigidoA',
    // 'Asunto',
    // 'Institucion',
    // 'Solicita',
    // 'FolioInterno',
    // 'Estado',
    // 'FechaEntregaDGAnalista',
    // 'Concepto',
    // 'RespuestaA',
    // 'Monto',
    // 'FechaRecepcionDependencia',
    // 'FechaEntregaAnalistaOperador',
    // 'Comentario',
    // 'ArchivoAdjunto',
    // 'UsuarioRegistro',
    // 'FechaActualizacion'


    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->conn;
    }
    // Función para obtener los oficios
    public function listarRegistroOficios()
    {
        try {
            // Consulta de totales
            $query = "SELECT 
                            ro.*,         
                            COALESCE(CONCAT(isT.NombreUser, ' ', isT.ApellidoUser), 'N/A') AS TurnadoNombreCompleto,
                            COALESCE(CONCAT(isU.NombreUser, ' ', isU.ApellidoUser), 'N/A') AS UsuarioRegistroNombreCompleto
                        FROM RegistroOficios ro
                        LEFT JOIN InicioSesion isT ON ro.Solicita = isT.InicioSesionID
                        LEFT JOIN InicioSesion isU ON ro.UsuarioRegistro = isU.InicioSesionID
                        ORDER BY ro.FechaRegistro DESC;
                        ";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } catch (PDOException $e) {
            // Respuesta de error en caso de fallo
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para crear un oficio
    public function crearRegistroOficio($data)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
            $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL
            // Crear historial de comentarios en formato JSON
            $comentariosArray = [];

            if (!empty($data['Comentario'])) {
                $comentarioInicial = [
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'],
                    "Usuario" => $data['UsuarioRegistro'],
                    "Comentario" => $data['Comentario']
                ];
                $comentariosArray[] = $comentarioInicial;
            }
            $jsonComentarios = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE);


            // Consulta con campos completos
            $query = "INSERT INTO RegistroOficios (
                TipoOficio,
                NumeroOficio,
                FechaRetroactivo,
                DirigidoA,
                Asunto,
                Institucion,
                Solicita,
                FolioInterno,
                Estado,
                FechaEntregaDGAnalista,
                Concepto,
                RespuestaA,
                Monto,
                FechaRecepcionDependencia,
                FechaEntregaAnalistaOperador,
                Comentario,
                ArchivoAdjunto,
                UsuarioRegistro
            ) VALUES (
                :TipoOficio,
                :NumeroOficio,
                :FechaRetroactivo,
                :DirigidoA,
                :Asunto,
                :Institucion,
                :Solicita,
                :FolioInterno,
                :Estado,
                :FechaEntregaDGAnalista,
                :Concepto,
                :RespuestaA,
                :Monto,
                :FechaRecepcionDependencia,
                :FechaEntregaAnalistaOperador,
                :Comentario,
                :ArchivoAdjunto,
                :UsuarioRegistro
            )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(':TipoOficio', isset($data['TipoOficio']) && trim($data['TipoOficio']) !== '' ? $data['TipoOficio'] : 'Oficios');
            $stmt->bindValue(':NumeroOficio', isset($data['NumeroOficio']) && trim($data['NumeroOficio']) !== '' ? $data['NumeroOficio'] : 'N/A');
            $stmt->bindValue(':FechaRetroactivo', isset($data['FechaRetroactivo']) && trim($data['FechaRetroactivo']) !== '' ? $data['FechaRetroactivo'] : $fechaActual);
            $stmt->bindValue(':DirigidoA', isset($data['DirigidoA']) && trim($data['DirigidoA']) !== '' ? $data['DirigidoA'] : 'N/A');
            $stmt->bindValue(':Asunto', isset($data['Asunto']) && trim($data['Asunto']) !== '' ? $data['Asunto'] : 'N/A');
            $stmt->bindValue(':Institucion', isset($data['Institucion']) && trim($data['Institucion']) !== '' ? $data['Institucion'] : 'N/A');
            $stmt->bindValue(':Solicita', isset($data['Solicita']) && trim($data['Solicita']) !== '' ? $data['Solicita'] : 'N/A');
            $stmt->bindValue(':FolioInterno', isset($data['FolioInterno']) && trim($data['FolioInterno']) !== '' ? $data['FolioInterno'] : 'N/A');
            $stmt->bindValue(':Estado', isset($data['Estado']) && trim($data['Estado']) !== '' ? $data['Estado'] : 'Registrado');
            $stmt->bindValue(':FechaEntregaDGAnalista', isset($data['FechaEntregaDGAnalista']) && trim($data['FechaEntregaDGAnalista']) !== '' ? $data['FechaEntregaDGAnalista'] : $fechaActual);
            $stmt->bindValue(':Concepto', isset($data['Concepto']) && trim($data['Concepto']) !== '' ? $data['Concepto'] : 'N/A');
            $stmt->bindValue(':RespuestaA', isset($data['RespuestaA']) && trim($data['RespuestaA']) !== '' ? $data['RespuestaA'] : 'N/A');
            $stmt->bindValue(':Monto', isset($data['Monto']) && trim($data['Monto']) !== '' ? $data['Monto'] : 0.00);
            $stmt->bindValue(':FechaRecepcionDependencia', isset($data['FechaRecepcionDependencia']) && trim($data['FechaRecepcionDependencia']) !== '' ? $data['FechaRecepcionDependencia'] : $fechaActual);
            $stmt->bindValue(':FechaEntregaAnalistaOperador', isset($data['FechaEntregaAnalistaOperador']) && trim($data['FechaEntregaAnalistaOperador']) !== '' ? $data['FechaEntregaAnalistaOperador'] : $fechaActual);
            $stmt->bindValue(':Comentario', isset($data['Comentario']) && trim($data['Comentario']) !== '' ? $data['Comentario'] : '[]');
            $stmt->bindValue(':ArchivoAdjunto', isset($data['ArchivoAdjunto']) && trim($data['ArchivoAdjunto']) !== '' ? $data['ArchivoAdjunto'] : 'N/A');
            $stmt->bindValue(':UsuarioRegistro', isset($data['UsuarioRegistro']) && trim($data['UsuarioRegistro']) !== '' ? $data['UsuarioRegistro'] : 'N/A');

            $stmt->execute();

            return json_encode(["message" => "Oficio creado correctamente"]);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para actualizar un oficio
    public function actualizarRegistroOficio($data)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
            $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL
            $data['FechaActualizacion'] = $fechaActual;
            
            // Obtener comentarios actuales si existen
            $queryComentarios = "SELECT Comentario FROM RegistroOficios WHERE ID_RegistroOficios = :ID_RegistroOficios";
            $stmtComentarios = $this->conn->prepare($queryComentarios);
            $stmtComentarios->bindValue(':ID_RegistroOficios', $data['ID_RegistroOficios']);
            $stmtComentarios->execute();
            $resultComentarios = $stmtComentarios->fetch(PDO::FETCH_ASSOC);

            $comentariosActuales = [];
            if (!empty($resultComentarios['Comentario'])) {
                $comentariosActuales = json_decode($resultComentarios['Comentario'], true);
            }

            if (!empty($data['Comentario'])) {
                $nuevoComentario = [
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'] ?? '',
                    "Usuario" => $data['UsuarioRegistro'] ?? '',
                    "Comentario" => $data['Comentario']
                ];
                $comentariosActuales[] = $nuevoComentario;
                $data['Comentario'] = json_encode($comentariosActuales, JSON_UNESCAPED_UNICODE);
            }

            $camposValidos = [                
                'TipoOficio',
                'NumeroOficio',
                'FechaRetroactivo',
                'DirigidoA',
                'Asunto',
                'Institucion',
                'Solicita',
                'FolioInterno',
                'Estado',
                'FechaEntregaDGAnalista',
                'Concepto',
                'RespuestaA',
                'Monto',
                'FechaRecepcionDependencia',
                'FechaEntregaAnalistaOperador',
                'Comentario',
                'ArchivoAdjunto',
                'UsuarioRegistro',
                'FechaActualizacion'
            ];

            if (!isset($data['ID_RegistroOficios'])) {
                http_response_code(400);
                return json_encode(["error" => "Falta el ID del oficio a actualizar"]);
            }

            $setClauses = [];
            $params = [];

            foreach ($camposValidos as $campo) {
                if (isset($data[$campo])) {
                    $setClauses[] = "$campo = :$campo";
                    $params[$campo] = $data[$campo];
                }
            }

            if (empty($setClauses)) {
                return json_encode(["message" => "No se proporcionaron campos para actualizar"]);
            }

            $setSQL = implode(", ", $setClauses);
            $query = "UPDATE RegistroOficios SET $setSQL WHERE ID_RegistroOficios = :ID_RegistroOficios";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $campo => $valor) {
                $stmt->bindValue(":$campo", $valor);
            }

            $stmt->bindValue(":ID_RegistroOficios", $data['ID_RegistroOficios']);
            $stmt->execute();

            $rowsAffected = $stmt->rowCount();

            if ($rowsAffected > 0) {
                return json_encode(["message" => "Oficio actualizado correctamente", "rowsAffected" => $rowsAffected]);
            } else {
                return json_encode(["message" => "No se realizaron cambios", "rowsAffected" => 0]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para eliminar un oficio
    public function eliminarRegistroOficio($data) {
        try {
            $query = "DELETE FROM RegistroOficios WHERE ID_RegistroOficios = :ID_RegistroOficios";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ID_RegistroOficios', $data['ID_RegistroOficios']);
            $stmt->execute();
            
            $rowsAffected = $stmt->rowCount();
            
            // Devuelve array directamente (NO json_encode)
            if ($rowsAffected > 0) {
                return [
                    "success" => true,
                    "message" => "Registro eliminado correctamente",
                    "rowsAffected" => $rowsAffected
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "No se encontró el registro a eliminar",
                    "rowsAffected" => 0
                ];
            }
        } catch (PDOException $e) {
            // Devuelve array para errores también
            return [
                "success" => false,
                "error" => "Error en la consulta: " . $e->getMessage()
            ];
        }
    }
    // Función para actualizar un oficio con archivo
    public function actualizarRegistroOficioArchivo($data, $archivos)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
            $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL
            $data['FechaActualizacion'] = $fechaActual;
            
            // ✅ 1. Validar y mover archivo si viene
            if (isset($archivos['Archivo']) && $archivos['Archivo']['error'] === UPLOAD_ERR_OK) {
                $archivo = $archivos['Archivo'];

                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $nombreArchivoFinal = $data['ID_RegistroOficios'] . '.' . $extension;
                $rutaDestino = __DIR__ . '/../../../assets/uploads/oficios/' . $nombreArchivoFinal;

                if (!is_dir(dirname($rutaDestino))) {
                    mkdir(dirname($rutaDestino), 0777, true);
                }

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $data['ArchivoAdjunto'] = $nombreArchivoFinal;
                } else {
                    return json_encode([
                        "error" => "No se pudo mover el archivo al directorio destino."
                    ]);
                }
            }

            // ✅ 2. Acumulación de comentarios
            if (!empty($data['Comentario'])) {
                // Obtener comentarios actuales
                $stmtComentarios = $this->conn->prepare("SELECT Comentario FROM RegistroOficios WHERE ID_RegistroOficios = :ID_RegistroOficios");
                $stmtComentarios->bindValue(":ID_RegistroOficios", $data['ID_RegistroOficios']);
                $stmtComentarios->execute();
                $resultado = $stmtComentarios->fetch(PDO::FETCH_ASSOC);

                $comentariosPrevios = [];
                if (!empty($resultado['Comentario'])) {
                    $comentariosPrevios = json_decode($resultado['Comentario'], true);
                    if (!is_array($comentariosPrevios)) {
                        $comentariosPrevios = [];
                    }
                }

                // Agregar nuevo comentario al historial
                $comentarioNuevo = [
                    "ID_OFICIO" => $data['ID_RegistroOficios'],
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'] ?? "ACTUALIZACIÓN",
                    "Usuario" => $data['UsuarioRegistro'] ?? "N/A",
                    "Comentario" => $data['Comentario']
                ];
                $comentariosPrevios[] = $comentarioNuevo;
                $data['Comentario'] = json_encode($comentariosPrevios, JSON_UNESCAPED_UNICODE);
            }

            // ✅ 3. Preparar UPDATE dinámico
            $camposValidos = [
                'TipoOficio',
                'NumeroOficio',
                'FechaRetroactivo',
                'DirigidoA',
                'Asunto',
                'Institucion',
                'Solicita',
                'FolioInterno',
                'Estado',
                'FechaEntregaDGAnalista',
                'Concepto',
                'RespuestaA',
                'Monto',
                'FechaRecepcionDependencia',
                'FechaEntregaAnalistaOperador',
                'Comentario',
                'ArchivoAdjunto',
                'UsuarioRegistro',
                'FechaActualizacion'
            ];

            if (!isset($data['ID_RegistroOficios'])) {
                http_response_code(400);
                return json_encode(["error" => "Falta el ID del oficio a actualizar"]);
            }

            $setClauses = [];
            $params = [];

            foreach ($camposValidos as $campo) {
                if (isset($data[$campo])) {
                    $setClauses[] = "$campo = :$campo";
                    $params[$campo] = $data[$campo];
                }
            }

            if (empty($setClauses)) {
                return json_encode(["message" => "No se proporcionaron campos para actualizar"]);
            }

            $setSQL = implode(", ", $setClauses);
            $query = "UPDATE RegistroOficios SET $setSQL WHERE ID_RegistroOficios = :ID_RegistroOficios";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $campo => $valor) {
                $stmt->bindValue(":$campo", $valor);
            }
            $stmt->bindValue(":ID_RegistroOficios", $data['ID_RegistroOficios']);
            $stmt->execute();

            $rowsAffected = $stmt->rowCount();

            return json_encode([
                "message" => $rowsAffected > 0 ? "Oficio actualizado correctamente" : "No se realizaron cambios",
                "rowsAffected" => $rowsAffected,
                "archivo" => $data['ArchivoAdjunto'] ?? null
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
}
