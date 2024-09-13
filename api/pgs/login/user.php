<?php

    class User{

        public $id;
        public $username;
        public $password;
        public $surname;
        public $name;
        public $onlyplayer;
        public $logo;
        public $vusername;

        public $teamsAuthorizations;
    }

    class TeamAuthorization{
                public $teamid;
                public $username;
                public $display;
                public $update;
                public $onlyplayer;
                public $teamdescription;
                public $teamlogo;
    }

?>