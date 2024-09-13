<?php

error_reporting(E_ERROR | E_PARSE);

require_once("partita.php");
require_once("../resultCalling.php");

function decodePage($url)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

            // $url = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=45877&SId=&PId=9653&btFiltro=CERCA';

        $local = false;

        if ($local) {
            $url = '2dif_18_19.htm';
            $html = file_get_contents($url);
        } else {

            ini_set("allow_url_fopen", 1);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);
            curl_close($ch);

        }

        $start = strrpos($html, "<tbody");
        $html = substr($html, $start);
        $end = strrpos($html, "</tbody>");
        $html = substr($html, 0, $end + 8);

        $xml = simplexml_load_string($html);

        $partite = array();
        $giornate = array();

        $conta_giornata = 0;

        $actgiornata = new Giornata();

        foreach ($xml->tr as $row_partita) {

            $act = new Partita();
            $act->numero = (string)$row_partita->td[0];
            $act->giornata = (string)$row_partita->td[1];
            $act->dataora = (string)$row_partita->td[2];
            $act->squadra_casa = (string)$row_partita->td[3];
            $act->squadra_ospite = (string)$row_partita->td[4];
            $act->risultato = (string)$row_partita->td[5];
            $act->risultato = str_replace(' ', '', $act->risultato);

            $count = 0;
            foreach ($row_partita->td[6]->span->attributes() as $attr) {
                $count++;
                if ($count == 1) {
                    $act->numerogara = explode('_',(string)$attr);
                    $act->numerogara = $act->numerogara[1];
                }
            }

            $results = explode("-", $act->risultato);

            $act->res_squadra_casa = $results[0];
            $act->res_squadra_ospite = $results[1];

            $act->luogo = $row_partita->td[6]->img->attributes('title');
            $act->luogo = $row_partita->td[6]->img->attributes();

            $count = 0;
            foreach ($row_partita->td[6]->img->attributes() as $attr) {
                $count++;
                if ($count == 4) {
                    $act->luogo = (string)$attr;
                }
            }

            // 13/10/18 19:30
            $act->startD = '20'.substr($act->dataora, 6, 2).'-'.substr($act->dataora, 3, 2)
                            .'-'.substr($act->dataora, 0, 2);
            $act->startH = substr($act->dataora, 9, 5);
            $act->endH = '23:30';

            $date = new DateTime($act->startD);
            $act->startW = $date->format("W");

            if ($conta_giornata == $act->giornata) {
                $actgiornata->partite[] = $act;
            } else {
                $actgiornata = new Giornata();
                $actgiornata->giornata = $act->giornata;
                $actgiornata->partite = [];
                $actgiornata->partite[] = $act;

                $giornate[] = $actgiornata;

                $conta_giornata = $actgiornata->giornata;
            }

            $partite[] = $act;
        }

        $returnResult->success = 1;
        $returnResult->returnObject = $giornate;

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;

}


function decodeRankingPage($url)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

            // $url = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=45877&SId=&PId=9653&btFiltro=CERCA';

        ini_set("allow_url_fopen", 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        curl_close($ch);

        // print_r($url);

        if ($url == '2dif_18_19.htm') {
            $html = file_get_contents("2dif_18_19.htm");
        }

        $start = strrpos($html, "<table class=");
        $html = substr($html, $start);
        $start = strrpos($html, "</thead>");
        $html = substr($html, $start + 8);
        $end = strrpos($html, "</table>");
        $html = substr($html, 0, $end - 1);

        $html = "<tbody>" . $html . "</tbody>";

            // echo ">>HTML>> ".$html." <<HTML<< ";

        $xml = simplexml_load_string($html);

            // print_r($xml);

        $rankings = array();

        foreach ($xml->tr as $row_ranking) {

            $act = new RankingItem();
            $act->ordine = (string)$row_ranking->td[0];
            $act->squadra = (string)$row_ranking->td[1];
            $act->punti = (string)$row_ranking->td[2];
            $act->pg = (string)$row_ranking->td[3];
            $act->pv = (string)$row_ranking->td[4];
            $act->pp = (string)$row_ranking->td[5];
            $act->sf = (string)$row_ranking->td[6];
            $act->ss = (string)$row_ranking->td[7];
            $act->qs = (string)$row_ranking->td[8];

            $rankings[] = $act;

                // print_r($act);
        }

        $returnResult->success = 1;
        $returnResult->returnObject = $rankings;

    } catch (Exception $e) {

            // print_r($e);
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;
}


