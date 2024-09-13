<?php

    class Game{
        public $id;
        public $title;
    }

    class Room{
        public $id;
        public $description;
        public $name;
        public $type;
        public $tilename;
        public $bypass_with_object_id;
        public $obligatory;
        public $sequences;
    }

    class Sequence {
        public $id;
        public $position;
        public $description;
        public $nextidsequence;
        public $nextpossequence;
        public $question;
        public $active;

        public $video;
        public $url_video;
        public $folder_video;
        public $filename_video;
        public $step_block;
        public $special_action;

        public $deliver_object_id;
        public $receive_object_id;

        public $answers;
    }

    class Video {
        public $id;
        public $url;
        public $description;
    }

    class GameObject {
        public $id;
        public $name;
        public $url;
        public $description;
    }

    class Answer {
        public $id;
        public $text;
        public $nextidsequence;
        public $nextpossequence;
        public $active;
    }

    class Folder {
        public $name;
        public $path;
        public $files;
    }


    class Player {
        public $id;
        public $username;
        public $position;
        public $gender;

        public $objects;
    }


    class Position {
        public $x;
        public $y;
        public $time;
    }

?>