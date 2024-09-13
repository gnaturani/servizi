<?php

    class Gruppo{

        public $id;
        public $nome;
        public $descrizione;
        public $inizio;
        public $fine;

        public $creato_il;
        public $aggiornato_il;

        public $files;
        public $codici;

    }

    class File {
        public $id;
        public $url;
        public $name;

        public $is_folder;
        public $is_file;

        public $descrizione;
        public $sub;
    }

    class Codice {
        public $id;
        public $code;
        public $nr_accessi;
        public $nr_accessi_max;
        public $creato_il;
    }

?>