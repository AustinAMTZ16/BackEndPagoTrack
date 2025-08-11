<?php
// Incluir las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Mailgun\Mailgun;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Incluimos las dependencias que necesita
include_once 'app/models/VolanteObservaciones/VolanteObservacionesModel.php';

class VolanteObservacionesController
{
    // Solo necesitamos una propiedad para el modelo
    private $model;

    // El constructor AHORA ACEPTA la conexión (Inyección de Dependencias)
    public function __construct(PDO $dbConnection)
    {
        // Ya no crea la conexión, la recibe.
        // Inyecta directamente la conexión al crear el modelo.
        $this->model = new VolanteObservacionesModel($dbConnection);
    }

    // Este método está perfecto, simplemente pasa la llamada al modelo.
    public function generarVolanteEspecifico($data)
    {
        // Validación opcional para asegurar que la clave 'FolioVolante' existe
        if (!isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("La clave 'FolioVolante' es requerida en los datos.", 400);
        }

        // Se pasa el array $data directamente al modelo.
        return $this->model->generarVolanteEspecifico($data);
    }
    /**
     * Orquesta la creación de un nuevo volante de observaciones.
     * Valida la entrada y pasa la responsabilidad al modelo.
     *
     * @param array $data Datos del nuevo volante (ej. TramiteID, ErrorID, Observaciones).
     * @return string El folio del volante recién creado.
     */
    public function crearNuevoVolante(array $data)
    {
        // Validación de entrada: Aseguramos que los datos mínimos existan.
        $camposRequeridos = ['TramiteID', 'ErrorID', 'Observaciones', 'GlosadorNombre'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo])) {
                throw new InvalidArgumentException("El campo '{$campo}' es requerido para crear un volante.", 400);
            }
        }

        // Pasa la orden al modelo para que aplique la lógica de negocio y guarde.
        return $this->model->crearVolante($data);
    }
    /**
     * Orquesta la obtención de la lista de todos los volantes.
     *
     * @return array Lista de todos los volantes.
     */
    public function listarVolantes()
    {
        // Simplemente pasa la llamada al modelo.
        return $this->model->listarTodosLosVolantes();
    }
    /**
     * Orquesta la actualización de un volante.
     *
     * @param array $data Los datos a actualizar, incluyendo el FolioVolante.
     * @return int El número de filas afectadas.
     */
    public function actualizarVolante(array $data)
    {
        // Validación de entrada: El FolioVolante es indispensable para saber qué registro actualizar.
        if (!isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("La clave 'FolioVolante' es requerida para actualizar.", 400);
        }

        return $this->model->actualizarVolante($data);
    }
    /**
     * Orquesta la eliminación de un volante.
     *
     * @param array $data Debe contener la clave 'FolioVolante'.
     * @return int El número de filas eliminadas.
     */
    public function eliminarVolante(array $data)
    {
        // Validación de entrada: El FolioVolante es indispensable.
        if (!isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("La clave 'FolioVolante' es requerida para eliminar.", 400);
        }

        return $this->model->eliminarVolante($data['FolioVolante']);
    }
    /**
     * Orquesta el envío de una notificación por correo electrónico.
     *
     * @param array $data Debe contener 'correoDestino' y 'FolioVolante'.
     * @return bool True si el correo se envió, false en caso contrario.
     */
    public function enviarNotificacionPHPMail(array $data)
    {
        // Validación de entrada
        if (!isset($data['correoDestino']) || !isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("Se requieren 'correoDestino' y 'FolioVolante'.", 400);
        }
        if (!filter_var($data['correoDestino'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("La dirección de correo no es válida.", 400);
        }

        // 1. Obtener los datos del volante desde el Modelo
        $volante = $this->model->generarVolanteEspecifico($data);
        if (!$volante) {
            throw new Exception("No se encontraron datos para el folio proporcionado.", 404);
        }

        // 2. Enviar el correo usando PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor (leída desde las variables de entorno)
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $_ENV['MAIL_PORT'];
            $mail->CharSet    = 'UTF-8';

            // Remitente y Destinatarios
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($data['correoDestino']);
            $mail->addReplyTo($_ENV['MAIL_FROM_ADDRESS'], 'Información');

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Notificación de Volante de Observaciones: ' . $volante['FolioVolante'];
            $mail->Body    = $this->crearCuerpoHtmlCorreo($volante);
            $mail->AltBody = 'Se ha generado un volante de observaciones. Por favor, revise el sistema.';

            $mail->send();
            // return true;
            $updateData = [
                'FolioVolante' => $data['FolioVolante'],
                'EstatusVolante' => 'Emitido'
            ];
            $this->model->actualizarVolante($updateData);
            // Si todo fue exitoso (envío y actualización), devolvemos true.
            return true;
        } catch (Exception $e) {
            // Registrar el error detallado en el log del servidor
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            // Lanzar una excepción genérica para el cliente
            throw new Exception("El servicio de correo no está disponible en este momento. Inténtelo más tarde.", 503);
        }
    }
    /**
     * Función auxiliar para generar el cuerpo HTML del correo con el diseño oficial.
     *
     * @param array $volante Array con todos los datos del volante.
     * @return string El HTML completo del correo.
     */
    private function crearCuerpoHtmlCorreo(array $volante): string
    {
        // Usamos number_format, que no requiere extensiones adicionales.
        $importeFormateado = '$' . number_format($volante['Importe'], 2, '.', ',');

        // Lógica para la sección de Reincidencia
        $seccionReincidencia = $volante['EsReincidencia']
            ? "Reincidencia: <strong>Si</strong> ({$volante['NumeroReincidenciasInstitucion']} Reincidencias de la Institución)"
            : "Reincidencia: <strong>No</strong>";

        // URLs de las imágenes (deben ser accesibles públicamente)
        $urlLogo = 'https://pagotrack.mexiclientes.com/assets/images/lg_banner.jpg'; // Ejemplo

        $urlQrValidacion = 'https://placehold.co/120x120/png?text=QR+Validacion'; // Placeholder
        $urlQrDetalle = 'https://placehold.co/120x120/png?text=QR+Detalle'; // Placeholder

        // URLs de destino para los QR
        $urlValidacionData = 'https://pagotrack.mexiclientes.com/ContestacionAnalistaActualizar.html';
        $urlDetalleData = "https://pagotrack.mexiclientes.com/TramiteDetalle.html?id={$volante['ID_CONTRATO']}";

        // Plantilla HTML para el correo
        return <<<HTML
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Volante de Observaciones</title>
            </head>
            <body style="font-family: Lato, Arial, sans-serif; background-color: #e9ecef; margin: 0; padding: 20px; color: #343a40;">

                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center">
                            <table class="document-container" width="850" border="0" cellspacing="0" cellpadding="40" style="background: #fff; border-radius: 8px; margin: 20px auto;">
                                <!-- HEADER -->
                                <tr>
                                    <td style="border-bottom: 2px solid #dee2e6; padding-bottom: 20px;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td><img src="{$urlLogo}" alt="Logo Tesorería" width="180" /></td>
                                                <td align="right" style="font-size: 14px; font-weight: 700; line-height: 1.4;">
                                                    Tesorería Municipal <br />
                                                    Dirección General de Egresos <br />
                                                    Dirección de Egresos y Control Presupuestal<br />
                                                    Departamento de Presupuesto<br />
                                                    <span style="font-size: 18px; color: #730104;">Volante de Observaciones</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- FOLIO INFO -->
                                <tr>
                                    <td style="font-size: 13px; line-height: 1.6; padding-top: 30px; padding-bottom: 30px;">
                                        <strong style="color: #000;">Institución:</strong> {$volante['Dependencia']}
                                            <br />
                                        <strong style="color: #000;">Folio Volante:</strong> {$volante['FolioVolante']}
                                            <br />
                                        <strong style="color: #000;">Fecha de Emisión:</strong> {$volante['FechaEmision']}
                                            <br />
                                        <strong style="color: #000;">Fecha Límite de Solventación:</strong> {$volante['FechaLimiteSolventacion']}
                                    </td>
                                </tr>
                                <!-- OBSERVATIONS TABLE -->
                                <tr>
                                    <td>
                                        <table class="observations-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 12px;">
                                            <thead>
                                                <tr>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left; border-radius: 6px 0 0 6px;">ID</th>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left;">Trámite</th>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left;">Beneficiario</th>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left;">Importe</th>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left;">Fundamento y Observación</th>
                                                    <th style="background-color: #730104; color: #fff; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left; border-radius: 0 6px 6px 0;">Glosador</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr style="border-bottom: 1px solid #dee2e6;">
                                                    <td style="padding: 12px 15px; vertical-align: top;">{$volante['ID_CONTRATO']}</td>
                                                    <td style="padding: 12px 15px; vertical-align: top;">{$volante['NoTramite']} - {$volante['TipoTramite']}</td>
                                                    <td style="padding: 12px 15px; vertical-align: top;">{$volante['Proveedor']}</td>
                                                    <td style="padding: 12px 15px; vertical-align: top;">{$importeFormateado}</td>
                                                    <td style="padding: 12px 15px; vertical-align: top;">
                                                        <strong>Fundamento:</strong> {$volante['FundamentoLegalVolante']}<br><br>
                                                        <strong>Observación:</strong> {$volante['ObservacionEspecifica']}
                                                    </td>
                                                    <td style="padding: 12px 15px; vertical-align: top;">{$volante['GlosadorNombreCompleto']}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 0 0 55px;">
                                        <p style="color: rgb(52, 58, 64); font-family: Lato, Arial, sans-serif; font-size: 8px; margin: 0; padding: 0;">
                                        <strong>CONCEPTO:</strong> {$volante['Concepto']}
                                        </p>
                                    </td>
                                </tr>
                                <!-- NOTES SECTION -->
                                <tr>
                                    <td style="padding-top: 20px;">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="15" style="font-size: 11px; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #730104; line-height: 1.5;">
                                            <tr>
                                                <td>
                                                    {$seccionReincidencia}<br><br>
                                                    <strong>Nota:</strong> De conformidad con el Artículo 46 de la Normatividad para el Ejercicio del Gasto y Control Presupuestal vigente, los expedientes con observaciones no podrán continuar su flujo ni contabilizarse dentro del periodo de tiempo para su atención, hasta que éstas hayan sido solventadas ante la Dirección de Egresos y Control Presupuestal (DECP); las observaciones deberán ser solventadas en un plazo máximo de 2 días hábiles posteriores a la fecha de emisión del volante; salvo en periodo de cierre, entendiéndose por éste a los días posteriores al último día de recepción de trámites del mes. No habrá prórroga alguna para la recepción de trámites. Durante ese periodo, la solventación de observaciones deberá ser en el mismo día; cuando el expediente de trámite tenga alguna observación, es responsabilidad del Ejecutor de Gasto recoger el expediente físico en la DECP, debido a que ésta se deslinda de toda responsabilidad de los expedientes físicos.
                                                    <br><br><br>
                                                    <strong>{$volante['FirmaAutorizacion']}</strong>:<strong>{$volante['EstatusVolante']}</strong>
                                                </td>
                                                <td>
                                                    
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- FOOTER SIGNATURES -->
                                <tr>
                                    
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </body>
            </html>
        HTML;
    }
    /**
     * Método para enviar una notificación por correo electrónico usando Mailgun.
     *
     * @param array $data Debe contener 'correoDestino' y 'FolioVolante'.
     * @return bool True si el correo se envió, false en caso contrario.
     */
    public function enviarNotificacionMailug(array $data)
    {
        // Validación de entrada
        if (!isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("Se requieren 'FolioVolante'.", 400);
        }
        // if (!filter_var($data['correoDestino'], FILTER_VALIDATE_EMAIL)) {
        //     throw new InvalidArgumentException("La dirección de correo no es válida.", 400);
        // }

        // 1. Obtener los datos del volante desde el Modelo
        $volante = $this->model->generarVolanteEspecifico($data);
        if (!$volante) {
            throw new Exception("No se encontraron datos para el folio proporcionado.", 404);
        }

        // 2. Enviar el correo usando Mailgun
        try {
            $apiKey    = $_ENV['MAILGUN_API_KEY'];
            $domain    = $_ENV['MAILGUN_DOMAIN'];
            $fromName  = $_ENV['MAILGUN_FROM_NAME'];
            $fromEmail = $_ENV['MAILGUN_FROM_ADDRESS'];

            $mg = Mailgun::create($apiKey);

            $response = $mg->messages()->send($domain, [
                'from'    => "$fromName <$fromEmail>",
                'to'      => $volante['Correo'],
                'subject' => 'Notificación de Volante de Observaciones: ' . $volante['FolioVolante'],
                'text'    => 'Se ha generado un volante de observaciones. Por favor ponerse en contacto con la DECP.',
                'html'    => $this->crearCuerpoHtmlCorreo($volante),
            ]);

            // Actualizar estado en base de datos
            $updateData = [
                'FolioVolante'    => $data['FolioVolante'],
                'EstatusVolante'  => 'Emitido'
            ];
            $this->model->actualizarVolante($updateData);

            return true;
        } catch (Exception $e) {
            error_log("Mailgun Error: {$e->getMessage()}");
            throw new Exception("El servicio de correo no está disponible en este momento. Inténtelo más tarde.", 503);
        }
    }
    /**
     * Método para enviar una notificación por correo electrónico usando la nueva API.
     *
     * @param array $data Debe contener 'FolioVolante' y opcionalmente 'EstatusVolante' y 'FirmaAutorizacion'.
     * @return bool True si el correo se envió, false en caso contrario.
     */
    public function enviarNotificacionSECATI(array $data)
    {
        // Validación de entrada
        if (!isset($data['FolioVolante'])) {
            throw new InvalidArgumentException("Se requiere 'FolioVolante'.", 400);
        }
        $fechaEmision = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $fechaLimiteStr = $this->calcularFechaLimite($fechaEmision->format('Y-m-d H:i:s'));
        // $fechaLimiteStr = $this->calcularFechaLimite('2025-08-08 10:40:00');
        // 1. Enviar el correo usando la nueva API
        try {
            // Si la petición no fue exitosa (código de respuesta no es 2xx), Guzzle lanzará una excepción
            // Actualizar estado en base de datos
            $updateData = [
                'FolioVolante'   => $data['FolioVolante'],
                'EstatusVolante' => $data['EstatusVolante'] ?? 'Emitido',
                'FirmaAutorizacion' => $data['FirmaAutorizacion'] ?? null,
                'FechaLimiteSolventacion' =>  $fechaLimiteStr,
                'FechaEmision' => $fechaEmision->format('Y-m-d H:i:s')
            ];
            $this->model->actualizarVolante($updateData);

            // 1. Obtener los datos del volante desde el Modelo
            $volante = $this->model->generarVolanteEspecifico($data);
            if (!$volante) {
                throw new Exception("No se encontraron datos para el folio proporcionado.", 404);
            }

            // Credenciales y endpoint de la nueva API desde variables de entorno
            $apiEndpoint = $_ENV['API_EMAIL_ENDPOINT'];
            $apiUser     = $_ENV['API_EMAIL_USER'];
            $apiPass     = $_ENV['API_EMAIL_PASS'];

            $client = new Client();

            // El cuerpo se envía como 'multipart' según la documentación de la API [cite: 15]
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-API-USER' => $apiUser, // [cite: 25]
                    'X-API-PASS' => $apiPass  // [cite: 25]
                ],
                'multipart' => [
                    [
                        'name'     => 'to',
                        // 'contents' => $volante['Correo']
                        'contents' => 'Tomar listado'
                    ],
                    [
                        'name'     => 'cc',
                        'contents' => 'doris.torres@ayuntamientopuebla.gob.mx,concepcion.soriano@ayuntamientopuebla.gob.mx,agustin.martinez@ayuntamientopuebla.gob.mx,carlos.pola@ayuntamientopuebla.gob.mx,eduardo.espinoza@ayuntamientopuebla.gob.mx'
                    ],
                    [
                        'name'     => 'subject',
                        'contents' => 'Notificación de Volante de Observaciones: ' . $volante['FolioVolante']
                    ],
                    [
                        'name'     => 'body',
                        // 'contents' => $this->crearCuerpoHtmlCorreo($volante)
                        'contents' => 'Mensaje de prueba para notificación de volante de observaciones. Entorno de pruebas.'
                    ],
                    [
                        'name'     => 'altbody',
                        'contents' => 'Se ha generado un volante de observaciones. Por favor ponerse en contacto con la DECP.'
                    ]
                    // Si necesitaras adjuntar el volante como PDF, lo añadirías aquí:
                    // [
                    //     'name'     => 'file1',
                    //     'contents' => fopen('/ruta/a/tu/volante.pdf', 'r')
                    // ]
                ]
            ]);

            return true;
        } catch (RequestException $e) {
            // Captura errores específicos de Guzzle (red, respuestas 4xx, 5xx)
            error_log("API Error: {$e->getMessage()}");
            throw new Exception("El servicio de notificación no está disponible. Inténtelo más tarde.", 503);
        } catch (Exception $e) {
            // Captura otras excepciones generales
            error_log("General Error: {$e->getMessage()}");
            throw new Exception("Ocurrió un error inesperado al procesar la notificación.", 500);
        }
    }
    /**
     * Calcula la fecha límite de solventación basada en reglas de negocio complejas.
     *
     * @param string $fechaEmisionStr La fecha y hora de emisión (ej. '2025-08-07 19:40:00').
     * @return string La fecha límite de solventación formateada.
     */
    public function calcularFechaLimite(string $fechaEmisionStr): string
    {
        // --- 1. CONFIGURACIÓN INICIAL ---
        $diaCierre = 25;
        $horaCorte = 15; // 3 PM
        $zonaHoraria = new DateTimeZone('America/Mexico_City');

        $fechaEmision = new DateTime($fechaEmisionStr, $zonaHoraria);
        $fechaLimite = clone $fechaEmision; // Clonamos para no modificar la fecha original

        // Bandera para saber si debemos ajustar la hora final a las 9 AM
        $ajustarHoraApertura = false;

        // --- 2. DETERMINAR REGLAS BASADAS EN LA HORA Y DÍA DE EMISIÓN ---

        // REGLA DE HORA: ¿La emisión fue después de la hora de corte?
        if ((int)$fechaEmision->format('G') >= $horaCorte) {
            $ajustarHoraApertura = true; // Marcamos que la hora final debe ser 9 AM.

            // ¡Importante! Si fue tarde, el plazo empieza a contar desde el DÍA SIGUIENTE.
            $fechaLimite->modify('+1 day');
        }

        // REGLA DE DÍA: ¿Estamos en periodo de cierre?
        // Determina cuántos días hábiles sumar.
        $diasHabilesASumar = ((int)$fechaEmision->format('j') >= $diaCierre) ? 1 : 2;

        // --- 3. CÁLCULO DE DÍAS HÁBILES ---

        for ($i = 0; $i < $diasHabilesASumar; $i++) {
            // Si no es el primer día del bucle, sumamos un día para avanzar.
            // Si es el primer día, ya estamos posicionados correctamente (hoy o mañana).
            // if ($i > 0) {
            //     $fechaLimite->modify('+1 day');
            // }

            // En cada iteración, avanzamos un día.
            $fechaLimite->modify('+1 day');

            // Mientras el día sea Sábado (6) o Domingo (7), sigue sumando días.
            while ($fechaLimite->format('N') >= 6) {
                $fechaLimite->modify('+1 day');
            }
        }

        // --- 4. AJUSTE FINAL DE LA HORA ---

        if ($ajustarHoraApertura) {
            // Si la bandera se activó, la hora de solventación es a las 9 AM.
            $fechaLimite->setTime(15, 0, 0);
        }
        // Si la bandera es false, la hora original de emisión se mantiene automáticamente.

        return $fechaLimite->format('Y-m-d H:i:s');
    }
    /**
     * Método para enviar un boletín informativo (placeholder).
     * Este método aún no está implementado.
     *
     * @param array $data Datos necesarios para el boletín.
     * @return void
     */
    public function enviarBoletinInformativo(array $data)
    {
        // Primero, verifica si el archivo existe y es válido
        if (!isset($_FILES['file1']) || $_FILES['file1']['error'] !== UPLOAD_ERR_OK) {
            // Si no hay archivo o hay un error, puedes lanzar una excepción o devolver false
            throw new Exception("No se adjuntó ningún archivo válido.", 400);
        }
        // Guarda el nombre del archivo para devolverlo después
        $nombreArchivo = $_FILES['file1']['name'];
        $rutaTmp       = $_FILES['file1']['tmp_name'];
        $mime          = mime_content_type($rutaTmp) ?: 'application/octet-stream';

        // $nombreAscii = preg_replace('/[^A-Za-z0-9._-]/','_',iconv('UTF-8','ASCII//TRANSLIT', $_FILES['file1']['name']));
        // $mime = mime_content_type($_FILES['file1']['tmp_name']) ?: 'application/pdf';
        try {
            // Credenciales y endpoint de la nueva API desde variables de entorno
            $apiEndpoint = $_ENV['API_EMAIL_ENDPOINT'];
            $apiUser     = $_ENV['API_EMAIL_USER'];
            $apiPass     = $_ENV['API_EMAIL_PASS'];

            $client = new Client();

            // El cuerpo se envía como 'multipart' según la documentación de la API [cite: 15]
            $response = $client->request('POST', $apiEndpoint, [
                'headers' => [
                    'X-API-USER' => $apiUser, // [cite: 25]
                    'X-API-PASS' => $apiPass  // [cite: 25]
                ],
                'multipart' => [
                    [
                        'name'     => 'to',
                        'contents' => 'agustin.martinez@ayuntamientopuebla.gob.mx'
                    ],
                    // [
                    //     'name'     => 'cc',
                    //     'contents' => 'doris.torres@ayuntamientopuebla.gob.mx,concepcion.soriano@ayuntamientopuebla.gob.mx,agustin.martinez@ayuntamientopuebla.gob.mx,carlos.pola@ayuntamientopuebla.gob.mx,eduardo.espinoza@ayuntamientopuebla.gob.mx'
                    // ],
                    [
                        'name'     => 'subject',
                        'contents' => 'Boletín Informativo'
                    ],
                    [
                        'name'     => 'body',
                        'contents' => 'Mensaje de prueba para notificación de volante de observaciones. Entorno de pruebas.'
                    ],
                    [
                        'name'     => 'altbody',
                        'contents' => 'Se ha generado un volante de observaciones. Por favor ponerse en contacto con la DECP.'
                    ],
                    [
                        'name'     => 'file1',
                        'contents' => fopen($_FILES['file1']['tmp_name'], 'r'),
                        'filename' => $nombreArchivo,
                        'headers'  => ['Content-Type' => $mime]
                    ]
                ]
            ]);

            return $nombreArchivo;

        } catch (RequestException $e) {
            // Captura errores específicos de Guzzle (red, respuestas 4xx, 5xx)
            error_log("API Error: {$e->getMessage()}");
            throw new Exception("El servicio de notificación no está disponible. Inténtelo más tarde.", 503);
        } catch (Exception $e) {
            // Captura otras excepciones generales
            error_log("General Error: {$e->getMessage()}");
            throw new Exception("Ocurrió un error inesperado al procesar la notificación.", 500);
        }
    }
}
