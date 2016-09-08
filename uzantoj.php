<?php

session_start();

include('ink.php');

$flagoj = kontroluUzanton();

metu_kapon("Uzantoj");

laMenuon($flagoj);


$kapolisto = array('n' => array('uzanto', "Uzanto"),
		   'p' => array('plena_nomo', "Plena nomo"),
		   't' => array('flagoj', "Roloj"),
		   'f' => array('funkcio', "Funkcio"),
		   'r' => array('ekde', "Registrita"),
		   //		   ''  => array('uzanto'),
		   );


/**
 * kreas la SQL-frazeron pri ordigado el la kodo-litero(j).
 */
function ordo($o)
{
  $sufikso = (strlen($o) > 1 && $o[1] == 'm') ? ' DESC' : '';
  return $GLOBALS['kapolisto'][$o[0]][0] . $sufikso;
}

if(isset($_GET['o'])) {
   $o = ordo($_GET['o']);
}
else {
  $o = ordo('n');
}
$uzantoj = dbElektu('uzantoj', 'true ORDER BY '.$o);



/**
 * Eltrovas, cxu la aktuala ordigo estas la menciita,
 * kaj en tiu kazo organizas ligon por mala ordigado.
 *
 * @param string $v la nomo de la ordigo-kampo por kompari.
 * @return string aux 'm' aux ''.
 */
function cxuM($v)
{
  if($GLOBALS['o'] == $v)
    return 'm';
  return '';
}

/**
 * kreas tabelkapon por unu kolumno.
 */
function tabelkapero($ordigoKodo, $kapovaloro) {
  if (count($kapovaloro) > 1)
    {
      echo "<th>";
      ligilon($kapovaloro[1], "uzantoj.php?o=" . $ordigoKodo .
	      cxuM($kapovaloro[0]));
      echo "</th>\n";
    }
}

echo '<table class="uzantolisto">
<tr>';
foreach($kapolisto AS $s => $v) {
  tabelkapero($s, $v);
}
echo "</tr>\n\n";


// $kiomoj = array('Observanto' => 0,
// 		'Komitatano' => 0,
// 		'Komitatano A/B' => 0,
// 		'Komitatano C' => 0,
// 		'Estrarano' => 0,
// 		'ĜenSek' => 0);

$kiomoj = array_fill_keys($GLOBALS['VDS_ROLONOMOJ'], 0);


$i = 0;
for(; $i < count($uzantoj); $i++)
{
  $uzanto = $uzantoj[$i];
  $roloj = roloTekstoj($uzanto['flagoj']);
  foreach($roloj AS $rolo) {
    $kiomoj[$rolo] ++;
  }

  echo '<tr><td>';
  if (estas_GxenSek()) {
    ligilon($uzanto['uzanto'],
	    'redaktuUzanton.php?uzanto='.$uzanto['uzanto'],
	    'GxenSek');
  }
  else {
    echo $uzanto['uzanto'];
  }
  echo '</td><td>'.
    $uzanto['plena_nomo'] .
    '</td><td>'.implode(", ", $roloj).
    '</td><td>'. $uzanto['funkcio'] .
    '</td><td>'.formatuTempon($uzanto['ekde']).'</td>';
  echo "</tr>\n";
}

echo '</table>';

echo
"<p>Entute: $i uzantoj (Komitatanoj: ".$kiomoj['Komitatano'].
' (A/B: ' . $kiomoj["Komitatano A/B"] .', C: '. $kiomoj["Komitatano C"] .
'), Estraranoj: '.$kiomoj['Estrarano'].
  ', ĜenSek: '.$kiomoj['ĜenSek'].', Observantoj: '.$kiomoj['Observanto'].').</p>';

if(estas_GxenSek()) {
  echo "<p>";
  ligilon('kreu novan uzanton', 'kreuUzanton.php', 'GxenSek');
  echo "</p>";
}

?>
<h3>Klarigoj</h3>
<table>
<?php  rajtotabelo("cxiuj");  ?>
</table>
</body>
</html>
