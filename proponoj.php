<?php

/**
 * Paĝo kun listo de ĉiuj proponoj.
 *
 * Tiu paĝo estas kvazaŭ la enirpaĝo, kiun oni normale
 * unue vidas post ensaluto.
 *
 * Ĝi estas uzebla por ĉiuj uzantoj, sed ĜenSek havas aldonan
 * kolumnon kun eblo forigi la proponon, kaj komitanoj povas vidi,
 * ĉu ili mem jam voĉis.
 */


session_start();

include('ink.php');

$flagoj = kontroluUzanton();

metu_kapon("Proponoj");

laMenuon();


/**
 * Eltrovas, ĉu la aktuala uzanto jam voĉdonis respektive ankoraŭ
 * rajtas voĉdoni pri iu propono.
 * @param int $propono la identigilo de la propono.
 * @param int $prop_flagoj la konfiguro de la propono.
 * @return string  'jes' - jam voĉdonis.
 *                 'NE!' - ankoraŭ ne voĉdonis, kaj rajtas (aŭ rajtis).
 *                 'ne rajtas' - ne rajtas voĉdoni.
 */
function vocxdonis($propono, $prop_flagoj)
{
  $uzanto = $_SESSION['uzanto'];
  if ($prop_flagoj & (~1) & $GLOBALS['flagoj']) {
    // rajtas/rajtis voĉdoni
    $v = dbElektu('vocxdonoj', "uzanto = '$uzanto' AND propono = '$propono'");
    if(count($v) > 0) {
      // voĉdonis
      return 'jes';
    }
    // ne voĉdonis
    return 'NE!';
  }
  else {
    // ne rajtas/rajtis voĉdoni
    return "ne rajtas";
  }
}

/**
 * formatas la rezulton de iu voĉdono kiel kelkaj tabelĉeloj.
 * | jes | ne | sind | entute |
 *
 * @param array $propono unu linio el la propono-tabelo.
 * @return string formatita ĉeno el nombro de voĉoj
 *      por ĉiu varianto.
 */
function rezulto($propono)
{
  // TODO: forigitaj?
  $listo_entute = dbElektu('vocxdonantoj', 'propono = ' .$propono['id']);
  $listo_ne_vocxis = dbElektu('vocxdonantoj', 'propono = '.$propono['id'] .
			      ' AND jam_vocxis = 0 ');

  return '<td title="jes">'.$propono['jesis'].
    '</td><td title="ne">'.$propono['neis'].
    '</td><td title="sindetene">'.$propono['sindetenis'].
    '</td><td title="ne voĉis">'. count($listo_ne_vocxis).
    '</td><td title="entute rajtis">'.count($listo_entute).
    '</td>';
}

// -------------------------------- Jen la ĉefa agado --------------------


$proponoj = dbElektu("proponoj", "true ORDER BY limtempo DESC");

$nun = (int)date('U');


if(estas_GxenSek()) {
  echo "<p>";
  novproponoLigilon();
  echo "</p>\n";
}


// ------------------- Tabelo de malfermitaj voĉdonoj

echo "<h2>Aktivaj voĉdonadoj</h2>\n";

// tabel-kapo
echo '<table class="vocxdonoj malfermitaj">' . "\n";
echo '<tr><th>Titolo</th><th colspan=2>Tipo</th><th>Registrita</th><th>Limtempo</th>';
if($flagoj & 1) {
  // komitatanoj ricevas tiun kolumnon
  echo '<th>Ĉu vi jam voĉdonis</th>';
}
echo "</tr>\n";

$i = 0;

// nur tiuj, kies limdato ankoraŭ ne pasis.
for(; $i < count($proponoj) and $nun < (int)($proponoj[$i]['limtempo']); $i++)
{
  echo '<tr><td>';
  // ligilo al la propono
  ligilon($proponoj[$i]['titolo'], 'propono.php?id='.$proponoj[$i]['id']);
  // publika/sekreta
  echo '</td><td>'. ($proponoj[$i]['flagoj'] & 1 ? "sekreta" : "publika");
  // rajtantaj uzanto-grupoj
  echo "</td><td>" . kiuRajtas($proponoj[$i]['flagoj']);
  // ekde/ĝis
  echo '</td><td>'.formatuTempon($proponoj[$i]['ekde']).
    '</td><td>'.formatuTempon($proponoj[$i]['limtempo']).'</td>';
  if($flagoj & 1) {
    // ĉu mi jam voĉdonis?
    echo '<td>'.vocxdonis($proponoj[$i]['id'], $proponoj[$i]['flagoj']);
  }
  // fino de tabellinio
  echo "</tr>\n";
}
echo "</table>\n\n";


// ------------------- Tabelo de fermitaj voĉdonoj

echo "<h2>Fermitaj voĉdonadoj</h2>\n";

echo '<table class="vocxdonoj fermitaj">' . "\n";
echo '<tr><th rowspan="2">Titolo</th><th  rowspan="2" colspan="2">Tipo</th><th rowspan="2">Registrita</th><th rowspan="2">Finita</th><th colspan="5">Rezulto</th>';
echo "</tr>\n";
echo "<tr class='subtitolo'><th title='jesis'>jes</th>".
"<th title='neis'>ne</th><th title='sindetenis'>s.d.</th>".
"<th title='ne voĉdonis'>n.v.</th><th title='entute rajtis'>raj.</th></tr>\n";

for(; $i < count($proponoj); $i++)
{
  echo '<tr><td>';
  // ligilo al la propono
  ligilon($proponoj[$i]['titolo'], 'propono.php?id='.$proponoj[$i]['id']);
  // publika/sekreta
  echo '</td><td>'. ($proponoj[$i]['flagoj'] & 1 ? "sekreta" : "publika");
  // rajtintaj uzanto-grupoj
  echo "</td><td>" . kiuRajtas($proponoj[$i]['flagoj']);
  // ekde/ĝis
  echo '</td><td>'.formatuTempon($proponoj[$i]['ekde']).
    '</td><td>'.formatuTempon($proponoj[$i]['limtempo']).'</td>'.
    // rezulto.
    rezulto($proponoj[$i]);
  echo '</tr>';
}

echo '</table>';

?>
</body>
</html>