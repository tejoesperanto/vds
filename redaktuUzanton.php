<?php

/**
 * Uzanto-redaktilo por GxenSek.
 *
 * parametroj:
 * $_REQUEST['uzanto']: uzantonomo de la redaktenda uzanto.
 *
 *   Se nur tio (kaj per GET), ni montras formularojn por la unuopaj
 *   agoj. En la aliaj kazoj necesas uzi POST.
 *
 * $_POST['ago']: ago farenda.
 *   - 'sxangxu':  sxangxas nomon, retadreson, funkcion kaj/aux uzantorolojn.
 *   - 'nomo':     sxangxas nomon kaj kreas/sendas novan pasvorton.
 *   - 'pasvorto': kreas kaj sendas novan pasvorton.
 *
 * $_POST['flagoj']:     rajtoj/ecoj de la uzanto.
 * $_POST['plena_nomo']: plena nomo de la uzanto.
 * $_POST['retadreso']:  retposxta adreso (tiun ni uzas por sendi
 *                       pasvorton ktp.)
 * $_POST['funkcio']:    funkcio de la uzanto (simpla teksto)
 *
 *  Tiuj kvar (nur) necesas por ago=sxangxu.
 *
 *
 * $_POST['nova_uzanto']:  nova uzantonomo. (nur por ago=nomo)
 */

session_start();


/**
 */
require_once('ink.php');
require_once("iloj_uzanto.php");

$flagoj = kontroluUzanton();

metu_kapon("Redaktado de uzanto", 'GxenSek');
laMenuon($flagoj);


if(!estas_GxenSek())
  die('Nur la ĜenSek povas redakti uzantojn!');



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  echo "<!--";
  var_export($_POST);
  echo "-->";

  switch($_REQUEST['ago']) {
  case 'sxangxu':
    if(! sxangxuUzanton($_POST['uzanto'],
			$_POST['flagoj'],
			$_POST['plena_nomo'],
			$_POST['retadreso'],
			$_POST['funkcio'])) {
      echo "<p>Okazis eraro: <code>" . mysql_error() . "</code></p>";
      mysql_query("ROLLBACK");
    }
    break;
  case 'nomo':
    if (sxangxuUzantonomon($_POST['uzanto'],
			   $_POST['nova_uzanto'])) {
      echo "<p>Ŝanĝis la nomon de uzanto <em>{$_POST['uzanto']}</em> al ".
	"<em>{$_POST['nova_uzanto']}</em>.</p>";
      $_REQUEST['uzanto'] = $_POST['nova_uzanto'];
    }
    else {
      echo "<p>Okazis eraro: <code>" . mysql_error() . "</code></p>";
    }
    break;
  case 'pasvorto':
    if (kreuNovanPasvorton($_POST['uzanto'])) {
      echo "<p>Kreis kaj sendis novan pasvorton al <em>{$_POST['uzanto']}</em>.</p>";
    }
    else {
      echo "<p>Okazis eraro: <code>" . mysql_error() . "</code></p>";
    }
    break;
  }

}


$uzanto = $_REQUEST['uzanto'];
$jam = dbElektu('uzantoj', "uzanto = '$uzanto'");
if (count($jam) != 1) {
  die("<p>Eraro: Ne estas precize 1 uzanto kun nomo '$uzanto'</p>");
}
$informoj = $jam[0];


?>


<h2>Redakto de detaloj</h2>
<form action="redaktuUzanton.php?ago=sxangxu" method="post">
<?php
  kreu_uzantdetaloformularon($informoj);
?>
<p>Atentu: Se vi ŝanĝas la rolon, li/ŝi rajtas (depende de la rolo)
   tuj voĉdoni en la nun aktivaj voĉdonoj, kie li/ŝi antaŭe ne rajtis.
</p>
<p><button type="submit">Ŝanĝu detalojn!</button></p>
</form>

<h2>Ŝanĝo de uzantonomo</h2>
<form action="redaktuUzanton.php?ago=nomo" method="post">
  <table class='uzantodetaloj'>
  <tr><th>nuna nomo</th>
      <td><input type="hidden" name='uzanto' value="<?php echo $informoj['uzanto']; ?>" /> <?php echo $informoj['uzanto']; ?> </td></tr>
  <tr><th>nova nomo</th>
      <td><input type="text" name='nova_uzanto' value="<?php echo $informoj['uzanto']; ?>" /> Salutnomo por la sistemo. Ĝi estu tajpebla.
</td></tr>
  </table>
<p>  Se vi ŝanĝas la uzantonomon, ni ankaŭ kreos kaj sendos novan
  pasvorton por la uzanto.</p>
<p><button type="submit">Ŝanĝu uzantonomon!</button></p>
</form>

<h2>Kreo de nova pasvorto</h2>
<form action="redaktuUzanton.php?ago=pasvorto" method="post">
  <p>Vi povas krei novan pasvorton por la uzanto kaj
  sendigi ĝin al li retpoŝte.</p>
  <table class='uzantodetaloj'>
  <tr><th>Uzanto-nomo</th>
      <td><input type="hidden" name='uzanto' value="<?php echo $informoj['uzanto']; ?>" /> <?php echo $informoj['uzanto']; ?> </td></tr>
  </table>
<p><button type="submit">Kreu novan pasvorton!</button></p>
</form>

<h2>Forigo</h2>
<p>
<?php
    ligilon('Forstreku la uzanton', 'forstrekuUzanton.php?uzanto='.$informoj['uzanto']);
echo " " . $informoj['uzanto']
?>
.</p>


</body>
</html>