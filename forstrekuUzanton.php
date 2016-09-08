<?php

/**
 * Paĝo por forstreko de uzanto.
 *
 * Nur uzebla de ĜenSek.
 *
 *
 *  $_REQUEST['uzanto']:
 *     la uzantonomo de la forstrekenda uzanto.
 *
 *
 *  $_POST['konfirmo']:
 *    se 'konfirmo' (= se oni sendis per la formularo), ni forstrekas.
 *    Alikaze ni montras konfirmo-formularon.
 *
 */

session_start();

include('ink.php');

$flagoj = kontroluUzanton('forstrekuUzanton.php?uzanto='.$_REQUEST['uzanto']);

metu_kapon("Forstreku uzanton", "GxenSek");
laMenuon();

if(!estas_GxenSek())
  die('Nur la ĜenSek povas forstreki uzantojn!');


/**
 * Forstrekas la uzanton.
 *
 * Antauxe ni faras kelkajn sekurigajn kontrolojn
 * (ke estas precize unu tia uzanto en la datumbazo ktp.)
 * @param string $uzanto la uzantonomo
 * @return boolean true, se ni sukcese forigis, alikaze false.
 */
function forstrekuUzanton($uzanto)
{
  $jam = dbElektu('uzantoj', "uzanto = '$uzanto'");
  if(count($jam) == 0)
  {
    echo "Uzanto '$uzanto' ne ekzistas!<br/>";
    return false;
  }
  if (count($jam) > 1) {
    echo "Ekzistas pluraj uzantoj kun la nomo '$uzanto'!<br/>";
    return false;
  }
  if(estas_GxenSek($jam[0]['flagoj']))
  {
    echo 'Ne povas forstreki na la ĜenSek!<br/>';
    return false;
  }
  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br/>';
    return false;
  }
  return mysql_query('DELETE FROM '.dbPrefikso()."uzantoj WHERE uzanto = '$uzanto'", $db);
}

// -------------------- La agado ---------------------------------------


if($_POST['konfirmo'] == 'konfirmo')
{
  // ---------------- vere forstreku --------
  if(forstrekuUzanton($_POST['uzanto']))
  {
    echo '<p>Forstrekis uzanton!<br/>';
    ligilon("reen al la listo", "uzantoj.php"); 
    echo "</p>\n";
  }
  else
  {
    echo '<p>Ne sukcesis forstreki uzanton!<br/>';
    echo mysql_error();
    echo "</p>\n";
  }
}
else
{
  // ------------- Konfirmo-formularo  -------

  ?>
  <p>Ĉu vi certas ke vi volas forstreki la uzanton <?php echo $_REQUEST['uzanto']; ?>?</p>
 <p>Eble sufiĉas <a href="redaktuUzanton.php?uzanto=<?php echo $_REQUEST['uzanto']; ?>">ŝanĝi liajn rajtojn</a> al <em>Observanto</em>?</p>
  <p>Informoj pri liaj pasintaj voĉdonrajtoj kaj voĉoj (por publikaj voĉdonoj) restas en la datumbazo.</p>
  <form action="forstrekuUzanton.php" method="post">
  <p>
  <input type="hidden" name="konfirmo" value="konfirmo">
  <input type="hidden" name="uzanto" value="<?php echo $_REQUEST['uzanto']; ?>">
  <input type="submit" value="Jes - forstreku!">
<?php ligilon("Ne, reen", "uzantoj.php"); ?>
</p>
  </form>
										      
  <?php
}

// ----------------- Fino ----------------

?>
</html>
</body>