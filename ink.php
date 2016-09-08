<?php

/**
 * Ĝeneralaj funkcioj.
 *
 * Tiu dosiero estas enmetita de ĉiuj aliaj paĝoj.
 */


/**
 * La datumbaza konekto.
 */
include('db.php');

/**
 * forigas \ en la teksto.
 * \\ iĝas \, ĉiuj aliaj \ malaperas.
 */
function senSlasxigu($s) {
  return str_replace('<SLASXO>', "\\", str_replace("\\", '', str_replace("\\\\", '<SLASXO>', $s)));
}

/**
 * Plusendas al iu adreso ĉe sama servilo.
 * (Tiu funkcio kreas nur la koncernan kaplinion.)
 */
function plusenduAl($uri_part) {
  header("Location: " .( ( isset($_SERVER['HTTPS']) &&
			   $_SERVER['HTTPS'] == 'on' ) ?
			 'https:' : 'http:') .
	 "//" . $_SERVER['HTTP_HOST'] .
	 $uri_part, true, 303);
}


/**
 * Esploras, ĉu la aktuala uzanto (aŭ la uzanto kun la donitaj rajtoj)
 * estas ĜenSek.
 * @param int $rajtoj - la rajtoj esplorendaj. Se ne donita, ni uzas
 *   $GLOBALS['rajtoj'] anstataŭe (t.e. la rajtoj de la aktuala uzanto).
 * @return boolean true, se estas, alikaze false.
 */
function estas_GxenSek($rajtoj=null) {
  if(!isset($rajtoj))
    $rajtoj = $GLOBALS['flagoj'];
  return $rajtoj & 4;
}


/**
 * Donas liston de tiuj roloj, kiuj rajtas voĉdoni pri iu propono.
 * 
 *  2 = estraranoj
 *  4 = ĜenSek
 *  8 = Komitatanoj A+B
 * 16 = Komitatanoj Ĉ
 *
 * Se pluraj rajtas, la $flago estas sumo de tiuj valoroj.
 * Eble aldone estas metita bito 1 (por sekretaj voĉoj).
 *
 * @param int $flagoj la tipo-indikilo de propono.
 * @return array de tekstoj.
 */
function kiuRajtasVocxdoni($flagoj) {
  return roloTekstoj($flagoj & (~1));
}

/**
 * Donas tekstan priskribon de tiu grupo, kiu
 * rajtas voĉdoni pri iu propono.
 */
function kiuRajtas($flagoj) {
  switch($flagoj & (~1)) {
  case 0:
    return "neniu";
  case 2:
    return "Estraro";
  case 8:
    return "Komitatanoj A+B";
  case 8+16:
    return "Komitatanoj A+B+Ĉ";
  case 8+16+2:
    return "ĉiuj Komitatanoj";
  default:
    return implode(", ", kiuRajtasVocxdoni($flagoj));
  }

}

$GLOBALS['VDS_ROLONOMOJ'] =
  array(0 => "Observanto",
	1 => "Komitatano",
	2 => "Estrarano",
	4 => "ĜenSek",
	8 => "Komitatano A/B",
	16 => "Komitatano Ĉ",
	);


/**
 * Donas liston de ĉiuj roloj, kiuj estas indikitaj
 * per la menciitaj flagoj.
 * @param int $flagoj la uzantorajtoj.
 * @return array listo de ĉiuj roloj indikitaj en $flagoj.
 */
function roloTekstoj($flagoj) {
  $listo = array();
  
  foreach($GLOBALS['VDS_ROLONOMOJ'] AS $roloID => $rolonomo) {
    if ($roloID & $flagoj) {
      $listo[]= $rolonomo;
    }
  }
  if (count($listo)== 0) {
    $listo[]= $GLOBALS['VDS_ROLONOMOJ'][0];
  }
  return $listo;
}

/**
 * Kreas liston el du-dimensia array():
 * por ĉiu elemento de ĝi ni prenas la
 * kampon kun la menciita ŝlosilo.
 *
 * @return string disigita per komoj.
 */
