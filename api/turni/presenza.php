<?php


    class DataPresenza{
        public $data;
        public $dataS;
        public $presente;
        public $id;
        public $data_conf;
    }

    class BloccoData{
        public $data;
        public $idturno;
        public $bloccato;
    }

    class Presenza{
        public $idturno;
        public $defturno;
        public $idanag;

        public $rimuovi;

        public $accompagnatore;
        public $nome;
        public $cognome;
        public $data_nascita;

        public $dataS;
        public $data;
        public $presente;

        public $sesso;
        public $comune_nascita;
        public $provincia_nascita;
        public $stato_nascita;
        public $cittadinanza;

        public $doc_tipo;
        public $doc_numero;
        public $doc_comune_ril;
        public $doc_provincia_ril;
        public $doc_stato_ril;

        public $ruolo;
        public $iscr_conf;

        public $stato_pagamento;  // P_arziale  S_aldato  N_ullo

        public $datePresenza;

        public $data_inserimento;
        public $id_iscrizione;
        public $stato_iscrizione;
        public $creato_da;
        public $settimana_1;
        public $settimana_2;
        public $anno_nascita;
        public $meta_turno;
        public $maschio;
        public $femmina;
        public $ultima_modifica;
        public $anag_base_confirmed;
        public $anag_base_diff;
        public $anag_base_diff_det;

        public $idturno2;
        public $defturno2;
        public $data_arrivo;
        public $data_partenza;

        public $dettagli_turno;

    }

?>