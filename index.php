<?php
// Configurar CORS
header("Access-Control-Allow-Origin: *"); // Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PATCH, DELETE"); // Permitir métodos HTTP
header("Access-Control-Allow-Headers: Content-Type"); // Permitir ciertos encabezados
// Manejar solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit();
}
// Declarar como variables globales
// Incluir el controlador
include_once 'app/controllers/OrdenesPago/OrdenesPagoController.php';
include_once 'app/controllers/RemesaController.php';
include_once 'app/controllers/LoginController.php';
include_once 'app/controllers/KpiController.php';
include_once 'app/controllers/SuficienciaController.php';
include_once 'app/controllers/Correspondencia/CorrespondenciaController.php';
include_once 'app/controllers/Oficios/OficiosController.php';
// Instanciamos el controlador
$controllerTramite = new OrdenesPagoController();
$controllerRemesa = new RemesaController();
$controllerLogin = new LoginController();
$controllerKpi = new KpiController();
$controllerSuficiencia = new SuficienciaController();
$controllerCorrespondencia = new CorrespondenciaController();
$controllerOficios = new OficiosController();
//Obtener el método de la solicitud HTTP
$requestMethod = $_SERVER['REQUEST_METHOD'];
// TRY: CONTROLA LOS METODOS [GET-POST-PATCH-DALETE] Y EXCEPTION A ERROR 500
try {
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
        case 'createTramite':
            if (!empty($data)) {
                // Declarar como global
                global $controllerTramite;
                $respuesta = $controllerTramite->createTramite((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }

            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Tramite registrado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Tramite no registrado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'createRemesa':
            if (!empty($data)) {
                // Declarar como global
                global $controllerRemesa;
                $respuesta = $controllerRemesa->createRemesa((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }

            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Remesa registrada.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Remesa no registrada.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'loginUser':
            if (!empty($data)) {
                // Declarar como global
                global $controllerLogin;
                $respuesta = $controllerLogin->loginUser((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Usuario logueado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Usuario no logueado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'registerUser':
            if (!empty($data)) {
                // Declarar como global
                global $controllerLogin;
                $respuesta = $controllerLogin->registerUser((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Usuario registrado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Usuario no registrado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'crearOficio':
            if (!empty($data)) {
                // Declarar como global
                global $controllerCorrespondencia;
                $respuesta = $controllerCorrespondencia->crearOficio((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Oficio creado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Oficio no creado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getDetalleRemesas':
            // Declarar como global
            global $controllerRemesa;
            $respuesta = $controllerRemesa->getDetalleRemesas((array) $data);
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de detalle de remesas.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron detalle de remesas.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'updateRemesaTramites':
            if (!empty($data)) {
                global $controllerRemesa;
                $respuesta = $controllerRemesa->updateRemesaTramites((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Trámites actualizados.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron trámites actualizados.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'actualizarOficioArchivo':
            header('Content-Type: application/json; charset=utf-8');

            $datos = !empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true);
            $archivos = $_FILES;

            // ✅ Asegúrate de que al menos $datos tenga algo
            if (!empty($datos)) {
                global $controllerCorrespondencia;
                // 👉 Pasamos tanto los datos como los archivos
                $respuesta = $controllerCorrespondencia->actualizarOficioArchivo($datos, $archivos);
            } else {
                echo json_encode(["error" => "Datos no proporcionados"]);
                exit;
            }

            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Oficio modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Oficio no modificado.'), JSON_UNESCAPED_UNICODE);
            }

            exit;
            break;
        case 'actualizarRegistroOficioArchivo':
            header('Content-Type: application/json; charset=utf-8');

            $datos = !empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true);
            $archivos = $_FILES;

            if (!empty($datos)) {
                global $controllerOficios;
                $respuesta = $controllerOficios->actualizarRegistroOficioArchivo($datos, $archivos);
            } else {
                echo json_encode(["error" => "Datos no proporcionados"]);
                exit;
            }

            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Registro de oficio modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Registro de oficio no modificado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'crearRegistroOficio':
            if (!empty($data)) {
                global $controllerOficios;
                $respuesta = $controllerOficios->crearRegistroOficio((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Contestacion creada.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Contestacion no creada.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getTallesTramites':
            if (!empty($data)) {
                global $controllerTramite;
                $respuesta = $controllerTramite->getTallesTramites((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de talles de trámites.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron talles de trámites.'), JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'getDetalleHistoricoMes':
            if (!empty($data)) {
                global $controllerTramite;
                $respuesta = $controllerTramite->getDetalleHistoricoMes((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de detalle de historico de trámites por mes.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron detalle de historico de trámites por mes.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'crearOficioArchivo':
            header('Content-Type: application/json; charset=utf-8');

            $datos = !empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true);
            $Archivo = $_FILES;

            if (!empty($datos)) {
                global $controllerCorrespondencia;
                $respuesta = $controllerCorrespondencia->crearOficioArchivo($datos, $Archivo);
            } else {
                echo json_encode(["error" => "Datos no proporcionados"]);
                exit;
            }

            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Registro de oficio.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Error en el registro del oficio.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'obtenerReportePrioridadTramites':
            if (!empty($data)) {
                global $controllerTramite;
                $respuesta = $controllerTramite->obtenerReportePrioridadTramites((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Reporte de prioridad de trámites.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron reporte de prioridad de trámites.'), JSON_UNESCAPED_UNICODE);
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
        case 'getTramites':
            // Declarar como global
            global $controllerTramite;
            $respuesta = $controllerTramite->getTramites();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de tramites.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron tramites.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getRemesas':
            // Declarar como global
            global $controllerRemesa;
            $respuesta = $controllerRemesa->getRemesas();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de remesas.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron remesas.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'checkUserExistsUser':
            // Declarar como global
            global $controllerLogin;
            $respuesta = $controllerLogin->checkUserExistsUser((array) $data);
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Usuario existe.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Usuario no existe.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getSeguimientoTramites':
            // Declarar como global
            global $controllerTramite;
            $respuesta = $controllerTramite->getSeguimientoTramites();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de seguimiento de trámites.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron seguimiento de trámites.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getHistoricoMes':
            // Declarar como global
            global $controllerTramite;
            $respuesta = $controllerTramite->getHistoricoMes();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de historico de trámites por mes.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron historico de trámites por mes.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getConteoEstatus':
            // Declarar como global
            global $controllerTramite;
            $respuesta = $controllerTramite->getConteoEstatus();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de conteo de estatus.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron conteo de estatus.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getReporteEstatusComentarios':
            // Declarar como global
            global $controllerTramite;
            $respuesta = $controllerTramite->getReporteEstatusComentarios();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de reporte de estatus de comentarios.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron reporte de estatus de comentarios.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getRemesasWithTramites':
            // Declarar como global
            global $controllerRemesa;
            $respuesta = $controllerRemesa->getRemesasWithTramites();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de remesas con trámites.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron remesas con trámites.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'obtenerKPI':
            // Declarar como global
            global $controllerKpi;
            $respuesta = $controllerKpi->obtenerKPI();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Data de KPI.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron KPI.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'listarOficios':
            // Declarar como global
            global $controllerCorrespondencia;
            $respuesta = $controllerCorrespondencia->listarOficios();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de oficios.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron oficios.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'getListaRemesas':
            // Declarar como global
            global $controllerRemesa;
            $respuesta = $controllerRemesa->getListaRemesas();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de remesas.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron remesas.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'listarRegistroOficios':
            // Declarar como global
            global $controllerOficios;
            $respuesta = $controllerOficios->listarRegistroOficios();
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Listado de registros de oficios.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'No se encontraron registros de oficios.'), JSON_UNESCAPED_UNICODE);
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
        case 'updateTramite':
            if (!empty($data)) {
                // Declarar como global
                global $controllerTramite;
                $respuesta = $controllerTramite->updateTramite((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Tramite modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Tramite no modificado.'), JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'updateRemesa':
            if (!empty($data)) {
                // Declarar como global
                global $controllerRemesa;
                $respuesta = $controllerRemesa->updateRemesa((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Remesa modificada.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Remesa no modificada.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'updateUser':
            if (!empty($data)) {
                // Declarar como global
                global $controllerLogin;
                $respuesta = $controllerLogin->updateUser((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Usuario modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Usuario no modificado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'recoverPasswordUser':
            if (!empty($data)) {
                global $controllerLogin;
                $respuesta = $controllerLogin->recoverPasswordUser((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Contraseña recuperada.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Contraseña no recuperada.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'updateTramiteRemesa':
            if (!empty($data)) {
                global $controllerRemesa;
                $respuesta = $controllerRemesa->updateTramiteRemesa((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Tramite y remesa modificados.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Tramite y remesa no modificados.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'actualizarOficio':
            if (!empty($data)) {
                global $controllerCorrespondencia;
                $respuesta = $controllerCorrespondencia->actualizarOficio((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Oficio modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Oficio no modificado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'updateTramiteCompleto':
            if (!empty($data)) {
                global $controllerTramite;
                $respuesta = $controllerTramite->updateTramiteCompleto((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Tramite modificados.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Tramite no modificados.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'actualizarRegistroOficio':
            if (!empty($data)) {
                global $controllerOficios;
                $respuesta = $controllerOficios->actualizarRegistroOficio((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Registro de oficio modificado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Registro de oficio no modificado.'), JSON_UNESCAPED_UNICODE);
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
        case 'deleteTramite':
            if (!empty($data)) {
                // Declarar como global
                global $controllerTramite;
                $respuesta = $controllerTramite->deleteTramite((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Tramite eliminado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Tramite no eliminado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'deleteRemesa':
            if (!empty($data)) {
                global $controllerRemesa;
                $respuesta = $controllerRemesa->deleteRemesa((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Remesa eliminada.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Remesa no eliminada.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'deleteUser':
            if (!empty($data)) {
                global $controllerLogin;
                $respuesta = $controllerLogin->deleteUser((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Usuario eliminado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Usuario no eliminado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'eliminarOficio':
            if (!empty($data)) {
                global $controllerCorrespondencia;
                $respuesta = $controllerCorrespondencia->eliminarOficio((array) $data);
            } else {
                echo "Datos no proporcionados";
                exit;
            }
            if ($respuesta) {
                http_response_code(200);
                echo json_encode(array('message' => 'Oficio eliminado.', 'data' => $respuesta), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(array('message' => 'Oficio no eliminado.'), JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        case 'eliminarRegistroOficio':
            header('Content-Type: application/json');

            try {
                // Para método DELETE, leemos el input directamente
                $input = json_decode(file_get_contents('php://input'), true);

                if (empty($input['ID_RegistroOficios'])) {
                    throw new Exception("ID no proporcionado");
                }

                global $controllerOficios;
                $resultado = $controllerOficios->eliminarRegistroOficio($input);

                // Solo codificamos a JSON una vez al final
                if ($resultado['success']) {
                    http_response_code(200);
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;
        default:
            http_response_code(404);
            echo json_encode(['Message' => 'Acción DELETE desconocida.'], JSON_UNESCAPED_UNICODE);
            break;
    }
}
