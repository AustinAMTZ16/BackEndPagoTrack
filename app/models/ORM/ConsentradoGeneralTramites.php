<?php

    const TIPO_TRAMITE_ENUM = ['OC', 'OP', 'SRF', 'CRF', 'JA', 'IPS', 'OCO', 'OPO', 'Obra'];
    const ESTATUS_ENUM = ['Creado','Turnado','Procesando','Observaciones','JuntasAuxiliares','Inspectoria','RegistradoSAP','Remesa','RevisionRemesa','RemesaAprobada','DevueltoOrdenesPago','Pagado','Terminado','Rechazado','Devuelto','Cancelado','Cheque','ComprobacionRecursoFinancieros','OrdenesPago','CRF'];

    class ConsentradoGeneralTramites
    {
        public $ID_CONTRATO;
        public $Mes;
        public $FechaRecepcion;
        public $TipoTramite;
        public $Dependencia;
        public $Proveedor;
        public $Concepto;
        public $Importe;
        public $Estatus;
        public $Comentarios;
        public $Fondo;
        public $FechaLimite;
        public $FechaTurnado;
        public $FechaTurnadoEntrega;
        public $FechaDevuelto;
        public $FechaRemesa;
        public $FechaRemesaAprobada;
        public $AnalistaID;
        public $RemesaNumero;
        public $DocSAP;
        public $IntegraSAP;
        public $OfPeticion;
        public $NoTramite;
        public $DoctacionAnexo;
        public $Analista;
        public $FechaLimitePago;
        public $FK_SRF;
        public $FechaCreacion;
    }