function listo($aro, $kampo)
{
  $rez = '';
  for($i = 0; $i < count($aro); $i++)
  {
    if($i > 0)
      $rez = $rez.', ';
    $rez = $rez.$aro[$i][$kampo];
  }
  return $rez;
}

/**
 * Plusendas al la ensaluta paĝo, por poste reiri al la nuna paĝo.
 *
 * Tiu funkcio estu vokita antaŭ ol iu peco de la teksto estas sendita,
 * ĉar necesas manipuli la HTTP-kapojn.
 *
 * La funkcio ne revenas, sed poste finas la programon.
 *
 * @param string $mesagxo se donita, ni montros la mesaĝon
 *     en la ensalut-paĝo. Povus esti kialo por la plusendo.
 */
function plusenduAlEnsaluto($mesagxo=false) {

  $uri = dirname($_SERVER['REQUEST_URI']) . "/ensalutu.php" .
	 "?irual=" . urlencode($_SERVER['REQUEST_URI']) .
    ($mesagxo? "&mesagxo=" . urlencode($mesagxo) :"");

  plusenduAl($uri);

  header("Content-Type: text/html; charset=UTF-8");

  ?><html><head><title>Plusendo</title></head><body>
  <?php if ($mesagxo) echo "<p>" . $mesagxo . "</p>\n"; ?>
<p>Plusendas al <a href='<?php echo $uri; ?>'>Ensaluto</a></p>
</body>
  <?php
    exit();
}

/**
 * Kontrolas, ĉu iu estas ensalutita, ĉu tiu uzanto ekzistas kaj
 * esploras, kiujn rajtojn ĝi havas.
 *
 * Se neniu estas ensalutita aŭ tiu uzanto malaperis, ni kreas plusendan
 * paĝon al la ensaluta paĝo, por pluiro al la menciita paĝo poste.
 * @param string $irual kien la uzanto iru post ensaluto, por reprovi.
 */
function kontroluUzanton()
{
  if(!isset($_SESSION['uzanto']))
  {

    plusenduAlEnsaluto("Vi devas ensaluti antaŭ ol atingi tiun paĝon." .
		       " (Se tiu problemo daŭre aperas, vi eble devos ebligi ".
		       "  kuketojn (Cookies) por nia domajno. )");
  }

  $rez = dbElektu('uzantoj',
		  "uzanto = '".$_SESSION['uzanto']."'");
  switch(count($rez))
  {
  case 0:
    plusenduAlEnsaluto("La uzanto " . $_SESSION['uzanto'] .
		       " ne plu estas en la datumbazo. Se la problemo " .
		       " persistas, kontaktu la GenSek.");
    return false;
  case 1:
    return $rez[0]['flagoj'];
  default:
    plusenduAlEnsaluto("La uzanto " . $_SESSION['uzanto'] .
		       " ekzistas pluroble en la datumbazo. Io tre fuŝas ...".
		       " Kontaktu la ĜenSek, se tio persistas.");
    return false;
  }
}

/**
 * kreas haketan version de la pasvorto de la uzanto, por
 * stokado en la datumbazo.
 *
 * @param string $uzanto la uzantonomo
 * @param string $pasvorto la pasvorto (en la pura formo entajpita
 *                     de la uzanto)
 * @return string ĉeno de longeco de 40 signoj, sesdekume kodita.
 */
function haketuPV($uzanto, $pasvorto) {
  return sha1( $uzanto . ":" . $pasvorto);
}

/**
 * Kontrolas, ĉu la uzanto havas la menciitan pasvorton.
 *
 * DB-konekto jam estu kreita.
 * @param string $uzanto la uzantonomo
 * @param string $pasvorto la pasvorto (en la pura formo entajpita
 *                     de la uzanto)
 * @return boolean true, se estas ĝusta, alikaze false.
 */
function kontroluPasvorton($uzanto, $pasvorto)
{
  $tabelo = dbPrefikso().'uzantoj';
  $sql = "SELECT pasvorto FROM $tabelo WHERE uzanto = '$uzanto'";
  $rezulto = mysql_query($sql);
  if($rezulto)
  {
    $linio = mysql_fetch_assoc($rezulto);
    return ($linio and
	    ($linio['pasvorto'] == haketuPV($uzanto, $pasvorto)));
  }
  else
  {
    //echo 'Fiaskis.<br>';
    return false;
  }
}

