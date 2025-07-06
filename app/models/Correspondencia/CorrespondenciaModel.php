<?php
include_once 'app/config/Database.php';

class CorrespondenciaModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->conn;
    }
    // Función para obtener los oficios
    public function listarOficios()
    {
        try {
            // Consulta de totales
            $query = "SELECT
                            c.*,         
                            COALESCE(CONCAT(isT.NombreUser, ' ', isT.ApellidoUser), 'N/A') AS TurnadoNombreCompleto,
                            COALESCE(CONCAT(isU.NombreUser, ' ', isU.ApellidoUser), 'N/A') AS UsuarioRegistroNombreCompleto
                        FROM Correspondencia c
                        LEFT JOIN InicioSesion isT ON c.Turnado = isT.InicioSesionID
                        LEFT JOIN InicioSesion isU ON c.UsuarioRegistro = isU.InicioSesionID
                        ORDER BY c.FechaCreacion DESC;
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
    public function crearOficio($data)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
            $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL
            // Crear historial de comentarios en formato JSON
            $comentariosArray = [];
            if (!empty($data['Comentarios'])) {
                $comentarioInicial = [
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'],
                    "Usuario" => $data['UsuarioRegistro'],
                    "Comentario" => $data['Comentarios']
                ];
                $comentariosArray[] = $comentarioInicial;
            }
            $jsonComentarios = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE);


            // Consulta con campos completos
            $query = "INSERT INTO Correspondencia (
                Folio,
                Solicitante,
                Dependencia,
                Departamento,
                NumeroOficio,
                tipoOficio,
                Asunto,
                Concepto,
                Monto,
                FechaVencimiento,
                Turnado,
                FechaRetroactiva,
                Estado,
                UsuarioRegistro,
                Comentarios,
                FechaRecepcion
            ) VALUES (
                :Folio,
                :Solicitante,
                :Dependencia,
                :Departamento,
                :NumeroOficio,
                :tipoOficio,
                :Asunto,
                :Concepto,
                :Monto,
                :FechaVencimiento,
                :Turnado,
                :FechaRetroactiva,
                :Estado,
                :UsuarioRegistro,
                :Comentarios,
                :FechaRecepcion
            )";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':Folio', $data['Folio']);
            $stmt->bindParam(':Solicitante', $data['Solicitante']);
            $stmt->bindParam(':Dependencia', $data['Dependencia']);
            $stmt->bindParam(':Departamento', $data['Departamento']);
            $stmt->bindParam(':NumeroOficio', $data['NumeroOficio']);
            $stmt->bindParam(':tipoOficio', $data['tipoOficio']);
            $stmt->bindParam(':Asunto', $data['Asunto']);
            $stmt->bindParam(':Concepto', $data['Concepto']);
            $stmt->bindParam(':Monto', $data['Monto']);
            $stmt->bindParam(':FechaVencimiento', $data['FechaVencimiento']);
            $stmt->bindParam(':Turnado', $data['Turnado']);
            $stmt->bindParam(':FechaRetroactiva', $data['FechaRetroactiva']);
            $stmt->bindParam(':Estado', $data['Estado']);
            $stmt->bindParam(':UsuarioRegistro', $data['UsuarioRegistro']);
            $stmt->bindParam(':Comentarios', $jsonComentarios);
            $stmt->bindParam(':FechaRecepcion', $data['FechaRecepcion']);
            $stmt->execute();

            return json_encode(["message" => "Oficio creado correctamente"]);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para actualizar un oficio
    public function actualizarOficio($data)
    {
        try {
            date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria de México
            $fechaActual = date('Y-m-d H:i:s'); // Obtener fecha y hora actual en formato MySQL
            // Obtener comentarios actuales si existen
            $queryComentarios = "SELECT Comentarios FROM Correspondencia WHERE ID = :ID";
            $stmtComentarios = $this->conn->prepare($queryComentarios);
            $stmtComentarios->bindValue(':ID', $data['ID']);
            $stmtComentarios->execute();
            $resultComentarios = $stmtComentarios->fetch(PDO::FETCH_ASSOC);

            $comentariosActuales = [];
            if (!empty($resultComentarios['Comentarios'])) {
                $comentariosActuales = json_decode($resultComentarios['Comentarios'], true);
            }

            if (!empty($data['Comentarios'])) {
                $nuevoComentario = [
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'] ?? '',
                    "Usuario" => $data['UsuarioRegistro'] ?? '',
                    "Comentario" => $data['Comentarios']
                ];
                $comentariosActuales[] = $nuevoComentario;
                $data['Comentarios'] = json_encode($comentariosActuales, JSON_UNESCAPED_UNICODE);
            }

            $camposValidos = [
                'Folio',
                'FechaRecepcion',
                'Solicitante',
                'Dependencia',
                'Departamento',
                'NumeroOficio',
                'tipoOficio',
                'Asunto',
                'Concepto',
                'Monto',
                'FechaVencimiento',
                'Turnado',
                'RespuestaConocimiento',
                'FechaRetroactiva',
                'Estado',
                'UsuarioRegistro',
                'Comentarios',
                'ArchivoScaneado',
                'FechaEntregaAcuse'
            ];

            if (!isset($data['ID'])) {
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
            $query = "UPDATE Correspondencia SET $setSQL WHERE ID = :ID";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $campo => $valor) {
                $stmt->bindValue(":$campo", $valor);
            }

            $stmt->bindValue(":ID", $data['ID']);
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
    public function eliminarOficio($data)
    {
        try {
            $query = "DELETE FROM Correspondencia WHERE ID = :ID";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ID', $data['ID']);
            $stmt->execute();
            $rowsAffected = $stmt->rowCount();
            if ($rowsAffected > 0) {
                return json_encode(["message" => "Oficio eliminado correctamente", "rowsAffected" => $rowsAffected]);
            } else {
                return json_encode(["message" => "No se realizó ninguna eliminación", "rowsAffected" => 0]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
    // Función para actualizar un oficio con archivo
    public function actualizarOficioArchivo($data, $archivos)
    {
        try {
            // Configuración inicial
            date_default_timezone_set('America/Mexico_City');
            $fechaActual = date('Y-m-d H:i:s');

            // Validar ID obligatorio
            if (!isset($data['ID']) || empty($data['ID'])) {
                http_response_code(400);
                return json_encode(["error" => "El ID del oficio es requerido"]);
            }

            // ✅ 1. Manejo de archivos adjuntos
            if (isset($archivos['Archivo']) && $archivos['Archivo']['error'] === UPLOAD_ERR_OK) {
                $archivo = $archivos['Archivo'];

                // Validar tipo de archivo
                $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $extensionesPermitidas)) {
                    return json_encode([
                        "error" => "Tipo de archivo no permitido. Solo se aceptan: " . implode(', ', $extensionesPermitidas)
                    ]);
                }

                // Generar nombre único para el archivo
                $nombreArchivoFinal = $data['ID'] . '_' . time() . '.' . $extension;
                $rutaDestino = __DIR__ . '/../../../assets/uploads/Correspondencia/' . $nombreArchivoFinal;

                // Crear directorio si no existe
                if (!is_dir(dirname($rutaDestino))) {
                    mkdir(dirname($rutaDestino), 0777, true);
                }

                // Mover archivo
                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    $data['ArchivoScaneado'] = $nombreArchivoFinal;

                    // Eliminar archivo anterior si existe
                    $this->eliminarArchivoAnterior($data['ID']);
                } else {
                    return json_encode([
                        "error" => "No se pudo guardar el archivo en el servidor"
                    ]);
                }
            }

            // ✅ 2. Configuración de campos con valores por defecto
            $camposValidos = [
                'Folio' => ['tipo' => 'string', 'requerido' => false],
                'FechaRecepcion' => ['tipo' => 'datetime', 'requerido' => false],
                'Solicitante' => ['tipo' => 'string', 'requerido' => false],
                'Dependencia' => ['tipo' => 'string', 'requerido' => false],
                'Departamento' => ['tipo' => 'string', 'requerido' => false],
                'NumeroOficio' => ['tipo' => 'string', 'requerido' => false],
                'tipoOficio' => ['tipo' => 'enum', 'requerido' => false],
                'Asunto' => ['tipo' => 'string', 'requerido' => false],
                'Concepto' => ['tipo' => 'string', 'requerido' => false],
                'Monto' => ['tipo' => 'decimal', 'requerido' => false],
                'FechaVencimiento' => ['tipo' => 'date', 'requerido' => false],
                'Turnado' => ['tipo' => 'string', 'requerido' => false],
                'RespuestaConocimiento' => ['tipo' => 'string', 'requerido' => false],
                'FechaRetroactiva' => ['tipo' => 'date', 'requerido' => false],
                'Estado' => ['tipo' => 'enum', 'requerido' => true],
                'UsuarioRegistro' => ['tipo' => 'string', 'requerido' => false],
                'Comentarios' => ['tipo' => 'json', 'requerido' => false],
                'ArchivoScaneado' => ['tipo' => 'string', 'requerido' => false],
                'FechaEntregaAcuse' => ['tipo' => 'date', 'requerido' => false]
            ];

            // ✅ 3. Validación de campos requeridos
            foreach ($camposValidos as $campo => $config) {
                if (
                    isset($config['requerido']) && $config['requerido'] &&
                    (!isset($data[$campo]) || empty($data[$campo]))
                ) {
                    http_response_code(400);
                    return json_encode(["error" => "El campo $campo es requerido"]);
                }
            }

            // ✅ 4. Normalización de fechas
            $camposFecha = ['FechaRecepcion', 'FechaVencimiento', 'FechaRetroactiva', 'FechaEntregaAcuse'];
            foreach ($camposFecha as $campo) {
                if (isset($data[$campo])) {
                    if ($data[$campo] === '' || $data[$campo] === null) {
                        $data[$campo] = null;
                    } else {
                        $date = DateTime::createFromFormat('Y-m-d\TH:i', $data[$campo])
                            ?: DateTime::createFromFormat('Y-m-d H:i:s', $data[$campo])
                            ?: DateTime::createFromFormat('Y-m-d', $data[$campo]);
                        $data[$campo] = $date ? $date->format('Y-m-d') : null;
                    }
                }
            }

            // ✅ 5. Manejo de comentarios (historial)
            if (!empty($data['Comentarios'])) {
                $stmtComentarios = $this->conn->prepare("SELECT Comentarios FROM Correspondencia WHERE ID = :ID");
                $stmtComentarios->bindValue(":ID", $data['ID']);
                $stmtComentarios->execute();
                $resultado = $stmtComentarios->fetch(PDO::FETCH_ASSOC);

                $comentariosPrevios = [];
                if (!empty($resultado['Comentarios'])) {
                    $comentariosPrevios = json_decode($resultado['Comentarios'], true);
                    if (!is_array($comentariosPrevios)) {
                        $comentariosPrevios = [];
                    }
                }

                $comentarioNuevo = [
                    "ID_OFICIO" => $data['ID'],
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'] ?? "ACTUALIZACIÓN",
                    "Usuario" => $data['UsuarioRegistro'] ?? "N/A",
                    "Comentario" => $data['Comentarios']
                ];

                $comentariosPrevios[] = $comentarioNuevo;
                $data['Comentarios'] = json_encode($comentariosPrevios, JSON_UNESCAPED_UNICODE);
            }

            // ✅ 6. Construcción dinámica del UPDATE
            $setClauses = [];
            $params = [];

            foreach ($camposValidos as $campo => $config) {
                if (array_key_exists($campo, $data)) {
                    $valor = $data[$campo];

                    // TRATAMIENTO ESPECÍFICO PARA RespuestaConocimiento Y OTROS STRINGS NO REQUERIDOS
                    // Si el campo es tipo 'string', no es requerido y el valor es una cadena vacía,
                    // lo convertimos a NULL explícitamente para la base de datos.
                    if (
                        $config['tipo'] === 'string' &&
                        isset($config['requerido']) && $config['requerido'] === false &&
                        (string)$valor === '' // Asegúrate de que es una cadena vacía, no solo un valor "falso"
                    ) {
                        $setClauses[] = "$campo = NULL";
                        // No añadimos nada a $params para este campo, ya que es NULL directo en el SQL
                        continue; // Pasa a la siguiente iteración del bucle
                    }


                    switch ($config['tipo']) {
                        case 'date':
                        case 'datetime':
                            if ($valor === null) {
                                $setClauses[] = "$campo = NULL";
                            } else {
                                $setClauses[] = "$campo = :$campo";
                                $params[$campo] = $valor;
                            }
                            break;

                        case 'decimal':
                            $setClauses[] = "$campo = :$campo";
                            $params[$campo] = is_numeric($valor) ? (float)$valor : 0.00;
                            break;

                        case 'json':
                            $setClauses[] = "$campo = :$campo";
                            // Si el valor de 'Comentarios' viene vacío y es un JSON, puedes querer que sea NULL también
                            // O si la lógica ya lo maneja para que no se envíe un JSON vacío.
                            // Aquí asumo que $data['Comentarios'] ya está correctamente formateado como JSON o vacio por tu lógica anterior.
                            $params[$campo] = is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor;
                            break;

                        default:
                            $setClauses[] = "$campo = :$campo";
                            $params[$campo] = $valor;
                    }
                }
            }

            if (empty($setClauses)) {
                return json_encode(["message" => "No se proporcionaron campos para actualizar"]);
            }

            // ✅ 7. Ejecución de la consulta
            $setSQL = implode(", ", $setClauses);
            $query = "UPDATE Correspondencia SET $setSQL WHERE ID = :ID";
            $params['ID'] = $data['ID'];

            $stmt = $this->conn->prepare($query);
            foreach ($params as $campo => $valor) {
                if ($valor === null && in_array($campo, $camposFecha)) {
                    $stmt->bindValue(":$campo", null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(":$campo", $valor);
                }
            }

            $stmt->execute();
            $rowsAffected = $stmt->rowCount();

            // ✅ 8. Respuesta final
            return json_encode([
                "success" => true,
                "message" => $rowsAffected > 0 ? "Oficio actualizado correctamente" : "No se realizaron cambios",
                "rowsAffected" => $rowsAffected,
                "archivo" => $data['ArchivoScaneado'] ?? null,
                "fechaActualizacion" => $fechaActual
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode([
                "success" => false,
                "error" => "Error en la base de datos",
                "details" => $e->getMessage(),
                "code" => $e->getCode()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode([
                "success" => false,
                "error" => "Error en el servidor",
                "details" => $e->getMessage()
            ]);
        }
    }
    // Función auxiliar para eliminar archivo anterior
    private function eliminarArchivoAnterior($idOficio)
    {
        try {
            $stmt = $this->conn->prepare("SELECT ArchivoScaneado FROM Correspondencia WHERE ID = :ID");
            $stmt->bindValue(":ID", $idOficio);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && !empty($result['ArchivoScaneado'])) {
                $rutaArchivo = __DIR__ . '/../../../assets/uploads/Correspondencia/' . $result['ArchivoScaneado'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
        } catch (Exception $e) {
            // No romper el flujo si falla la eliminación
            error_log("Error al eliminar archivo anterior: " . $e->getMessage());
        }
    }
    // Función para crear un oficio con archivo
    public function crearOficioArchivo($data, $Archivo)
    {
        try {
            date_default_timezone_set('America/Mexico_City');
            $fechaActual = date('Y-m-d H:i:s');

            // Crear historial de comentarios
            $comentariosArray = [];
            if (!empty($data['Comentarios'])) {
                $comentariosArray[] = [
                    "Fecha" => $fechaActual,
                    "Estatus" => $data['Estado'],
                    "Usuario" => $data['UsuarioRegistro'],
                    "Comentario" => $data['Comentarios']
                ];
            }
            $jsonComentarios = json_encode($comentariosArray, JSON_UNESCAPED_UNICODE);

            // Paso 1: Insertar el oficio SIN archivo
            $query = "INSERT INTO Correspondencia (
            Folio, Solicitante, Dependencia, Departamento, NumeroOficio,
            tipoOficio, Asunto, Concepto, Monto, FechaVencimiento,
            Turnado, FechaRetroactiva, Estado, UsuarioRegistro,
            Comentarios, FechaRecepcion
        ) VALUES (
            :Folio, :Solicitante, :Dependencia, :Departamento, :NumeroOficio,
            :tipoOficio, :Asunto, :Concepto, :Monto, :FechaVencimiento,
            :Turnado, :FechaRetroactiva, :Estado, :UsuarioRegistro,
            :Comentarios, :FechaRecepcion
        )";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':Folio', $data['Folio']);
            $stmt->bindParam(':Solicitante', $data['Solicitante']);
            $stmt->bindParam(':Dependencia', $data['Dependencia']);
            $stmt->bindParam(':Departamento', $data['Departamento']);
            $stmt->bindParam(':NumeroOficio', $data['NumeroOficio']);
            $stmt->bindParam(':tipoOficio', $data['tipoOficio']);
            $stmt->bindParam(':Asunto', $data['Asunto']);
            $stmt->bindParam(':Concepto', $data['Concepto']);
            $stmt->bindParam(':Monto', $data['Monto']);
            $stmt->bindParam(':FechaVencimiento', $data['FechaVencimiento']);
            $stmt->bindParam(':Turnado', $data['Turnado']);
            $stmt->bindParam(':FechaRetroactiva', $data['FechaRetroactiva']);
            $stmt->bindParam(':Estado', $data['Estado']);
            $stmt->bindParam(':UsuarioRegistro', $data['UsuarioRegistro']);
            $stmt->bindParam(':Comentarios', $jsonComentarios);
            $stmt->bindParam(':FechaRecepcion', $data['FechaRecepcion']);
            $stmt->execute();

            // Paso 2: Obtener ID recién insertado
            $nuevoID = $this->conn->lastInsertId();

            // Validar si el ID no se obtuvo correctamente
            if (!$nuevoID || $nuevoID == 0) {
                // Fallback: buscar por folio
                $stmtID = $this->conn->prepare("SELECT ID FROM Correspondencia WHERE Folio = :folio ORDER BY ID DESC LIMIT 1");
                $stmtID->bindParam(':folio', $data['Folio']);
                $stmtID->execute();
                $nuevoID = $stmtID->fetchColumn();
            }

            // Paso 2: Procesar y guardar el archivo, si existe
            $nombreArchivoFinal = null;
            if (isset($Archivo['Archivo']) && $Archivo['Archivo']['error'] === UPLOAD_ERR_OK) {
                $archivo = $Archivo['Archivo'];
                $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

                if (!in_array($extension, $extensionesPermitidas)) {
                    return json_encode([
                        "error" => "Tipo de archivo no permitido. Solo se aceptan: " . implode(', ', $extensionesPermitidas)
                    ]);
                }

                $timestamp = time();
                $nombreArchivoFinal = $nuevoID . '_' . $timestamp . '.' . $extension;
                $rutaDestino = __DIR__ . '/../../../assets/uploads/Correspondencia/' . $nombreArchivoFinal;

                if (!is_dir(dirname($rutaDestino))) {
                    mkdir(dirname($rutaDestino), 0777, true);
                }

                if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    return json_encode(["error" => "No se pudo guardar el archivo en el servidor"]);
                }

                // Paso 3: Actualizar el campo ArchivoScaneado
                $stmtUpdate = $this->conn->prepare("UPDATE Correspondencia SET ArchivoScaneado = :archivo WHERE ID = :id");
                $stmtUpdate->bindParam(':archivo', $nombreArchivoFinal);
                $stmtUpdate->bindParam(':id', $nuevoID);
                $stmtUpdate->execute();
            }

            return json_encode([
                "success" => true,
                "message" => "Oficio creado correctamente",
                "id" => $nuevoID,
                "archivo" => $nombreArchivoFinal
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            return json_encode(["error" => "Error en la consulta: " . $e->getMessage()]);
        }
    }
}
