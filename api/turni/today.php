<?php

require_once("operations_2.php");

$data = getTodayData();

?>

<div style="width:100%; text-align: center">
<center>

<style>
.circle-text {
    display: table-cell;
    height: 300px;
 /*change this and the width for the size of your initial circle*/
    width: 300px;

    text-align: center;
    vertical-align: middle;
    border-radius: 50%;
  /*make it pretty*/
    background: #ff4545;
    color: white;
    font: 18px "josefin sans", arial;
  /*change this for font-size and font-family*/
}
</style>

<div class="circle-text">

    <h5>OGGI</h5>
    <h1>
        <?=$data->presenze?>
    </h1>
</div>
</center>
</div>

<?php

?>
