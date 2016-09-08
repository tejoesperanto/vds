<?php

session_start();

include('ink.php');

$flagoj = kontroluUzanton('menuo.php');
if(!($flagoj === false))
  laMenuon($flagoj);

function kreu()
{
  echo 'Saluton, '.$_SESSION['uzanto'].'!<br>';

  //$sql = 
  //if(

?>

<form action="board.php" method="get">
  Fill in an ID, or leave blank to create a new one: 
  <input type=text name="id">
  <input type="submit" value="Go!">
<br></form>
require('elsalutu.php');
<?php
}

//if(!isset($_SESSION['user']))
//  echo '<html><head><meta http-equiv="Refresh" content="0; url=ensalutu.php"></head></html>';
//else kreu();

?>