/**
 * eltrovas, ĉu $pasvorto estas akceptebla pasvorto.
 */
function validaPasvorto($pasvorto)
{
  return strlen($pasvorto) >= 3;
}

/**
 * Eltrovas, ĉu $retadreso povus esti valida retadreso.
 * Por tio ni postulas, ke almenaŭ unu signo estu antaŭ kaj
 * unu post la @-signo.
 */
function validaRetadreso($retadreso)
{
  $at_idx = strpos($retadreso, "@");
  return
    0 < $at_idx and
    $at_idx+1 < strlen($retadreso);
}

/**
 * Eltrovas, ĉu $uzanto estas valida uzantonomo.
 * (Nuntempe tio nur signifas, ke ĝi estu minimume 1 signon longa.)
 */
function validaUzanto($uzanto)
{
  return strlen($uzanto) >= 1;
}

/**
 * Kreas kaj eldonas ligilon al iu adreso.
 */
function ligilon($teksto, $adreso, $klaso=false)
{
  if($klaso) {
    echo '<a href="'.$adreso.'" class="'.$klaso.'">'.$teksto.'</a>';
  }
  else {
    echo '<a href="'.$adreso.'">'.$teksto.'</a>';
  }
}

/**
 * Kreas ligilon (ne butonon) por elsaluti.
 */
function elsalutButonon()
{
  echo "<strong>";
  ligilon("Elsalutu", "ensalutu.php?elsalutu=elsalutu");
  echo "</strong>";

  /* ?><form style='display:inline;' action="ensalutu.php" method="post">
    <input type="hidden" name="elsalutu" value="elsalutu">
    <input type="submit" value="elsalutu"></form>
    <?php */
}

/**
 * Kreas ligilon al "mia konto".
 */
function miajdifinojLigilon()
{
  ligilon('Mia konto', 'uzanto.php');
}

/**
 * Kreas ligilon al la uzantolisto.
 */
function uzantojLigilon()
{
  ligilon('uzantoj', 'uzantoj.php');
}

/**
 * Kreas ligilon al la listo de proponoj.
 */
function proponojLigilon()
{
  ligilon('proponoj', 'proponoj.php');
}

/**
 * Kreas ligon al la kreilo por nova propono.
 */
function novproponoLigilon()
{
  ligilon('Kreu novan proponon.', 'novpropono.php', 'GxenSek');
}

/**
 * Metas HTML-kapon. (Tio ne inkluzivas la menuon, sed ja
 * la komencon de la <div>, en kiu sidas la menuo.)
 */
function metu_kapon($titolo, $klaso=false) {
  header("Content-Type: text/html; charset=utf-8");
  

  ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" ></meta>
    <title>
    <?php echo "Reta Voĉdonsistemo de TEJO: " . $titolo; ?>
    </title>
    <link rel="stylesheet" type="text/css" href="./stilo.css" />
  </head>
   <body<?php if($klaso) { echo " class='$klaso'"; } ?>>
     <div class='kapo'>
  <p class='vdsvarbado'>
    Reta voĉdonsistemo de TEJO. Kreita de Tom Juval, kun etaj ŝanĝoj
     de Paŭlo Ebermann.<br/>
     Ĉiuj tempoj estas UTC/GMT (= brita vintra tempo). Nun estas
   <?php echo formatutempon(time()); ?>.</p>
<?php
}

/**
 * Metas la menuon.
 */
function laMenuon()
{
  echo '<p class="menuo">Saluton, <em>'.$_SESSION['uzanto']."</em>!\n";
  ?>&nbsp; — &nbsp;<?php
  miajdifinojLigilon();
  ?>&nbsp; — &nbsp;<?php
  proponojLigilon();
  ?>&nbsp; — &nbsp;<?php
  uzantojLigilon();
  ?>&nbsp; — &nbsp;<?php
  elsalutButonon();
  echo "</p>\n</div><!-- kapo -->\n\n";
}

