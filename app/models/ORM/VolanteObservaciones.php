<?php

class ORM_VolanteObservaciones
{
    /**
     * @var string[] Posibles estatus de un volante.
     */
    public const ESTATUS = ['Creado','Emitido', 'Notificado_Email', 'Notificado_WhatssApp', 'Solventado', 'Vencido'];

    private $FolioVolante;
    private $TramiteID;
    private $FechaEmision;
    private $FechaLimiteSolventacion;
    private $EstatusVolante;
    private $GlosadorNombre;
    private $Observaciones;
    private $FundamentoLegal;
    private $ErrorID;

    // --- Getters y Setters ---

    public function getFolioVolante()
    {
        return $this->FolioVolante;
    }

    public function setFolioVolante($FolioVolante)
    {
        $this->FolioVolante = $FolioVolante;
    }

    public function getTramiteID()
    {
        return $this->TramiteID;
    }

    public function setTramiteID($TramiteID)
    {
        $this->TramiteID = $TramiteID;
    }

    public function getFechaEmision()
    {
        return $this->FechaEmision;
    }

    public function setFechaEmision($FechaEmision)
    {
        // Ejemplo de validación futura:
        // if (!filter_var($FechaEmision, FILTER_VALIDATE_EMAIL)) {
        //     throw new \InvalidArgumentException("Formato de fecha no válido");
        // }
        $this->FechaEmision = $FechaEmision;
    }

    public function getFechaLimiteSolventacion()
    {
        return $this->FechaLimiteSolventacion;
    }

    public function setFechaLimiteSolventacion($FechaLimiteSolventacion)
    {
        $this->FechaLimiteSolventacion = $FechaLimiteSolventacion;
    }

    public function getEstatusVolante()
    {
        return $this->EstatusVolante;
    }

    public function setEstatusVolante($EstatusVolante)
    {
        // Ejemplo de validación futura:
        // if (!in_array($EstatusVolante, self::ESTATUS)) {
        //     throw new \InvalidArgumentException("Estatus no válido: " . $EstatusVolante);
        // }
        $this->EstatusVolante = $EstatusVolante;
    }

    public function getGlosadorNombre()
    {
        return $this->GlosadorNombre;
    }

    public function setGlosadorNombre($GlosadorNombre)
    {
        $this->GlosadorNombre = $GlosadorNombre;
    }

    public function getObservaciones()
    {
        return $this->Observaciones;
    }

    public function setObservaciones($Observaciones)
    {
        $this->Observaciones = $Observaciones;
    }

    public function getFundamentoLegal()
    {
        return $this->FundamentoLegal;
    }

    public function setFundamentoLegal($FundamentoLegal)
    {
        $this->FundamentoLegal = $FundamentoLegal;
    }

    public function getErrorID()
    {
        return $this->ErrorID;
    }

    public function setErrorID($ErrorID)
    {
        $this->ErrorID = $ErrorID;
    }
}