<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("calendar.php");


    function getNextApp(){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation!";
            $today = date("Y-m-d");
            $resultCall = getCalendars();
            foreach ($resultCall->returnObject as $item) {
                if ($item->startD >= $today) {
                    $resultCall->returnObject = $item;
                    return $resultCall;
                }
            }

        } catch(Exception $e) {
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;
        }

        return $returnResult;
    }

    function getNextApps($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation!";
            $today = date("Y-m-d");
            $resultCall = getCalendars($filters);
            $apps = array();
            $count = 0;
            foreach ($resultCall->returnObject as $item) {
                if ($item->startD >= $today) {
                    $apps[] = $item;
                    $count++;
                    $returnResult->returnMessages[] = "add count: " . $count;
                    if ($count === 6) {
                        $resultCall->returnObject = $apps;
                        return $resultCall;
                    }
                }
            }
            return $resultCall;

        } catch(Exception $e) {
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;
        }

        return $returnResult;
    }


    function getSingleTeam($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT pgs_teams.id as team_id,
                                   pgs_teams.name as team_name,
                                   pgs_teams.calendarid as team_calendarid
                                 from pgs_teams
                            where pgs_teams.id = '$filters[idteam]'
                             ";

            $stmt = $conn->prepare($sqlString);

            $act = new Team();

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();

            $first = true;
            // output data of each row
            foreach ($rows as $row) {

                    $act->id = $row['team_id'];
                    $act->name = $row['team_name'];
                    $act->calendarId = $row['team_calendarid'];
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $act;

            $conn = null;

        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function getCalendars($filters = NULL){

        $local = false;
        error_log (">>>>>>>>>> START getCalendars", 0);

        $actinterval = 0;

        $hostport = $_SERVER[HTTP_HOST];
        if ($hostport == "localhost:8888"){
            $local = true;
        }

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation!";

            $url = 'https://www.googleapis.com/calendar/v3/calendars/s1fb88nv7shg18r2nvtjmqua78@group.calendar.google.com/events?key=AIzaSyDXnFjQZlOfh35c_h69gW1ljCujSJHhNHc';

            if ($filters != NULL) {

                // error_log (print_r($filters), 0);

                $returnResultCal = getSingleTeam($filters);
                // error_log (print_r($returnResultCal) , 0);

                $calendarid = $returnResultCal->returnObject->calendarId;
                // error_log ($calendarid , 0);


                $url = 'https://www.googleapis.com/calendar/v3/calendars/'.$calendarid.'/events?key=AIzaSyDXnFjQZlOfh35c_h69gW1ljCujSJHhNHc';
            }

            if ($local) {
                $url = 'events.json';
                $result = file_get_contents($url);
            } else {
                ini_set("allow_url_fopen", 1);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec($ch);
                curl_close($ch);
            }


            // error_log ($url , 0);

            $returnResult->returnMessages[] = "Url: " .$url;
            $returnResult->returnMessages[] = "Calendario Recuperato!";

            $obj = json_decode($result);
            $items = $obj->items;

            $apps = array();
            $today = date("Y-m-d");

            if ($filters[old] !== undefined && $filters[old] !== null && $filters[old] !== '') {
                $today = date('Y-m-d', strtotime('-15 day', strtotime($today) ) );
            }

            foreach ($items as $item) {

                $act = new Appointment();

                $act->description = $item->summary;
                $act->details = $item->description;

                $act->details = nl2br($act->details);

                $act->numero = '';

                try {
                    $datasplit = explode(':', $act->description);
                    $inutile = $datasplit[1];
                    $act->numero = $datasplit[0];
                } catch (Exception $err) {
                }

                $act->start = $item->start->dateTime;
                $act->end = $item->end->dateTime;
                $act->location = $item->location;

                $act->recurringEventId = "";
                $act->originalStartTime = "";

                try {
                    $act->originalStartTime = $item->originalStartTime->dateTime;
                    $act->recurringEventId = $item->recurringEventId;
                } catch (Exception $err) {
                }

                $act->startD = substr($item->start->dateTime,0,10);
                $act->startH = substr($item->start->dateTime,11,5);

                $date = new DateTime($act->startD);
                $act->startW = $date->format("W");

                $act->endD = substr($item->end->dateTime,0,10);
                $act->endH = substr($item->end->dateTime,11,5);

                if ($act->start >= $today) {
                    $apps[] = $act;
                }

                if ($item->recurringEventId != null) {
                    if ($item->status == "cancelled") {
                        foreach($apps as $itemRec) {
                            if ($itemRec->id == $item->recurringEventId) {
                                if (substr($item->originalStartTime->dateTime,0,16) == substr($itemRec->start,0,16)) {
                                    // print_r($item);
                                    // print_r($itemRec);
                                    $itemRec->toDelete = true;
                                }
                            }
                        }
                    }

                    if ($item->status == "confirmed") {
                        foreach($apps as $itemRec) {
                            if ($itemRec->id == $item->recurringEventId) {
                                if (substr($item->originalStartTime->dateTime,0,16) == substr($itemRec->start,0,16)) {
                                    // print_r($item);
                                    // print_r($itemRec);
                                    $itemRec->toDelete = true;
                                }
                            }
                        }
                    }

                }

                if ($item->recurrence != null) {
                    $recArray = $item->recurrence;
                    // print_r($recArray);

                    $freq = "";
                    $until = "";
                    $byday = "";
                    $interval = 1;
                    $exdate = [];

                    foreach ($recArray as $rec) {
                        if ( substr( $rec, 0, 5 ) === "RRULE" ) {
                            $rec = substr( $rec, 6 );
                            $recRules = explode(";", $rec);
                            // print_r($recRules);
                            foreach ($recRules as $rule) {
                                $data = explode("=", $rule);
                                if ($data[0] === "FREQ") {
                                    $freq = $data[1];
                                }
                                if ($data[0] === "UNTIL") {
                                    $until = $data[1];
                                }
                                if ($data[0] === "BYDAY") {
                                    $byday = $data[1];
                                }
                                if ($data[0] === "INTERVAL") {
                                    $interval = $data[1];
                                }
                            }
                        }
                        if ( substr( $rec, 0, 6 ) === "EXDATE" ) {
                            $recRules = explode(":", $rec);
                            $exdate = explode(",", $recRules[1]);
                        }
                    }

                    $dates = dateRange($item->start->dateTime, substr($until, 0, 8));
                    if ($byday !== '') {
                        $dates = array_filter($dates, function ($date) use ($byday) {
                            $day = $date->format("D");
                            $dayUp = strtoupper(substr($day,0,2));
                            if ( $dayUp === $byday ) {
                                return true;
                            }
                        });
                    }

                    if ($freq !== '') {
                        $startD = substr($item->start->dateTime,0,10);
                        $datetime = DateTime::createFromFormat('Y-m-d', $startD);
                        $dayOfWeek = $datetime->format('D');

                        /*
                        print_r("\n ");
                        print_r($dayOfWeek);
                        print_r(" ");
                        print_r($freq);
                        print_r(" ");
                        print_r($startD);
                        print_r(" ");
                        print_r($interval);
                        print_r(" ");
                        */

                        $actinterval = 0;

                        $dates = array_filter($dates, function ($date) use ($dayOfWeek, $interval, $actinterval) {
                            $day = $date->format("D");

                            if ( $day === $dayOfWeek ) {
                                    return true;
                            }
                        });

                        $okDates = [];
                        if ($interval !== 1) {
                            $actInterval = -1;
                            foreach($dates as $date) {
                                $actInterval++;
                                if ($actInterval == $interval ) {
                                    $okDates[] = $date;
                                    $actInterval = 0;
                                }
                            }
                            $dates = $okDates;
                        }

                    }

                    if (count($exdate) > 0 ){
                        $dates = array_filter($dates, function ($date) use ($exdate) {
                            $ok = true;
                            foreach ($exdate as $dtex) {
                                // $datetoex = new DateTime($dtex);
                                // echo "date_compare -> ".date_format($date,"Ymd"). " ";
                                // echo "date_to_ex -> ".substr($dtex,0,8)." <br> " ;

                                if (date_format($date,"Ymd") === substr($dtex,0,8) ) {
                                    $ok = false;
                                }
                            }
                            return $ok;
                        });
                    }

                    // print_r($dates);

                    foreach($dates as $date) {

                        $act = new Appointment();
                        $act->id = $item->id;
                        $act->description = $item->summary;
                        $act->details = $item->description;
                        $act->location = $item->location;

                        $dateS = date_format($date,"Y-m-d");
                        $start = substr($item->start->dateTime,10);
                        $act->start = $dateS.$start;
                        $end = substr($item->end->dateTime,10);
                        $act->end = $dateS.$end;

                        $act->startD = $dateS;
                        $act->startH = substr($item->start->dateTime,11,5);

                        $act->recurringEventId = "";
                        $act->originalStartTime = "";

                        try {
                            // $act->originalStartTime = $item->originalStartTime->dateTime;
                            // $act->recurringEventId = $item->recurringEventId;    
                        } catch (Exception $err) {
                        }        

                        $date = new DateTime($act->startD);
                        $act->startW = $date->format("W");

                        $act->endD = $dateS;
                        $act->endH = substr($item->end->dateTime,11,5);

                        if ($act->start >= $today) {

                            $toAdd = true;

                            foreach($apps as $itemRec) {
                                if ($itemRec->recurringEventId == $act->id && $itemRec->originalStartTime !== "") {

                                    if ($itemRec->originalStartTime !== undefined && $itemRec->originalStartTime !== null) {
                                        // print_r($itemRec->originalStartTime);
                                        
                                        try {
                                            if (substr($itemRec->originalStartTime,0,16) == substr($act->start,0,16)) {
                                                // print_r($act);
                                                // print_r($itemRec);
                                                
                                                $toAdd = false;
                                            }
                                        } catch (Exception $err) {
                                        } 

                                    }
   
                                }
                            }

                            if ($toAdd) {
                                $apps[] = $act;
                            }
                        }
                    }

                }

            }

            usort($apps, 'sortCalendar');

            $okapps = [];
            foreach($apps as $app) {
                if ($app->toDelete == null || $app->toDelete == false) {
                    $okapps[] = $app;
                }
            }
            usort($okapps, 'sortCalendar');

            $returnResult->returnMessages[] = "Finito!";
            // $returnResult->returnMessages[] = $obj;

            $returnResult->success = 1;
            $returnResult->returnObject = $okapps;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function dateRange($begin, $end, $interval = null)
    {
        $begin = new DateTime($begin);
        $end = new DateTime($end);
        // Because DatePeriod does not include the last date specified.
        $end = $end->modify('+1 day');
        $interval = new DateInterval($interval ? $interval : 'P1D');

        return iterator_to_array(new DatePeriod($begin, $interval, $end));
    }


    function getMatches(){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation!";

            $url = 'https://www.googleapis.com/calendar/v3/calendars/s1fb88nv7shg18r2nvtjmqua78@group.calendar.google.com/events?key=AIzaSyDXnFjQZlOfh35c_h69gW1ljCujSJHhNHc';
            //$url = "http://api.openweathermap.org/data/2.5/weather?id=5128638&lang=en&units=metric&APPID={APIKEY}";

            ini_set("allow_url_fopen", 1);
            $json = file_get_contents($url);
            $obj = json_decode($json);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $obj = json_decode($result);
            $items = $obj->items;

            $apps = array();
            $today = date("Y-m-d");

            foreach ($items as $item) {

                $act = new Appointment();

                $act->description = $item->summary;
                $act->start = $item->start->dateTime;
                $act->end = $item->end->dateTime;
                $act->location = $item->location;

                if ($act->start >= $today && is_numeric(substr($act->description, 0, 1)) ) {
                    $apps[] = $act;
                }
            }

            $returnResult->returnMessages[] = "Finito!";
            // $returnResult->returnMessages[] = $obj;

            usort($apps, 'sortCalendar');

            $returnResult->success = 1;
            $returnResult->returnObject = $apps;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function sortCalendar($a, $b) {
        if ($a->start  == $b->start) {
            // return 0 if equal
            return 0;
        }
        return ($a->start < $b->start) ? -1 : 1;
    }

?>