<?php

/**
 * Ensaluta formularo.
 *
 * Ĉiuj paĝoj plusendas ĉi tien, se la uzanto ne estas ensalutita.
 *
 * La formularo sendas tiujn parametrojn:
 *
 *   $_POST['uzanto'] - uzantonomo entajpita.
 *   $_POST['pasvorto'] - pasvorto entajpita.
 *
 * (kaj kopiojn el tiuj variabloj, kiujn aliaj sendis antaŭe.)
 *
 * Aliaj paĝoj sendas tion:
 *
 * $_POST['mesagxo']/$_GET['mesagxo']:
 *     mesaĝo montrenda apud la ensalutilo.
 * $_POST['irual']/$_GET['irual']:
 *     paĝo, al kiu ni plusendu post sukcesa ensaluto.
 *     (Se ne donita, ni sendas al proponoj.php.)
 * $_GET['elsalutu']
 *     se 'elsalutu', tiam ni forigas la sesiajn informojn.
 */



session_start();

if(isset($_REQUEST['elsalutu']) &&
   $_REQUEST['elsalutu'] == 'elsalutu')
  unset($_SESSION['uzanto']);


/**
 * Kontrolas, ĉu la kombino de uzanto kaj pasvorto estas valida.
 * La funkcio antaŭe konektas al la datumbazo.
 *
 * @param string $uzanto la uzantonomo
 * @param string $pasvorto la pasvorto (en la pura formo entajpita
 *                     de la uzanto)
 * @return boolean true, se estas ĝusta, alikaze false.
 */
function valida($uzanto, $pasvorto)
{
  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi konekton al la datumbazo!<br>';
    return false;
  }
  return kontroluPasvorton($uzanto, $pasvorto);

}

/**
 * kreas ensalutan formularon.
 * @param string $mesagxo teksto montrenda super la formularo.
 */
function kreu($mesagxo)
{
  metu_kapon("Ensaluto");

  echo "</div>\n\n";

  echo "<p>" . $mesagxo . "</p>\n";
  echo "<form action='ensalutu.php' method='POST'>\n<p>";

  if (isset($_POST['mesagxo'])) {
    $mesagxTeksto = urldecode($_POST['mesagxo']);
  }
  else if(isset($_GET['mesagxo'])) {
    // $_GET jam faris la urldecode().
    $mesagxTeksto = $_GET['mesagxo'];
  }
  else {
    $mesagxTeksto = false;
  }

  if ($mesagxTeksto) {
    echo "<input type='hidden' name='mesagxo' value='" .
      urlEncode($mesagxTeksto) . "' />\n";
    echo "" . htmlspecialchars($mesagxTeksto) . "</p>\n<p>";
  }


  if (isset($_GET['irual'])) {
    // resendi finan celon.
    echo "<input type='hidden' name='irual' value='" .
      urlencode($_GET['irual']) . "' />\n";
  }
  else if (isset($_POST['irual'])) {
    // resendi finan celon.
    echo "<input type='hidden' name='irual' value='" .
      $_POST['irual'] . "' />\n";
  }
  // la formularo por ensaluti.
  echo "Uzanto: <input type='text' name='uzanto' /><br />\n";
  echo "Pasvorto: <input type='password' name='pasvorto' /><br/>\n";
  echo "<input type='submit' value='Ek!' />";
  echo "</p>\n</form>\n";

  echo "</body>";
  echo "</html>";
}


require_once('ink.php');


if(isset($_POST['uzanto']) || isset($_POST['pasvorto']))
{
  if(valida($_POST['uzanto'], $_POST['pasvorto']))
  {
    $_SESSION['uzanto'] = $_POST['uzanto'];

    if(isset($_POST['irual']))
      $iruAl = urldecode($_POST['irual']);
    else
      $iruAl = dirname($_SERVER['REQUEST_URI']) . '/proponoj.php';
    plusenduAl($iruAl);

    echo "<html><head><title>Plusendo</title></head>
<body><p>Bonvolu sekvu la <a href='$iruAl'>Plusendon</a>.</p></body></html>";
    exit();
  }
  else
  {
    kreu('Nevalida uzanto kaj/aŭ pasvorto!</p>'.
	 '<p>Se vi forgesis vian pasvorton, kontaktu la '.
	 ' Ĝeneralan Sekretarion, kiu povas krei novan por vi.');
  }
}
else
  kreu('Saluton!');

