<?php

    class RataPagamento {

        public $id;
        public $quota_pagata;
        public $data;
        public $saldato;
        public $todelete;
    }

    class Pagamento {

        public $idanag;
        public $idturno;

        public $totale_da_pagare;
        public $numero_presenze;
        public $saldato;

        public $anagrafica;

        public $rate;

    }

?>