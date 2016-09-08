<?php

/**
 * Paĝo por krei novan uzanton.
 *
 * Nur uzebla de ĜenSek.
 *
 *
 * La paĝo montras formularon por indiki la gravajn
 * informojn kaj poste povas el la enmetitaj informoj
 * krei la novan uzanton.
 *
 *
 * La formularo sendas la jenajn informojn:
 *
 * $_POST['uzanto']
 *     la nova uzantonomo (uzota por ensaluti).
 *     (Ni kontrolas, ĉu ĝi ankoraŭ ne ekzistas.)
 * $_POST['retadreso'] 
 *    la retpoŝta adreso de la uzanto.
 * $_POST['plena_nomo']
 *    La plena (persona+familia) nomo de la uzanto (teksto).
 * $_POST['flagoj']
 *    La uzantorajtoj (decimala nombro, aŭ 'anst')
 * $_POST['funkcio']
 *    La funkcio de la uzanto, ekzemple la reprezentata
 *    landa sekcio (teksto)
 * $_POST['anstataux']
 *    La uzanto, kies rajtojn oni transprenas (se flagoj='anst').
 */


session_start();


require_once('ink.php');
require_once('iloj_uzanto.php');

$flagoj = kontroluUzanton();

metu_kapon("Kreado de uzanto", "GxenSek");
laMenuon();

if(!estas_GxenSek())
  die('Nur la ĜenSek povas krei novajn uzantojn!');



// -------------------- kreado de nova uzanto ------------------------

if($_SERVER['REQUEST_METHOD'] == 'POST')
  //isset($_POST['uzanto']) || isset($_POST['pasvorto1']) || isset($_POST['pasvorto2']))
{
  echo "<p>";
  echo "<!-- _POST:" . var_export($_POST, true) . "-->";
  if(validaUzanto($_POST['uzanto']))
  {
    if (validaRetadreso($_POST['retadreso']))
      {
	$pasvorto = kreu_hazardan_pasvorton(10);
	//	echo "<p>Pasvorto: " . $pasvorto. "</p>";

	if(kreuUzanton($_POST['uzanto'], $pasvorto, $_POST['flagoj'],
		       $_POST['plena_nomo'], $_POST['retadreso'],
		       $_POST['funkcio'],  $_POST['anstataux']))
	  {
	    echo "Kreis uzanton <a href='redaktuUzanton.php?uzanto=".
	      $_POST['uzanto']."'>".$_POST['uzanto']."</a>!<br/>\n";
	    sendu_retmesagxon_al_uzanto($_POST['uzanto'], $_POST['retadreso'],
					 $pasvorto);
	    if($_POST['flagoj'] != 'anst' && $_POST['flagoj'] != 0) {
	      $listo = dbElektu('proponoj', 'limtempo > UNIX_TIMESTAMP()');
	      if(count($listo) > 0) {
		echo "</p>\n<p>".
		  "La nova uzanto ne aŭtomate ricevis voĉdonrajtojn por la ".
		  "jam aktivaj proponoj, do <em>".listo($listo, 'titolo').
		  "</em>.</p>\n<p>".
		  "Se vi volas rajtigi lin tuj (kaj ne nur por estontaj ".
		  "voĉdonoj), <a href='redaktuUzanton.php?uzanto=".
		  $_POST['uzanto']."'>ŝanĝu lian rolon</a> al Observanto".
		  " kaj reen.";
	      }
	    }
	  }
        else
	  {
	    echo "Ne sukcesis krei uzanton!<br />\n";
	    echo mysql_error();
	    mysql_query("ROLLBACK");
	  }
      }
    else
      {
	echo "La retadreso <code>" . $_POST['retadreso'] .
	  "</code> aspektas malbona.<br/>";
      }
  }
  else
  {
    echo 'Donita uzanto-nomo ne validas!<br/>';
  }
  echo "</p>\n";
}


// ---------------------------- Montrado de la formularo ----------------

?>

<h2>Kreado de nova uzanto</h2>

<form action="kreuUzanton.php" method="post">
<p>Por krei novan uzanton plenigu la jenajn kampojn.</p>
<?php
  kreu_uzantdetaloformularon();
?>
<p><input type="submit" value="Kreu uzanton!"></p>
</form>

</body>
</html>