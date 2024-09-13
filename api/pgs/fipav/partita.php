<?php

class Partita{
    public $numero;
    public $giornata;
    public $dataora;
    public $squadra_casa;
    public $squadra_ospite;
    public $res_squadra_casa;
    public $res_squadra_ospite;
    public $risultato;
    public $luogo;
    public $numerogara;
}

class Giornata {
    public $giornata;
    public $partite;
}

class RankingItem {
    public $campionato;
    public $ordine;
    public $squadra;
    public $punti;
    public $pg;
    public $pv;
    public $pp;
    public $sf;
    public $ss;
    public $qs;
}

class GroupItem {
    public $campionato;
    public $girone;
    public $idgirone;
}

?>