<?php
// --- CONFIGURACIÓN CORS Y CABECERAS ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8"); // Es buena práctica especificar el Content-Type
header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");  // Limita a los métodos que realmente usas
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// Manejo de la solicitud pre-vuelo (preflight) de CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- INCLUSIONES ---
require_once __DIR__ . '/vendor/autoload.php';
// Asumimos que Database.php usa el patrón Singleton que discutimos
include_once 'app/config/bdConexion.php';
include_once 'app/controllers/VolanteObservaciones/VolanteObservacionesController.php';
// TRY: CONTROLA LOS METODOS [GET-POST-PATCH-DALETE] Y EXCEPTION A ERROR 500
try {
    // 1. OBTENER LA CONEXIÓN A LA BASE DE DATOS
    $db_connection = Database::getInstance()->conn;
    // 2. INYECTAR LA CONEXIÓN AL CONTROLADOR
    $controllerVolante = new VolanteObservacionesController($db_connection);
    // 3. ENRUTAMIENTO BASADO EN MÉTODO Y ACCIÓN
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        $data = json_decode(file_get_contents("php://input"));
        // Ejecutar el manejo según el método de la solicitud HTTP
        switch ($requestMethod) {
            case 'GET':
                handleGetRequest($action, $data);
                break;
            case 'POST':
                handlePostRequest($action, $data);
                break;
            case 'PATCH':
                handlePatchRequest($action, $data);
                break;
            case 'DELETE':
                handleDeleteRequest($action, $data);
                break;
            default:
                http_response_code(404);
                echo json_encode([
                    'Message' => 'Solicitud no válida.'
                ], JSON_UNESCAPED_UNICODE);
                break;
        }
    } else {
        http_response_code(404);
        echo json_encode([
            'Message' => 'No hay acción. URL'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'Message' => 'Error interno del servidor. Detalles: ' . $e->getMessage() . ' en línea ' . $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
// Función para manejar las solicitudes POST
function handlePostRequest($action, $data)
{
    switch ($action) {
        case 'generarVolanteEspecifico':
            if (!empty($data)) {
                global $controllerVolante;
                $respuesta = $controllerVolante->generarVolanteEspecifico((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Volante de Observaciones Armado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontro Volante de Observaciones.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'crearNuevoVolante':
            if (!empty($data)) {
                global $controllerVolante;
                $respuesta = $controllerVolante->crearNuevoVolante((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Volante de Observaciones creado exitosamente.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se ha creado Volante de Observaciones.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'enviarNotificacion':
            try {
                if (empty($data)) {
                    throw new Exception("No se proporcionaron datos para la notificación.", 400);
                }

                global $controllerVolante;
                $exito = $controllerVolante->enviarNotificacionSECATI((array) $data);

                if ($exito) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Notificación enviada exitosamente.']);
                } else {
                    // Este caso no debería ocurrir si se usan excepciones, pero es una red de seguridad.
                    throw new Exception("La notificación no pudo ser enviada.", 500);
                }
            } catch (Exception $e) {
                $code = is_numeric($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
                http_response_code($code);
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
            exit;
            break;
        case 'enviarBoletinInformativo':
            try {
                global $controllerVolante;
                // La variable $resultado ahora contendrá el nombre del archivo o false/excepción
                $resultado = $controllerVolante->enviarBoletinInformativo((array) $data);

                // CAMBIO: Verifica si el resultado es un string (el nombre del archivo)
                if (is_string($resultado) && !empty($resultado)) {
                    http_response_code(200); // OK
                    // Incluye el nombre del archivo en la respuesta
                    echo json_encode([
                        'message' => 'Boletín informativo enviado exitosamente.',
                        'archivo_procesado' => $resultado
                    ]);
                } else {
                    // Si la función devuelve algo inesperado (false, null)
                    throw new Exception("El boletín informativo no pudo ser enviado.", 500);
                }
            } catch (Exception $e) {
                $code = is_numeric($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
                http_response_code($code);
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
            exit;
        break;
        default:
            http_response_code(404);
            echo json_encode(['Message' => 'Acción POST desconocida.'], JSON_UNESCAPED_UNICODE);
            exit;
        break;
    }
}
// Función para manejar las solicitudes GET
function handleGetRequest($action, $data)
{
    switch ($action) {
        case 'listarVolantes':
            global $controllerVolante;
            $respuesta = $controllerVolante->listarVolantes((array) $data);
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Lista de volantes obtenida exitosamente.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Lista de volantes no obtenida.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        default:
            http_response_code(404);
            echo json_encode(['Message' => 'Acción GET desconocida.'], JSON_UNESCAPED_UNICODE);
            exit;
            break;
    }
}
// Función para manejar las solicitudes PATCH
function handlePatchRequest($action, $data)
{
    switch ($action) {
        case 'actualizarVolante':
            try {
                if (empty($data)) {
                    throw new Exception("No se proporcionaron datos para actualizar.", 400);
                }

                global $controllerVolante;
                $filasAfectadas = $controllerVolante->actualizarVolante((array) $data);

                if ($filasAfectadas > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Volante actualizado exitosamente.']);
                } else {
                    // Si no se afectaron filas, puede ser que el folio no exista o los datos eran los mismos.
                    http_response_code(200); // Aún es una solicitud exitosa, aunque no haya cambiado nada.
                    echo json_encode(['message' => 'La operación se completó, pero no se realizaron cambios. Verifique el folio o los datos enviados.']);
                }
            } catch (Exception $e) {
                $code = is_numeric($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
                http_response_code($code);
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
            exit;
            break;
        default:
            http_response_code(404);
            echo json_encode(['Message' => 'Acción PATCH desconocida.'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
// Función para manejar las solicitudes DELETE
function handleDeleteRequest($action, $data)
{
    switch ($action) {
        case 'eliminarVolante':
            try {
                if (empty($data)) {
                    throw new Exception("No se proporcionaron datos para eliminar.", 400);
                }

                global $controllerVolante;
                $filasAfectadas = $controllerVolante->eliminarVolante((array) $data);

                if ($filasAfectadas > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Volante eliminado exitosamente.']);
                } else {
                    // Este caso no debería ocurrir gracias a la pre-verificación en el modelo,
                    // pero lo mantenemos como una red de seguridad.
                    throw new Exception("La operación no afectó ninguna fila. El volante podría haber sido eliminado previamente.", 404);
                }
            } catch (Exception $e) {
                $code = is_numeric($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
                http_response_code($code);
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
            exit;
            break;
        default:
            http_response_code(404);
            echo json_encode(['Message' => 'Acción DELETE desconocida.'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
