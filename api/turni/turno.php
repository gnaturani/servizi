<?php

    require_once("../dati_anagrafici/anagrafica.php");

    class Turno{
        public $titolo;
        public $inizio;
        public $fine;
        public $id;
        public $idturnorif;
        public $idturnopre;
        public $preiscrizione;
        public $diaria;

        public $idacc1;
        public $acc_nome;
        public $acc_cognome;

        public $anag_acc1;

        public $year;
        public $id_pre_isc_online;
        public $gruppo;
        public $auto_gestione;
        public $inizio_isc;
        public $fine_isc;
        public $isc_aperte;

        public $count_ragazzi;
        public $count_edu;
        public $count_ragazzi_la;
        public $count_edu_la;

        public $chiuso;
        public $costo_totale;
        public $specifica_date;
        public $posti_max;
        public $posti_max_totali;
        public $accetta_edu_auto;
        public $accetta_rag_auto;

    }

?>