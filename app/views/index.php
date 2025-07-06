<?php
// Capturamos la URI
$requestUri = $_SERVER['REQUEST_URI']; 

// Extraemos la parte de la URL que nos interesa (el segmento después de la base)
$path = parse_url($requestUri, PHP_URL_PATH);

// Eliminamos la base de la URL para quedarnos con la acción
$path = str_replace('/DigitalOcean/Egresos/system_recepcion_pagos_dependencias/', '', $path);

// Ahora extraemos la acción desde la URL
$pathParts = explode('/', trim($path, '/'));


// El controlador será la primera parte (Remesas)
$controlador = isset($pathParts[0]) ? $pathParts[0] : ''; 

// El método o acción será la segunda parte (getRemesas)
$metodo = isset($pathParts[1]) ? $pathParts[1] : '';

// Imprimimos para verificar
// echo "Controlador: " . $controlador . "<br>";
// echo "Método: " . $metodo . "<br>";

// Verificar si la solicitud es POST y leer los datos JSON
$data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos JSON de la solicitud
    $jsonData = file_get_contents('php://input');
    
    // Decodificar los datos JSON en un array de PHP
    $data = json_decode($jsonData, true); 
}

switch ($controlador) {
    case 'Remesa':
        // Incluir el controlador
        include_once 'app/controllers/RemesaController.php';
        // Instanciamos el controlador
        $controllerRemesa = new RemesasController();
        switch($metodo){
            case 'createRemesa':
                if(!empty($data)){
                    $controllerRemesa->createRemesa($data);
                }else{
                    echo "Datos no proporcionados";
                }
                exit;
                break;
            case 'getRemesas':
                $controllerRemesa->getRemesas();
                exit;
                break;
            default:
                echo "Acción no válida o datos no proporcionados.";
                exit;
                break;
        }
    case 'Tramite':
        // Incluir el controlador
        include_once 'app/controllers/TramitesController.php';
        // Instanciamos el controlador
        $controllerTramite = new TramitesController();
        switch($metodo){
            case 'createTramite':
                if(!empty($data)){
                    $controllerTramite->createTramite($data);
                }else{
                    echo "Datos no proporcionados";
                }
                exit;
                break;
            case 'getTramites':
                $controllerTramite->getTramites();
                exit;
                break;
            default:
                echo "Acción no válida o datos no proporcionados.";
                exit;
                break;
        }
}
?>
    {
         "FechaRemesa": "2025-01-13",
         "NumeroRemesa": "1",
         "NumeroConsecutivo": "1",
         "FolioIntegra": "179502",
         "OficioPeticion": "SECATI-DGA-DEA-OP0181/2024",
         "TipoTramite": "Pago",
         "Beneficiario": "LOPEZ BERRELLEZA MARISOL",
         "Concepto": "Concepto de ejemplo",
         "Importe": "1000.00",
         "FechaPago": "2025-01-29",
         "FuenteFinanciamiento": "10050",
         "Documento": "NA",
         "Estatus": "VoBO",
         "Comentarios": "SIN COMENTARIOS",
         "FK_CONTRATO": "1"
     }
     $data = [
     'FechaRemesa' => '2025-01-13',
     'NumeroRemesa' => '1',
     'NumeroConsecutivo' => '1',
     'FolioIntegra' => '179502',
     'OficioPeticion' => 'SECATI-DGA-DEA-OP0181/2024',
     'TipoTramite' => 'Pago',
     'Beneficiario' => 'LOPEZ BERRELLEZA MARISOL',
     'Concepto' => 'Concepto de ejemplo',
     'Importe' => '1000.00',
     'FechaPago' => '2025-01-29',
     'FuenteFinanciamiento' => '10050',
     'Documento' => 'NA',
     'Estatus' => 'VoBO',
     'Comentarios' => 'SIN COMENTARIOS',
     'FK_CONTRATO' => '1'
     ];