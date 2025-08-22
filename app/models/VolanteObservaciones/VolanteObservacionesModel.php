<?php
require_once 'app/models/ORM/VolanteObservaciones.php';

class VolanteObservacionesModel extends ORM_VolanteObservaciones
{
    private $conn;

    /**
     * El constructor ahora recibe la conexión a la base de datos (Inyección de Dependencias).
     *
     * @param PDO $dbConnection La conexión activa a la base de datos.
     */
    public function __construct(PDO $dbConnection)
    {
        $this->conn = $dbConnection;
    }

    // funcion Generar un Volante Específico (Lógica de Conteo Total)
    public function generarVolanteEspecifico(array $data)
    {
        $sql = "
            WITH VolanteConteoTotal AS (
                SELECT
                    FolioVolante,
                    COUNT(*) OVER (PARTITION BY TramiteID) AS TotalVolantes
                FROM
                    VolantesObservaciones
            )
            SELECT
                vo.FolioVolante,
                vo.FechaEmision,
                vo.FechaLimiteSolventacion,
                vo.Observaciones AS ObservacionEspecifica,
                (CASE WHEN vct.TotalVolantes > 1 THEN TRUE ELSE FALSE END) AS EsReincidencia,
                vct.TotalVolantes AS NumeroReincidenciasInstitucion,
                cgt.ID_CONTRATO,
                cgt.Proveedor,
                cgt.Concepto,
                cgt.Importe,
                cgt.TipoTramite,
                cgt.NoTramite,
                cgt.FechaRecepcion,
                vo.GlosadorNombre,
                CONCAT(is2.NombreUser, ' ', is2.ApellidoUser) AS GlosadorNombreCompleto,
                ce.ErrorID,
                ce.CodigoError,
                ce.DescripcionCorta AS DescripcionError,
                ce.FundamentoLegal,
                ce.AccionCorrectora,
                cat.NombreCategoria AS CategoriaDelError,
                ea.Secretaria AS Dependencia, 
                ea.Nombre, 
                ea.Correo,
                ea.AmbitoAtencion,
                vo.EstatusVolante,
                vo.FundamentoLegal AS FundamentoLegalVolante,
                vo.FirmaAutorizacion
            FROM
                VolantesObservaciones vo
            JOIN
                ConsentradoGeneralTramites cgt ON vo.TramiteID = cgt.ID_CONTRATO
            JOIN 
            	InicioSesion is2 ON is2.InicioSesionID  = vo.GlosadorNombre
            JOIN 
                EnlacesAdministrativos ea ON cgt.DependenciaID = ea.EnlaceID
            JOIN
                CatalogoErrores ce ON vo.ErrorID = ce.ErrorID
            JOIN
                CategoriasErrores cat ON ce.CategoriaID = cat.CategoriaID
            JOIN
                VolanteConteoTotal vct ON vo.FolioVolante = vct.FolioVolante
            WHERE
                vo.FolioVolante = :FolioVolante;
        ";
        // Preparar la consulta
        $stmt = $this->conn->prepare($sql);
        // Vincular el valor de forma segura
        $stmt->bindParam(':FolioVolante', $data['FolioVolante'], PDO::PARAM_STR);

        $stmt->execute();

        // return $stmt->fetch(PDO::FETCH_ASSOC);


        // Paso 3: Obtener la fila de resultados.
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Paso 4 (La Mejora): Lanzar la excepción si no se encontró nada.
        if (!$resultado) {
            throw new Exception("No se encontró un Volante de Observaciones con el folio proporcionado.", 404);
        }

        // Paso 5: Devolver el array con la data completa si todo fue bien.
        return $resultado;
    }
    /**
     * Crea un nuevo volante, aplicando las reglas de negocio para las fechas.
     *
     * @param array $data Los datos para el nuevo volante.
     * @return string El folio del nuevo volante creado.
     */
    public function crearVolante(array $data)
    {
        // --- 1. APLICAR REGLAS DE NEGOCIO Y PREPARAR DATOS ---
        $fechaEmision = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $folioVolante = 'VO' . $fechaEmision->format('mdHis') . $data['TramiteID'];
        // --- 2. PREPARAR Y EJECUTAR LA CONSULTA SQL ---
        $sql = "INSERT INTO VolantesObservaciones 
                    (FolioVolante, TramiteID, EstatusVolante, GlosadorNombre, Observaciones, FundamentoLegal, ErrorID) 
                VALUES 
                    (:FolioVolante, :TramiteID, :EstatusVolante, :GlosadorNombre, :Observaciones, :FundamentoLegal, :ErrorID)";
        $stmt = $this->conn->prepare($sql);
        // // Formatear fechas a string para la BD
        // $fechaEmisionStr = $fechaEmision->format('Y-m-d H:i:s');
        // $fechaLimiteStr = $fechaLimite->format('Y-m-d H:i:s');
        $estatusInicial = ORM_VolanteObservaciones::ESTATUS[0]; // 'Emitido'
        // Vincular todos los parámetros de forma segura
        $stmt->bindParam(':FolioVolante', $folioVolante);
        $stmt->bindParam(':TramiteID', $data['TramiteID'], PDO::PARAM_INT);
        // $stmt->bindParam(':FechaEmision', $fechaEmisionStr);
        // $stmt->bindParam(':FechaLimiteSolventacion', $fechaLimiteStr);
        $stmt->bindParam(':EstatusVolante', $estatusInicial);
        $stmt->bindParam(':GlosadorNombre', $data['GlosadorNombre']);
        $stmt->bindParam(':Observaciones', $data['Observaciones']);
        $stmt->bindParam(':FundamentoLegal', $data['FundamentoLegal']);
        $stmt->bindParam(':ErrorID', $data['ErrorID'], PDO::PARAM_INT);

        // Ejecutar y verificar
        if ($stmt->execute()) {
            // Si la inserción es exitosa, devolver el nuevo folio
            return $folioVolante;
        } else {
            // Si falla, lanzar una excepción para que el enrutador la atrape
            throw new Exception("No se pudo crear el volante de observaciones.", 500);
        }
    }
    /**
     * Obtiene una lista de todos los volantes de observaciones con datos clave.
     *
     * @return array Un array de volantes. Si no hay volantes, devuelve un array vacío.
     */
    public function listarTodosLosVolantes()
    {
        // Seleccionamos los campos más importantes para una vista de lista.
        $sql = "SELECT 
                    vo.FolioVolante,
                    vo.TramiteID,
                    vo.FechaEmision,
                    vo.FechaLimiteSolventacion,
                    vo.EstatusVolante,
                    EA.Secretaria AS Dependencia,
                    cgt.Proveedor,
                    vo.GlosadorNombre,
                    CONCAT(is2.NombreUser, ' ', is2.ApellidoUser) AS GlosadorNombreCompleto
                FROM 
                    VolantesObservaciones vo
                LEFT JOIN 
                    ConsentradoGeneralTramites cgt ON vo.TramiteID = cgt.ID_CONTRATO
                JOIN 
            	    InicioSesion is2 ON is2.InicioSesionID  = vo.GlosadorNombre
                JOIN
                    EnlacesAdministrativos EA ON EA.EnlaceID = cgt.DependenciaID
                ORDER BY 
                    vo.FechaEmision DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        // fetchAll devuelve todos los resultados en un array.
        // Si no hay resultados, devolverá un array vacío, lo cual es una respuesta válida.
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Actualiza uno o más campos de un volante existente.
     *
     * @param array $data Un array asociativo con los datos a actualizar. Debe contener 'FolioVolante'.
     * @return int El número de filas afectadas por la actualización.
     */
    public function actualizarVolante(array $data)
    {
        // --- PASO 1: VERIFICAR SI EL VOLANTE EXISTE ---
        $checkSql = "SELECT COUNT(*) FROM VolantesObservaciones WHERE FolioVolante = :FolioVolante";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':FolioVolante', $data['FolioVolante']);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() == 0) {
            // Si no existe, lanzamos una excepción 404. El enrutador la manejará.
            throw new Exception("No se encontró un Volante de Observaciones con el folio proporcionado.", 404);
        }