/**
 * formatas la tempon laŭ UTC.
 * @param int $tempo tempo kiel uniks-tempoŝtampo.
 */
function formatuTempon($tempo)
{
  return gmdate("Y-m-d H:i", $tempo);
}

/**
 *  Kreas tabellinion pri rajto (por la helpo).
 * 
 * (Uzata el rajtotabelo().)
 *
 * @param int $flago la bito por la rolo.
 * @param string $nomo la nomo de la rolo.
 * @param string $priskribo 
 * @param string $miaj kiujn rolojn montri?
 *        -  "miaj" se ni montru la rolojn de la nuna uzanto,
 *        -  "nemiaj" se ni montru la rolojn, kiuj mankas al la uzanto.
 *        -  "cxiuj" se ni montru ĉiujn rolojn.
 */
function rajtolinio($flago, $priskribo, $miaj)
  {  
    $nomo = $GLOBALS['VDS_ROLONOMOJ'][$flago];

    if (($miaj == "cxiuj") or
	(($GLOBALS['flagoj'] & $flago) and ($miaj == "miaj")) or
	((!($GLOBALS['flagoj'] & $flago)) and ($miaj == "nemiaj")
	 and ($flago != 0)) or
	(($flago == 0) and ($GLOBALS['flagoj'] == 0) and ($miaj == "miaj"))
	)
    {
      echo "<tr><th>";
      echo $nomo;
      echo "</th><td>";
      echo $priskribo;
      echo "</tr>\n";
    }
  else {
    echo "<!-- ellasas: '$nomo'\n -->";
  }
}

  /**
   * Kreas tabelon kun klarigo pri la rajtoj. La "<table>"-tagojn ni ne
   * mem metas.
   * @param string $miaj kiujn rolojn montri?
   *        -  "miaj" se ni montru la rolojn de la nuna uzanto,
   *        -  "nemiaj" se ni montru la rolojn, kiuj mankas al la uzanto.
   *        -  "cxiuj" se ni montru ĉiujn rolojn.
   */
function rajtotabelo($miaj) {

  rajtolinio(1, "Komitatanoj estas ĉiuj, kiuj rajtas voĉdoni ".
	     "entute.<br/>Ili estas aŭ Komitatanoj A, B, Ĉ aŭ Estraranoj.",
	     $miaj);
  rajtolinio(2, "La estraro gvidas la asocion, elektita de ".
	     "la Komitatanoj A, B kaj Ĉ. <br/>La estraranoj mem" .
	     "estas komitatanoj, sed ne rajtas elekti Estraron aŭ " .
	     "Komitatanojn Ĉ.", $miaj);
  rajtolinio(8, "La kerno de la Komitato. Komitatanoj A estas reprezentantoj de ".
	     "landaj kaj fakaj sekcioj de TEJO, Komitatanoj B reprezentantoj ".
	     "de la individuaj membroj.<br/>Ili ĉiam rajtas voĉdoni.", $miaj);
  rajtolinio(16, "Komitatanoj Ĉ estas elektataj de la Komitatanoj A kaj B, ".
	     "ekzemple por havi iun specifan kompetencon en la Komitato. ".
	     "<br/>Ili rajtas voĉdoni pri ĉio krom elektado de (pliaj) " .
	     "Komitatanoj Ĉ.", $miaj);
  rajtolinio(4, "La Ĝenerala Sekretario traktas ĉiujn formalaĵojn ".
	     "(do la <em>ĝenajn sekaĵojn</em>). Li (aŭ ŝi) kreas/forigas".
	     " uzantokontojn kaj gvidas balotojn. <br/>Kutime por mem ".
	     "voĉdoni oni uzas alian uzantokonton.", $miaj);
  rajtolinio(0, "Observantoj estas ĉiuj, kiuj havas aliron al la " .
	     "voĉdonsistemo sed ne rajtas voĉdoni nek administri tie. " .
	     "<br/>Ili tamen povas kiel ĉiu tie rigardi, kiel estis la ".
	     "rezultoj de voĉdonoj.", $miaj);
}