function decodeLitRankingPage($lit_campionato, $lit_girone)
{
    $gironiOk = [];

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";
        $returnResult->returnObject = [];

        $url = 'http://www.fipavpiacenza.it/mobile/risultati.asp';
        $lit_campionato = htmlspecialchars($lit_campionato);
        $completeCampionati = decodeGroupsPage($url, $lit_campionato);

        $campionati = $completeCampionati->returnObject;
        foreach($campionati as $campionato ) {
            $found = strpos(strtoupper($campionato->girone), strtoupper($lit_girone));
            if ($found === false) {
            } else {
                $gironiOk[] = $campionato;
                $foundGirone = true;
            }
        }

        if (count($gironiOk) == 0) {
            $returnResult->success = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Nessun girone trovato!";
        }
        if (count($gironiOk) > 1) {
            $returnResult->success = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Ho trovato più di un girone!";
            $returnResult->returnObject = $gironiOk;
        }
        if (count($gironiOk) == 1) {
            $CId = $gironiOk[0]->idgirone;
            $url_fipav = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId='.$CId.'&SId=&PId=9653&btFiltro=CERCA';
            $returnResult = decodeRankingPage($url_fipav);
        }
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;

}


function decodeGroupsPage($url, $lit_campionato)
{

    $groups = array();

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

        ini_set("allow_url_fopen", 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        curl_close($ch);

        $html = file_get_contents($url);

            // echo ">>HTML>> ".$html." <<HTML<< ";

        $start = strrpos($html, "<div style=");
        $html = substr($html, $start);
        $end = strrpos($html, "</div>");
        $html = substr($html, 0, $end - 1);

        $html = str_replace('><a href="/mobile/risultati.asp?CampionatoId=', ' class="', $html);
        $html = str_replace('</a>', '', $html);

            // $html = "<tbody>".$html."</tbody>";
            // echo   "   <br> " . $html . "   <br> ";

        $xml = simplexml_load_string($html);
            // print_r($xml);
            // print_r($xml->ul[0]->li);
        $count = 0;

        foreach ($xml->ul as $row_group) {

            if ($lit_campionato !== null && $lit_campionato !== undefined
                && $lit_campionato !== "") {
                $camp = (string)$xml->h2[$count];
                $found = strpos(strtoupper($camp), strtoupper($lit_campionato));
                if ($found === false) {
                    $count++;
                    continue;
                }
            }

            foreach ($row_group->li as $row_girone) {
                $act = new GroupItem();
                $act->campionato = (string)$xml->h2[$count];
                    // print_r($row_girone);
                $act->girone = (string)$row_girone[0];
                $act->idgirone = (string)$row_girone->attributes()->class;
                    // print_r($act);
                $groups[] = $act;
            }
            $count++;
        }

        $returnResult->success = 1;
        $returnResult->returnObject = $groups;

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;
}

function decodePartita($url, $squadra, $last, $next)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

            // $url = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=45877&SId=&PId=9653&btFiltro=CERCA';

        ini_set("allow_url_fopen", 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        curl_close($ch);

            /*
            echo '$html2: >> '.$url;
            echo '<< ';
            echo $html;
         */

        $start = strrpos($html, "<tbody");
        $html = substr($html, $start);
        $end = strrpos($html, "</tbody>");
        $html = substr($html, 0, $end + 8);

        $xml = simplexml_load_string($html);

        $partite = array();
        $giornate = array();

        $conta_giornata = 0;

        $actgiornata = new Giornata();

        $foundNext = false;

        foreach ($xml->tr as $row_partita) {

            if ($next) {
                if ($foundNext) {
                    continue;
                }
            }

            $act = new Partita();
            $act->numero = (string)$row_partita->td[0];
            $act->giornata = (string)$row_partita->td[1];
            $act->dataora = (string)$row_partita->td[2];
            $act->squadra_casa = (string)$row_partita->td[3];
            $act->squadra_ospite = (string)$row_partita->td[4];
            $act->risultato = (string)$row_partita->td[5];
            $act->risultato = str_replace(' ', '', $act->risultato);

            $results = explode("-", $act->risultato);

            $act->res_squadra_casa = $results[0];
            $act->res_squadra_ospite = $results[1];

            if ($squadra != null && $squadra !== '') {
                $found = strpos($act->squadra_casa, $squadra);
                if ($found === false) {
                    $found = strpos($act->squadra_ospite, $squadra);
                }
                if ($found === false) {
                    continue;
                }
            }

            if ($last) {
                if ($act->res_squadra_casa !== '' && $act->res_squadra_casa !== '0') {
                } else {
                    continue;
                }
            }

            if ($next) {
                if ($act->res_squadra_casa !== ''
                    && $act->res_squadra_casa !== null) {
                    continue;
                } else {
                    $foundNext = true;
                }
            }
            // print_r($act);

            $act->luogo = $row_partita->td[6]->img->attributes('title');
            $act->luogo = $row_partita->td[6]->img->attributes();

            $count = 0;
            foreach ($row_partita->td[6]->img->attributes() as $attr) {
                $count++;
                if ($count == 4) {
                    $act->luogo = (string)$attr;
                }
            }

            if ($conta_giornata == $act->giornata) {
                $actgiornata->partite[] = $act;
            } else {
                $actgiornata = new Giornata();
                $actgiornata->giornata = $act->giornata;
                $actgiornata->partite = [];
                $actgiornata->partite[] = $act;

                $giornate[] = $actgiornata;

                $conta_giornata = $actgiornata->giornata;
            }

            $partite[] = $act;
        }

        if ($last) {
            $act = end($partite);
            $partite = [];
            $partite[] = $act;
        }

        $returnResult->success = 1;
        $returnResult->returnObject = $partite;

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;

}


function literalSearch_CampAndSq($lit_campionato, $lit_squadra, $operation)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

        $foundCampionato = false;

        $local = false;

        if ($local) {
            $url_campionati = 'campionati.html';
        } else {
            $url_campionati = 'http://www.fipavpiacenza.it/mobile/risultati.asp';
        }

        // echo $url_campionati;
        $completeCampionati = decodeGroupsPage($url_campionati);
        // print_r($completeCampionati );
        // echo json_encode($completeCampionati);
        $campionati = $completeCampionati->returnObject;
        // print_r($campionati);

        // Recupero i campionati validi
        if ($lit_campionato === null || $lit_campionato === '') {
            $campsOk = $campionati;
        } else {

            $campsOk = [];
            $contCampFound = 0;
            foreach ($campionati as $campionato) {
                $found = strpos(strtoupper($campionato->campionato), strtoupper($lit_campionato));
                if ($found === false) {
                } else {
                    $campsOk[] = $campionato;
                    $foundCampionato = true;
                }
            }

            if (!$foundCampionato) {
                $returnResult->success = 0;
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Nessun campionato trovato con " . $lit_campionato;

                return $returnResult;
            }
        }


        $returnResult->returnMessages[] = "Trovato il campionato!";

        // print_r($campsOk);

        // per i campionati trovati recupero le squadre e in particolare quella ricercata
        // per farlo passo dalle classifiche dei vari campionati
        $teamsOk = [];
        foreach ($campsOk as $campionato) {

            if ($local) {
                $url_classifica = '2dif_18_19.htm';
            } else {
                $url_classifica = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=' .
                    $campionato->idgirone . '&SId=&PId=9653&btFiltro=CERCA';
            }
            $completeResult2 = decodeRankingPage($url_classifica);
            foreach ($completeResult2->returnObject as $team) {

                $found = strpos(strtoupper($team->squadra), strtoupper($lit_squadra));
                if ($found === false) {
                } else {
                    $team->campionato = $campionato;
                    $teamsOk[] = $team;
                }
            }

            if (count($teamsOk) > 1) {
                $moreCampionati = false;
                $prec = '';
                foreach ($teamsOk as $team) {
                    if ($prec === '') {
                        $prec = $team->campionato->campionato;
                    } else {
                        if ($prec !== $team->campionato->campionato) {
                            $moreCampionati = true;
                        }
                    }
                }

                if ($moreCampionati) {
                    $returnResult->success = 0;
                    $returnResult->returnObject = $teamsOk;
                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Trovate più squadre con " . $lit_squadra;
                    return $returnResult;
                    break;
                }
            }
        }

        if (count($teamsOk) == 0) {
            $returnResult->success = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Nessuna squadra trovata con " . $lit_squadra;
            return $returnResult;
        }

        $returnResult->returnMessages[] = "Trovato la squadra!";
        // print_r($teamsOk);

        // recupero quindi le partite per le squadre/campionati trovati
        $returnResult->returnObject = [];
        $matchesOk = [];
        foreach ($teamsOk as $team) {

            if ($local) {
                $url_partite = '';
            } else {
                $url_partite = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=' .
                    $team->campionato->idgirone . '&SId=&PId=9653&btFiltro=CERCA';
            }

            if ($operation === 'next') {
                $completeResult3 = decodePartita($url_partite, $team->squadra, false, true);
            }
            if ($operation === 'last') {
                $completeResult3 = decodePartita($url_partite, $team->squadra, true, false);
            }

            // print_r($completeResult3);

            if (count($completeResult3->returnObject) == 0) {
                continue;
            }

            $returnResult->success = 1;
            $returnResult->returnObject[] = $completeResult3->returnObject[0];

            $returnResult->returnMessages[] = "Trovata la partita!";

            // break;
        }

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;

}


