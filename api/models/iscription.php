<?php

    class Iscription{
        public $id;
        public $name;
        public $surname;
        public $year;

        public $created_at;
        public $created_by;
        public $updated_at;
        public $db_insert_at;
        public $status;


        public $nome;
        public $cognome;

        public $sesso;
        public $comune_nascita;
        public $provincia_nascita;
        public $stato_nascita;
        public $cittadinanza;

        public $comune_res;
        public $comune_res_n;
        public $via_res;
        public $provincia_res;
        public $pullman;

        public $comune_n_ques;
        public $provincia_n_ques;
        public $stato_n_ques;
        public $cittadinanza_n_ques;

        public $ruolo;
        public $forced_note;

    }

    class IscriptionHistory{
        public $id;
        public $id_iscrizione;
        public $created_at;
        public $db_insert_at;
        public $status;
        public $firChoice;
        public $firWeek1;
        public $firWeek2;
        public $firChoiceTitle;
    }

?>