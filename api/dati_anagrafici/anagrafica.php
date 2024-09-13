<?php

    class Anagrafica{

        public $id;
        public $nome;
        public $cognome;
        public $data_nascita;
        public $anno;
        public $sesso;
        public $comune_nascita;
        public $provincia_nascita;
        public $stato_nascita;
        public $cittadinanza;

        public $comune_n_ques;
        public $provincia_n_ques;
        public $stato_n_ques;
        public $cittadinanza_n_ques;

        public $doc_tipo;
        public $doc_numero;
        public $doc_comune_ril;
        public $doc_provincia_ril;
        public $doc_stato_ril;

        public $ruolo;
        public $pullman_a;
        public $pullman_r;
        public $pullman_g;
        public $pullman_ga;
        public $pullman_gr;

        public $cellulare_1;
        public $cellulare_2;
        public $cellulare_3;
        public $email_1;
        public $email_2;
        public $res_via;
        public $res_citta;
        public $res_provincia;

        public $accompagnatore;

        public $dettagli_turno;


    }

    class AnagraficaDettaglioTurno {

        public $id;
        public $idturno;
        public $idanag;
        public $maglia;
        public $stanze;
        public $note;
    }

    class StoricoPresenze {
        public $idturno;
        public $titolo;
        public $inizio;
    }

?>