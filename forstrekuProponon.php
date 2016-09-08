<?php

/**
 * Paĝo por forigo de proponoj kaj rilataj informoj.
 * (Se oni forigas proponon, oni aŭtomate ankaŭ forigas
 *  ĉiujn informojn rilate al ĝi en la tabeloj ŝlosoj,
 *  voĉdonoj kaj voĉdonantoj.)
 *
 * Nur ĜenSek povas uzi tiun paĝon.
 *
 *  $_REQUEST['id']:
 *     la identigilo de la forigenda propono.
 *
 *
 *  $_POST['konfirmo']:
 *    se 'konfirmo' (= se oni sendis per la formularo), ni forstrekas.
 *    Alikaze ni montras konfirmo-formularon.
 *
 */


session_start();

include('ink.php');

$flagoj = kontroluUzanton('forstrekuProponon.php?id='.$_REQUEST['id']);

metu_kapon("Forstreku proponon", 'GxenSek');
laMenuon();

if(!estas_GxenSek())
  die('Nur la ĜenSek povas forstreki proponojn!');



/**
 * Forigas proponon kaj ĉiujn rilatajn informojn pri ĝi.
 * @param int $id la identigilo pri la propono.
 * @return true se sukcese forigis, false, se estis problemoj.
 */
function forstrekuProponon($id)
{
  $jam = dbElektu('proponoj', "id = '$id'");
  if(count($jam) == 0)
  {
    echo "Propono '$id' ne ekzistas!<br/>";
    return false;
  }
  if (count($jam) > 1) {
    echo "Ekzistas pluraj proponoj kun la ID '$id'!<br/>";
    return false;
  }
  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br>';
    return false;
  }
  return mysql_query('DELETE FROM '.dbPrefikso()."proponoj WHERE id = '$id'", $db) &&
    mysql_query('DELETE FROM '.dbPrefikso()."vocxdonoj WHERE propono = '$id'", $db) &&
    mysql_query('DELETE FROM '.dbPrefikso()."sxlosoj WHERE propono = '$id'", $db) && 
    mysql_query('DELETE FROM '.dbPrefikso()."vocxdonantoj WHERE propono = '$id'", $db);

}  // forstrekuProponon

$proponoj = dbElektu('proponoj', 'id = '.$_REQUEST['id']);
$propono = $proponoj[0];

// --------- informoj pri la propono. -------------
echo "<table class='propono-datumoj'>";

echo '<tr><th>Titolo</th><td/><td><strong>'.$propono['titolo']."</strong></td></tr>\n";
echo '<tr><th>Propono</th><td/><td>'.$propono['enhavo']."</td></tr>\n";
echo "</table>";



if(isset($_POST['konfirmo']) && $_POST['konfirmo'] == 'konfirmo')
{
  //------------ forstrekado ----------

  if(forstrekuProponon($_POST['id']))
  {
    echo '<p>Forstrekis proponon!</p><p>';
    ligilon("Reen al la propono", "propono.php?id=" . $_POST['id']);
    echo "</p>";
  }
  else
  {
    echo 'Renkontis eraron!<br>';
    echo mysql_error();
  }
}
else
{
  // ------------- konfirmo-formularo ---------

  ?>
  <form action="forstrekuProponon.php" method="post">
  <p>Ĉu vi certas ke vi volas forstreki la proponon <?php echo $_REQUEST['id']; ?>?</p>
  <p>Tio ankaŭ forigas ĉiujn informojn pri voĉdonrajtintoj, voĉdonantoj,
      donitaj voĉoj ktp.</p>
  <p>Normale vi nur volas forigi proponon, se vi erare kreis ĝin. Pasintajn proponojn ne forigu: tiel eblas ankoraŭ relegi pri ili, kaj ili ne vere ĝenas.</p>
<p>
  <input type="hidden" name="konfirmo" value="konfirmo">
  <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>">
  <input type="submit" value="Jes - forstreku!">
  <?php
  ligilon("Ne, reen al la propono.", "propono.php?id=".$_REQUEST['id']);
?>
</p>
  </form>
  <?php
}

?>
</body>
</html>