function literalSearch_Camp($lit_campionato)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

        $foundCampionato = false;

        $local = false;

        if ($local) {
            $url_campionati = 'campionati.html';
        } else {
            $url_campionati = 'http://www.fipavpiacenza.it/mobile/risultati.asp';
        }

        // echo $url_campionati;
        $completeCampionati = decodeGroupsPage($url_campionati);
        // print_r($completeCampionati );
        // echo json_encode($completeCampionati);
        $campionati = $completeCampionati->returnObject;
        // print_r($campionati);

        // Recupero i campionati validi
        if ($lit_campionato === null || $lit_campionato === '') {
            $campsOk = $campionati;
        } else {

            $campsOk = [];
            $contCampFound = 0;
            foreach ($campionati as $campionato) {
                $found = strpos(strtoupper($campionato->campionato), strtoupper($lit_campionato));
                if ($found === false) {
                } else {
                    $foundC = false;
                    foreach ($campsOk as $camp) {
                        if ($campionato->campionato === $camp->campionato) {
                            $foundC = true;
                        }
                    }
                    if (!$foundC) {
                        $campionato->girone = '';
                        $campsOk[] = $campionato;
                    }
                    $foundCampionato = true;
                }
            }

            if (!$foundCampionato) {
                $returnResult->success = 0;
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Nessun campionato trovato con " . $lit_campionato;

                return $returnResult;
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $campsOk;
        }

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;

}

