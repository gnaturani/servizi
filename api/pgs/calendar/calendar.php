<?php

    class Appointment{
        public $id;
        public $start;
        public $end;
        public $description;
        public $details;
        public $location;
        public $toDelete;
        public $dayName;
    }

    class Team{

        public $id;
        public $name;

        public $players;
        public $username;
        public $calendarId;
        public $cid;
    }

?>