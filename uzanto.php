<?php

/**
 * Informoj pri la propra uzantokonto, kaj eblo ŝanĝi ion.
 *
 * Tiu paĝo estas alirebla por ĉiu uzanto, kaj ĉiam montras la informon
 * de tiu uzanto, kiu rigardas ĝin (do, $_SESSION['uzanto'])
 *
 * La paĝo enhavas plurajn formularojn, kiuj
 * sendas la jenajn datumojn:
 *
 * Pasvortŝanĝo:
 *
 * $_POST['pasvorto']      - malnova pasvorto
 * $_POST['novPasvorto']   - nova pasvorto
 * $_POST['novPasvorto2']  - nova pasvorto
 *
 *  Se malnova pasvorto kongruas kun datumbazo kaj la du novaj
 *  pasvortoj kongruas, ni ŝanĝas la pasvorton.
 *
 * Uzantinformoj:
 *
 * $_POST['retadreso']    - retadreso de la uzanto
 * $_POST['plena_nomo']   - plena nomo de la uzanto
 *
 *
 * Krome la paĝo montras la rolojn de la uzanto.
 */


session_start();

require_once('ink.php');

$flagoj = kontroluUzanton();
$db = dbMalfermu();


metu_kapon("Viaj informoj");

laMenuon($flagoj);

/**
 * Ŝanĝas la pasvorton.
 * @param string $uzanto la uzantonomo.
 * @param string $pasvorto la nova pasvorto.
 */
function sxangxuPasvorton($uzanto, $pasvorto)
{
  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br>';
    return false;
  }
  $kr_pasvorto = haketuPV($uzanto, $pasvorto);

  return mysql_query('UPDATE '.dbPrefikso()."uzantoj ".
		     "   SET pasvorto = '$kr_pasvorto' ".
		     " WHERE uzanto = '$uzanto'",
		     $db);
}


/**
 * Ŝanĝas nomon kaj/aŭ retadreson.
 * @param string $uzanto la uzantonomo.
 * @param string $retadreso la retpoŝtadreso
 * @params string $plena_nomo la plena nomo
 */
function sxangxuInformojn($uzanto, $retadreso, $plena_nomo)
{
  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br>';
    echo mysql_error();
  }
  if (!mysql_query("UPDATE " .dbPrefikso()."uzantoj " .
		    "   SET retadreso  = '" .
		   mysql_escape_string(htmlspecialchars($retadreso)) ."', " .
		    "       plena_nomo = '" .
		   mysql_escape_string(htmlspecialchars($plena_nomo)) . "' " .
		   "WHERE uzanto = '".$uzanto ."'",
		   $db))
    {
      echo "<p>Ne povis ŝanĝi! <br/>\n" .
	mysql_error() . "</p>";
    }
}  // sxangxuInformojn




if(isset($_POST['pasvorto']) || isset($_POST['novPasvorto1']) || isset($_POST['novPasvorto2']))
{
  if(kontroluPasvorton($_SESSION['uzanto'], $_POST['pasvorto']))
  {
    if($_POST['novPasvorto1'] == $_POST['novPasvorto2'])
    {
      if(validaPasvorto($_POST['novPasvorto1']))
      {
        if(sxangxuPasvorton($_SESSION['uzanto'], $_POST['novPasvorto1']))
        {
          echo 'Ŝanĝis pasvorton!<br>';
        }
        else
        {
          echo 'Ne sukcesis ŝanĝi pasvorton!<br>';
        }
      }
      else
      {
        echo 'Donita nova pasvorto ne validas!<br>';
      }
    }
    else
    {
      echo 'Donitaj novaj pasvortoj ne samas!<br>';
    }
  }
  else
  {
    echo 'Malĝusta pasvorto!<br>';
  }
}
else if (isset($_POST['retadreso']) or
	 isset($_POST['plena_nomo'])) {

  sxangxuInformojn($_SESSION['uzanto'],
		   $_POST['retadreso'],
		   $_POST['plena_nomo']);
}

?>

<h2>Pasvorto</h2>

<form action="uzanto.php" method="post">
<p>Por ŝanĝi vian pasvorton plenigu la jenajn kampojn kaj klaku
  la butonon sube.</p>
<table>
<tr><th>Nuna pasvorto</th><td><input type="password" name="pasvorto"/></td></tr>
<tr><th>Nova pasvorto</th><td>
  <input type="password" name="novPasvorto1"></td></tr>
<tr><th>Nova pasvorto (konfirmo)</th><td>
  <input type="password" name="novPasvorto2">
</td></tr>
</table>
  <p>Via pasvorto povas esti ĉio ajn, kion vi volas, sed atentu ke ĝi
  estu tajpebla en ĉiu komputilo/klavaro, kie vi volas uzi tiun
  sistemon.
  </p>
  <p>Ne uzu tro facile diveneblan pasvorton.</p>
<p><input type="submit" value="Ŝanĝu pasvorton!"></p>
</form>

<h2>Miaj informoj</h2>

<?php

$uzantoj = dbElektu("uzantoj",
		    "uzanto = '".
		    $_SESSION['uzanto']."'");
$uzanto = $uzantoj[0];

?>

<form action='uzanto.php' method='post'>
<table>
   <tr><th>Salutnomo</th><td><?php echo $uzanto['uzanto'];
 ?></td></tr>
<tr><th>Plena nomo</th><td>
  <input type="text" name="plena_nomo" size='50' value="<?php
   echo $uzanto['plena_nomo'];
 ?>"/> <br/>
	      La plena nomo estas videbla en la listo de uzantoj, por
   ke aliaj uzantoj povu scii, kiu vi estas.
</td></tr>
<tr><th>Retpoŝta adreso</th><td>
  <input type="text" name="retadreso" size='50' value="<?php
   echo $uzanto['retadreso'];
?>"> <br/>
   La retpoŝta adreso estas uzata por sendi novan pasvorton al vi,
   kaj eble estonte ankaŭ por aliaj celoj. Nepre havu ĉi tie funkciantan
   adreson.
</td></tr>
<tr><th>Funkcio</th><td><strong><?php echo $uzanto['funkcio']; ?></strong><br/>
   Funkcio de vi ene de TEJO, ekzemple la landa sekcio, kiun vi reprezentas.
   (Se ĝi estas malĝusta, kontaktu la Ĝeneralan Sekretarion.)
</td></tr>
</table>
<p><input type="submit" value="Ŝanĝu informojn"></p>
</form>


<h2>Miaj uzanto-rajtoj</h2>

<?php

  ;

?><p>Vi havas la jenajn uzanto-rolojn:</p>

<table class='rajtotabelo'>
<?php  rajtotabelo("miaj"); ?>
<tr><td class='tabel-interrompo' colspan='2'>
<p>La jenajn rolojn vi <strong>ne</strong> havas:</p>
</td></tr>
<?php  rajtotabelo("nemiaj"); ?>
</table>
</body>
</html>