function literalSearch_Sq($lit_campionato, $lit_squadra)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation!";

        $foundCampionato = false;

        $local = false;

        if ($local) {
            $url_campionati = 'campionati.html';
        } else {
            $url_campionati = 'http://www.fipavpiacenza.it/mobile/risultati.asp';
        }

        $completeCampionati = decodeGroupsPage($url_campionati);
        $campionati = $completeCampionati->returnObject;

        // Recupero i campionati validi
        if ($lit_campionato === null || $lit_campionato === '') {
            $campsOk = $campionati;
        } else {

            $campsOk = [];
            $contCampFound = 0;
            foreach ($campionati as $campionato) {
                $found = strpos(strtoupper($campionato->campionato), strtoupper($lit_campionato));
                if ($found === false) {
                } else {
                    $campsOk[] = $campionato;
                    $foundCampionato = true;
                }
            }

            if (!$foundCampionato) {
                $returnResult->success = 0;
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Nessun campionato trovato con " . $lit_campionato;

                return $returnResult;
            }
        }

        // per i campionati trovati recupero le squadre e in particolare quella ricercata
        // per farlo passo dalle classifiche dei vari campionati
        $teamsOk = [];
        foreach ($campsOk as $campionato) {

            if ($local) {
                $url_classifica = '2dif_18_19.htm';
            } else {
                $url_classifica = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId=' .
                    $campionato->idgirone . '&SId=&PId=9653&btFiltro=CERCA';
            }
            $completeResult2 = decodeRankingPage($url_classifica);
            foreach ($completeResult2->returnObject as $team) {

                $found = strpos(strtoupper($team->squadra), strtoupper($lit_squadra));
                if ($found === false) {
                } else {
                    $team->campionato = $campionato;
                    $teamsOk[] = $team;
                }
            }

            if (count($teamsOk) > 1) {
                $moreCampionati = false;
                $prec = '';
                foreach ($teamsOk as $team) {
                    if ($prec === '') {
                        $prec = $team->campionato->campionato;
                    } else {
                        if ($prec !== $team->campionato->campionato) {
                            $moreCampionati = true;
                        }
                    }
                }

                if ($moreCampionati) {
                    $returnResult->success = 0;
                    $returnResult->returnObject = $teamsOk;
                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Trovate più squadre con " . $lit_squadra;
                    return $returnResult;
                    break;
                } else {
                    $teamOk = end($teamsOk);
                    $teamsOk = [];
                    $teamsOk[] = $teamOk;
                }
            }
        }

        if (count($teamsOk) == 0) {
            $returnResult->success = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Nessuna squadra trovata con " . $lit_squadra;
            return $returnResult;
        }

        $returnResult->success = 1;
        $returnResult->returnObject = $teamsOk;

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
    }

    return $returnResult;
}
