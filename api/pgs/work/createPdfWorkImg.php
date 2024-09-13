<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

header('Content-Type: application/pdf');

    require_once("../dbconnect.php");
    require_once("work.php");
    require_once("../practice/practice.php");
    require_once("../practice/operations.php");
    require_once("fpdf.php");
    require_once("operations.php");

    error_reporting(E_ERROR | E_PARSE);

    $idwork = $_GET['idwork'];
    $completeResult = createPdfForWork($idwork);
    echo $completeResult;

    function createPdfForWork($idwork){

        $filters = array();
        $filters[id] = $idwork;
        $completeResult = getSingleWork($filters);

        $pdf = new FPDF('L');

        $letters = ["A","B","C","D","E","F","G","H","I","J","K","L",
        "M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",":","/",";",","];

        $work = $completeResult->returnObject;

        $hostport = $_SERVER[HTTP_HOST];

        if ($hostport == "localhost:8888"){
            $baseUrl = "/Users/guidonaturani/Library/Mobile Documents/com~apple~CloudDocs/Projects/my/Pgs/PgsIonic/src/assets/imgs/";
        }
        else {
            $baseUrl = "http://www.parrocchiacarpaneto.com/pgs/assets/imgs/";
        }

        if ($hostport == "localhost:8888"){
            $baseCircuitUrl = "/Users/guidonaturani/Library/Mobile Documents/com~apple~CloudDocs/Projects/my/Pgs/PgsIonic/circuitsImages/";
            $basePracticeUrlExist = "/Users/guidonaturani/Library/Mobile Documents/com~apple~CloudDocs/Projects/my/Pgs/PgsIonic/practicesImages/";
            $basePracticeUrl = "/Users/guidonaturani/Library/Mobile Documents/com~apple~CloudDocs/Projects/my/Pgs/PgsIonic/practicesImages/";
        }
        else {
            $baseCircuitUrl = "http://www.parrocchiacarpaneto.com/pgs/circuitsImages/";
            $basePracticeUrlExist = $_SERVER['DOCUMENT_ROOT'] ."pgs/practicesImages/";
            $basePracticeUrl = "http://www.parrocchiacarpaneto.com/pgs/practicesImages/";
        }

        foreach ($work->practices as $practice) {

            $singleWidth = 4.5;
            $singleHeight = 4.45;
            $startFieldX = 180;
            $startFieldY = 30;

            $filters = array();
            $filters[id] = $practice->id;
            $completePratice = getSinglePractice($filters)->returnObject;
            //var_dump($completePratice);

            if ($practice->title == "VUOTO") {
                continue;
            }

            $pdf->AddPage();

            $pdf->SetFont('Arial','B',16);
            $work_title = iconv('UTF-8', 'windows-1252', $work->title);
            // $pdf->Cell(40,10,$work_title." / ".substr($work->wdate, 0, 10) );
            $pdf->Cell(40,10, $work_title." / ".substr($work->wdate, 0, 10) .' / '.$work->duration_m.'m ' );

            $pdf->SetXY(280,10);
            $pdf->Cell(1,10,$pdf->PageNo());

            $pdf->Line(5,20,290,20);

            $pdf->SetY(20);
            $pdf->SetFont('Arial','B',16);
            $title = iconv('UTF-8', 'windows-1252',  $practice->partial.' '. $practice->duration_m.'m  '.$practice->title);
            $pdf->Cell(130,10,$title);

            $pdf->SetFont('Arial','',16);
            $pdf->SetX(30);
            $pdf->SetY(30);
            $description = iconv('UTF-8', 'windows-1252', $completePratice->description);
            $pdf->MultiCell(150,7,$description);

            $practiceImage = $basePracticeUrlExist.'practice_'.$practice->id.'.png';
            // $pdf->MultiCell(350,4,$practiceImage);
            if (file_exists($practiceImage)) {
                $practiceImage = $basePracticeUrl.'practice_'.$practice->id.'.png';
                try {
                    $pdf->Image($practiceImage,180,30,100);
                } catch (Exception $e) {
                    // $pdf->MultiCell(350,14,$e->getMessage());
                }
            }

            $first = true;
            foreach ($completePratice->fieldItems as $fieldItem) {

                if ($first == true){
                    $fieldImage = $baseUrl . 'field_v.png';
                    $pdf->Image($fieldImage,180,30,82,130);
                    $first = false;
                }

                if (strpos($fieldItem->class_init, 'item-p-blue') !== false) {
                    $imageName = "p-blue";
                }
                if (strpos($fieldItem->class_init, 'item-p-red') !== false) {
                    $imageName = "p-red";
                }
                if (strpos($fieldItem->class_init, 'item-a-green') !== false) {
                    $imageName = "p-green";
                }
                if (strpos($fieldItem->class_init, 'item-ball') !== false) {
                    $imageName = "ball_small";
                }
                if (strpos($fieldItem->class_init, 'item-cinesino') !== false) {
                    $imageName = "cinesino";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-up') !== false) {
                    $imageName = "arrow_up";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-down') !== false) {
                    $imageName = "arrow_down";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-left') !== false) {
                    $imageName = "arrow_left";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-right') !== false) {
                    $imageName = "arrow_right";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-down-l') !== false) {
                    $imageName = "arrow_downl";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-down-r') !== false) {
                    $imageName = "arrow_downr";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-up-l') !== false) {
                    $imageName = "arrow_upl";
                }
                if (strpos($fieldItem->class_init, 'item-arrow-up-r') !== false) {
                    $imageName = "arrow_upr";
                }

                $imageUrl = $baseUrl . $imageName . ".png";

                $row = array_search($fieldItem->row, $letters);

                $actCellX = $startFieldX + $fieldItem->col * $singleWidth;
                $actCellY = $startFieldY + $row * $singleHeight;
                $pdf->Image($imageUrl,$actCellX,$actCellY,10,10);

                if ($fieldItem->name != ''){
                    $pdf->SetTextColor(255,255,255);
                    $pdf->SetFont('Arial','',12);
                    $pdf->SetXY($actCellX+2.8,$actCellY+3);
                    $pdf->Cell($singleWidth,$singleHeight,$fieldItem->name,0,0,'C');
                    $pdf->SetTextColor(0,0,0);
                }

                /*
                $actCellX = $startFieldX + 0 * $singleWidth;
                $actCellY = $startFieldY + 30 * $singleHeight;
                $pdf->Rect($actCellX,$actCellY,10,10,F);
                */

            }

            $count = 0;
            foreach ($completePratice->circuitItems as $circuitItem) {
                $count++;

                $description = iconv('UTF-8', 'windows-1252', $circuitItem->description);
                $equipment = iconv('UTF-8', 'windows-1252', $circuitItem->equipment);

                $pdf->SetFont('Arial','',12);
                $pdf->MultiCell(100,7,$count.") ". $description);

                $pdf->SetFont('Arial','I',12);
                $pdf->MultiCell(150,7,"           ". $equipment);

            }

            $startFieldX = 130;
            $startFieldY = 22;
            $circuitImgWidth = 35;
            $circuitImgHeight = 35;
            $count = 0;
            $count4row = 0;
            foreach ($completePratice->circuitItems as $circuitItem) {

                $count++;
                $imageName = "circuit_".$circuitItem->id;
                $imageUrl = $baseCircuitUrl . $imageName . ".png";

                try {

                    $count4row++;

                    if($count4row == 4) {
                        $count4row = 1;
                        $startFieldY = $startFieldY + 10 + $circuitImgHeight;
                    }

                    try {
                        $actCellX = $startFieldX + ( ( $count4row - 1 ) *  $circuitImgWidth ) + ( $count4row - 1 ) * 10 ;
                        $actCellY = $startFieldY;
                        $pdf->SetFont('Arial','',8);
                        $pdf->SetXY($actCellX,$actCellY);
                        $pdf->Cell(1,10,$count.") ");
                        $pdf->Image($imageUrl,$actCellX + 5,$actCellY, $circuitImgWidth);

                    }catch(Exception $Err){
                        try {
                            $pdf->Image($imageUrl,$actCellX + 5,$actCellY, $circuitImgWidth,0,"JPEG");
                        }catch(Exception $Err2){
                            error_log (print_r($Err2,true), 0);
                        }
                    }

                } catch(Exception $err3) {

                }

            }

        }

        return $pdf->Output("S");

    }

?>