        // --- PASO 2: CONSTRUIR Y EJECUTAR LA ACTUALIZACIÓN (si existe) ---
        $setParts = [];
        foreach ($data as $key => $value) {
            if ($key !== 'FolioVolante') {
                $setParts[] = "$key = :$key";
            }
        }

        if (empty($setParts)) {
            throw new InvalidArgumentException("No se proporcionaron campos para actualizar.", 400);
        }

        $sql = "UPDATE VolantesObservaciones SET " . implode(', ', $setParts) . " WHERE FolioVolante = :FolioVolante";

        $stmt = $this->conn->prepare($sql);

        foreach ($data as $key => &$value) {
            $stmt->bindParam(":$key", $value);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }
    /**
     * Elimina un volante de la base de datos de forma permanente.
     *
     * @param string $folio El FolioVolante del registro a eliminar.
     * @return int El número de filas eliminadas (debería ser 1).
     */
    public function eliminarVolante(string $folio)
    {
        // --- PASO 1: VERIFICAR SI EL VOLANTE EXISTE (Buena práctica) ---
        $checkSql = "SELECT COUNT(*) FROM VolantesObservaciones WHERE FolioVolante = :FolioVolante";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':FolioVolante', $folio);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() == 0) {
            // Si no existe, lanzamos una excepción 404.
            throw new Exception("No se encontró un Volante de Observaciones con el folio proporcionado para eliminar.", 404);
        }

        // --- PASO 2: EJECUTAR LA ELIMINACIÓN ---
        $sql = "DELETE FROM VolantesObservaciones WHERE FolioVolante = :FolioVolante";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':FolioVolante', $folio);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
