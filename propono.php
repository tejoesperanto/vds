<?php

/**
 * Paĝo por vidi proponon kaj voĉdoni pri ĝi.
 *
 * - $_GET['id'] - la identigilo de la propono.
 *
 * - $_GET['moduso']:
 *      'vocxu'  - montras la voĉdonformularon.
 *      'vidu'   - montras la propran voĉon (nur ĉe publikaj
 *                 voĉdonoj, kiuj ankoraŭ ne finiĝis).
 *      ne donita - montras informojn pri la propono
 * 
 * - $_POST['vocxo'] - la valoro de la voĉo:
 *           1 = jes, 2 = ne, 3 = sindeteno.
 */

session_start();

include('ink.php');

$flagoj = kontroluUzanton();

$id = (int)$_GET['id'];

$proponoj = dbElektu('proponoj', 'id = '.$id);
if(count($proponoj) != 1)
  die('Propono #'.$_GET['id'].' ne ekzistas, aŭ estas eraro en la datumbazo!<br>');
$propono = $proponoj[0];

metu_kapon("Propono: " . $propono['titolo']);

laMenuon($flagoj);

function kreuVocxdonFormularon($teksto)
{
  ?>
  <script type="text/javascript">
  function konfirmuVocxdonon()
  {
    return confirm('Rimarku ke voĉdono ne estas poste ŝanĝebla!' +
      ' Konfirmu nur se vi certas pri via voĉo!' <?php
		   if (!($GLOBALS['propono']['flagoj'] & 1)) { ?> +
      ' Rimarku ankaŭ ke la voĉdonado estas publika kaj ĉiuj uzantoj de' +
		   ' la sistemo povos vidi vian elekton post la limtempo.'<?php } ?>
		   );
  }
  </script>
  <form id="vocxdono" action="propono.php?id=<?php echo $_GET['id']; ?>" method="post">
      <p class='propono'>
      <?php echo nl2br($teksto); ?>
      </p>
<p>
  Mi voĉdonas
  <input type="radio" name="vocxo" value="1" />jese
  <input type="radio" name="vocxo" value="2" />nee
  <input type="radio" name="vocxo" value="3" checked="checked" />sindetene.
</p>
<p>
  <input type="submit" value="Voĉdonu!"
         onclick="return konfirmuVocxdonon();" />
      <a href="propono.php?id=<?php echo $_GET['id']; ?>">Reen!</a>
  </p>
  </form>
  <?php
}

$fermita = $propono['limtempo'] < date('U');
$sekreta = $propono['flagoj'] & 1; // lasta bito indikas la sekretecon




/**
 * ŝlosas la propono-tabelon por eviti ŝanĝon de diversaj voĉdonantoj
 * samtempe.
 * @param int      $propono  ID
 * @param string   $uzanto uzantonomo
 * @param resource $db  datumbazkonekto.
 * @return string|false erarmesaĝo, se ne eblis ŝlosi, alikaze false.
 */
function sxlosu_tabelon($propono, $uzanto, $db)  {
  //  echo "<!-- sxlosu_tabelon($propono, $uzanto) -->";
  $jam =
    mysql_query('SELECT * FROM '.dbPrefikso()."sxlosoj ".
		" WHERE propono = $propono", $db);
  if(!mysql_fetch_array($jam)) {
    if(! mysql_query('INSERT INTO '.dbPrefikso()."sxlosoj ".
		"   SET propono = $propono, uzanto = NULL",
		     $db)) {
      return "Problemo kreanta ŝloson: ".mysql_error()." <br/>";
    }
  }
  if(!mysql_query('UPDATE '.dbPrefikso()."sxlosoj ".
		  "   SET uzanto = '$uzanto' ".
		  " WHERE propono = $propono ".
		  "   AND uzanto IS NULL",
		  $db))
    return 'Ne sukcesis skribi ŝloson: '.mysql_error().'<br>';
  $cxu = mysql_query('SELECT * FROM '.dbPrefikso()."sxlosoj ".
		     " WHERE uzanto = '$uzanto' ".
		     "   AND propono = $propono",
		     $db);
  if(!$cxu)
    return 'Ne sukcesis kontroli ŝloson: '.mysql_error().'<br/>';
  $kontrolo = mysql_fetch_array($cxu);
  if(!$kontrolo)
    return 'Ne sukcesis akiri ŝloson!<br/>';

  return false; // ĉio en ordo.
}



/**
 * registras la voĉdonon de uzanto por propono.
 *
 * @param int     $propono  propono-ID
 * @param string  $uzanto   uzanto-nomo
 * @param int     $vocxo    1 = jes, 2 = ne, 3 = sindeteno
 * @param boolean $sekreta  ĉu estas sekreta voĉdono?
 *
 * @return string erarmesaĝo aŭ sukces-mesaĝo.
 */
function registruVocxdonon($propono, $uzanto, $vocxo, $sekreta)
{
  $db = dbMalfermu();
  if(!$db)
    return 'Ne sukcesis malfermi datumbazon!<br>';

  $eraro = sxlosu_tabelon($propono, $uzanto, $db);
  if ($eraro)
    return $eraro;

  $kampoj = array(1 => 'jesis',
		  2 => 'neis',
		  3 => 'sindetenis');

  $kampo = $kampoj[$vocxo];
  mysql_query('UPDATE '.dbPrefikso()."proponoj ". 
	      "   SET `{$kampo}` = (`{$kampo}` + 1) ".
	      " WHERE `id` = $propono");

  if($sekreta) {
    // en sekretaj voĉdonoj ni ne memoras la voĉon
    $vocxo = 0;
  }
  mysql_query('INSERT INTO '.dbPrefikso()."vocxdonoj ".
	      "SET propono = $propono,".
	      "    uzanto  = '$uzanto', ".
	      "    vocxo   = $vocxo, " .
	      "    kiam    = ".date('U'),
	      $db);
  mysql_query("UPDATE " . dbPrefikso()."vocxdonantoj " .
	      "   SET jam_vocxis = 1 " .
	      " WHERE propono = {$propono} " .
	      "   AND uzanto = '{$uzanto}' ", $db);

  // malŝlosi la tabelon
  mysql_query('UPDATE '.dbPrefikso()."sxlosoj ".
	      "   SET uzanto = NULL ".
	      " WHERE propono = $propono",
	      $db);
  return 'Registris vian voĉon!<br>';
} // registruVocxdonon()


function vortumuVocxon($vocxo)
{
  if($vocxo == 1)
    return ' jese';
  if($vocxo == 2)
    return ' nee';
  if($vocxo == 3)
    return ' sindetene';
  return '';
}


/**
 * Listas ĉiujn voĉdonrajtantojn.
 */
function listuRajtantojn($aro) {
  $rez = array();
  foreach($aro AS $ero)
  {
    //    echo "<!-- " . var_export($ero, true) . "\n-->";
    if ($ero['uzanto_forigita'] < $ero['uzanto_aldonita']) {
      // uzanto lastfoje estis aldonita
      $rez []= "<ins>" .  $ero['uzanto'] . "</ins>";
    }
    else if ($ero['uzanto_aldonita'] < $ero['uzanto_forigita'])  {
      // uzanto lastfoje estis forigita
      $rez []= "<del>" .  $ero['uzanto'] . "</del>";
    }
    else {
      // uzanto jam ĉeestis komence de la voĉdono kaj daŭre ĉeestas.
      $rez []= $ero['uzanto'];
    }
  }
  return join(", ", $rez);
}


//function kalkuluForstrekitaj($id)
//{
//  $vocxdonintoj = dbElektu('vocxdonoj', 'propono = '.$_GET['id']);
//  $rez = 0;
//  for($i = 0; $i < count($vocxdonintoj); $i++)
//  {
//    if(count(dbElektu('uzantoj', "uzanto = '".$vocxdonintoj[$i]['uzanto']."'")) == 0)
//      $rez++;
//  }
//  return $rez;
//}


// ----------------- Komenco de vera agado --------------------

if(isset($_POST['vocxo']))
{
  if($fermita)
    die('La voĉdonado jam fermiĝis!<br>');

  $vocxdonantoj = dbElektu("vocxdonantoj",
			  " propono = '{$id}' and ".
			  " uzanto = '{$_SESSION['uzanto']}' ");
  if (count($vocxdonantoj) > 1) {
    die("Iu stranga problemo okazis (linio " . __LINE__ . " en " .
	__FILE__ ."), bonvolu rakontu al Paŭlo.");
  }
  if (count($vocxdonantoj) < 1) {
    die('Vi ne rajtas privoĉdoni tiun ĉi proponon!');
  }
  $vocxdonanto = $vocxdonantoj[0];
  
  if ($vocxdonanto['jam_vocxis'] > 0) {
    die('Vi jam voĉdonis!');
  }

  if ($vocxdonanto['uzanto_aldonita'] < $vocxdonanto['uzanto_forigita'])
    {
      die ("Via uzantokonto (respektive via voĉdonrajto pri tiu ĉi propono) ".
	   " estis forigita (kaj ankoraŭ ne realdonita). Plendu ĉe ĜenSek,".
	   " se vi supozas ke vi devus rajti voĉdoni pri tiu ĉi propono. ");
    }

  if (in_array($_POST['vocxo'],
	       array('1', '2', '3'))) {
    echo "<p>" . registruVocxdonon($id,
				   $_SESSION['uzanto'],
				   (int)$_POST['vocxo'],
				   $sekreta) .
      "</p>";
  }
  else {
    die("Erara voĉdono - la formularo ŝajne estas difekta. ".
	"Bonvolu raporti tiun eraron.");
  }

  
} // if $_POST


$vocxo = dbElektu('vocxdonoj', 'propono = '.$_GET['id'].
		  " AND uzanto = '".$_SESSION['uzanto']."'");




if(isset($_GET['moduso'])) {
  if($_GET['moduso'] == 'vocxu') {
    if(count($vocxo) > 1) {
      echo "<p>La sistemo renkontis seriozan problemon (#" . __LINE__ .
	"), kiu ne devus okazi! Bonvolu raporti tiun eraron!</p>\n";
    } elseif(count($vocxo) == 1) {
      echo "<p>Vi voĉdonis".vortumuVocxon($vocxo[0]['vocxo']).' je '
	.formatuTempon($vocxo[0]['kiam']).".</p>\n";
    }
    elseif((!$fermita) && ($flagoj & 1)) {

      // TODO: distingi laŭ voĉdontipo  (??)

      $kiuRajtas = $propono['flagoj'] & (2|8|16);
      if ($kiuRajtas & $flagoj) {
	echo "<h2>Voĉdono</h2>\n";
	kreuVocxdonFormularon($propono['enhavo']);
      }
      else {
	$listo = kiuRajtasVocxdoni($kiuRajtas);
	echo "<p>En tiu voĉdono vi ne rajtas voĉdoni, ĉar vi " .
	  (count($listo) == 1 ? "ne estas " : "estas nek ") .
	  implode(" nek ", $listo) . ".</p>";
      }
    }
    
  }  // moduso == vocxu
  else if ($_GET['moduso'] == 'vidu') {
    if(count($vocxo) > 1) {
      echo "<p>La sistemo renkontis seriozan problemon (#" . __LINE__ .
	"), kiu ne devus okazi! Bonvolu raporti tiun eraron!</p>\n";
    } elseif(count($vocxo) == 1) {
      echo "<p>Vi voĉdonis".vortumuVocxon($vocxo[0]['vocxo']).' je '
	.formatuTempon($vocxo[0]['kiam']).".</p>\n";
    }
    else {
      echo "<p>Vi ankoraŭ ne voĉis.</p>";
    }

  }

}
echo "<h2>Propono</h2>\n";


if ($fermita) {
  echo "<p>(Jam fermita.)</p>\n";
} else {
  echo "<p>(Ankoraŭ aktiva.)</p>\n";
}

  //echo '<h2>'.rezulto($propono).' propono:</h2>';

  echo "<table class='propono-datumoj'>\n";

  echo '<tr><th>Titolo</th><td/><td><strong>'.$propono['titolo']."</strong></td></tr>\n";
  echo '<tr><th>Propono</th><td/><td class="propono">'.nl2br($propono['enhavo'])."</td></tr>\n";

  $rajtintoj = dbElektu('vocxdonantoj', 'propono = '.$_GET['id'].' ORDER BY uzanto');


if($fermita)
{
  echo '<tr><th>Datoj</th><td/><td>La propono registriĝis je '.
    formatuTempon($propono['ekde']).
    ',<br/> kaj la voĉdonado fermiĝis je '.formatuTempon($propono['limtempo']).
    ".</td></tr>\n";
  echo '<tr><th>Jesis</th><td>'.$propono['jesis']."</td></tr>\n";
  echo "<tr><th>Neis</th><td>".$propono['neis'].'</td></tr>';
  echo "<tr><th>(Aktive) sindetenis</th><td>".$propono['sindetenis'].
    '</td></tr>';
  echo '<tr><th>Rajtintoj voĉdoni</th><td>'.count($rajtintoj).'</td><td>'.
    listuRajtantojn($rajtintoj).'</td></tr>';
  if($sekreta)
  {
    echo '<tr><th></th><td/><td>La voĉdonado estis <strong>sekreta</strong>.</td></tr>';
    //$forstrekitaj = kalkuluForstrekitaj($_GET['id']);
    //if($forstrekitaj > 0)
    //{
    //  if($forstrekitaj > 1)
    //    $pluralo = 'j';
    //  else
    //    $pluralo = '';
    //  echo 'Voĉdonis '.$forstrekitaj.' uzanto'.$pluralo.' kiu'.$pluralo.
    //    ' intertempe estas forstrekita'.$pluralo.' de la sistemo.<br>';
    //}
  }
  else
  {
    echo "<tr><th></th><td/><td>La voĉdonado estis <strong>publika</strong>.</td></tr>\n";
    $jesintoj = dbElektu('vocxdonoj', 'propono = '.$_GET['id'].
			 ' && vocxo = 1 ORDER BY uzanto');
    $neintoj = dbElektu('vocxdonoj', 'propono = '.$_GET['id'].
			' && vocxo = 2 ORDER BY uzanto');
/*     $sindetenintoj = */
/*       dbElektuDu('uzanto', 'vocxdonoj', */
/* 		 'propono = '.$_GET['id'].' AND vocxo = 3', */
/* 		 'uzanto', 'vocxdonantoj', */
/* 		 'propono = '.$_GET['id']. ' AND jam_vocxis = 0 '. */
/* 		 ' AND uzanto_forigita <= uzanto_aldonita '. */
/* 		 ' ORDER BY uzanto'); */
    $sindetenintoj_aktivaj = 
      dbElektu('vocxdonoj', 'propono = '.$_GET['id'].
	       ' && vocxo = 3 ORDER BY uzanto');
    $sindetenintoj_pasivaj = 
      dbElektu('vocxdonantoj', 'propono = '.$_GET['id'].
	       ' AND jam_vocxis = 0 '.
	       ' AND uzanto_forigita <= uzanto_aldonita '.
	       'ORDER BY uzanto');

    echo '<tr><th>Jesintoj</th><td>'.count($jesintoj).'</td><td>'
      .listo($jesintoj, 'uzanto')."</td></tr>\n";
    echo '<tr><th>Neintoj</th><td>'.count($neintoj).'</td><td>'
      .listo($neintoj, 'uzanto')."</td></tr>\n";
    echo '<tr><th>Sindetenintoj</th><td>'.count($sindetenintoj_aktivaj) .
      '</td><td>'.listo($sindetenintoj_aktivaj, 'uzanto')."</td></tr>\n";
    echo '<tr><th>Nepartoprenintoj</th><td>' . count($sindetenintoj_pasivaj) .
      '</td><td>'.listo($sindetenintoj_pasivaj, 'uzanto')."</td></tr>\n";
  }
}
else  // ne fermita
{
  echo '<tr><th>Datoj</th><td/><td>La propono registriĝis je '.
    formatuTempon($propono['ekde']).
    ',<br/> kaj la voĉdonado daŭros ĝis '.formatuTempon($propono['limtempo']).
    ".</td></tr>\n";
  echo '<tr><th>Rajtantoj voĉdoni</th><td>'.count($rajtintoj).'</td><td>'.
    listuRajtantojn($rajtintoj, 'uzanto').'</td></tr>';

  echo '<tr><th></th><td/><td>La voĉdonado estas <strong>' .
    ($sekreta ? "sekreta" : "publika") . '</strong>.</td></tr>';

}
if(estas_GxenSek()) // GxenSek
{
  $vocxdonintoj = dbElektu('vocxdonoj',
			   'propono = '.$_GET['id'].' ORDER BY uzanto');
  echo '<tr class="GxenSek"><th>Voĉdonis</th><td>'.count($vocxdonintoj).'</td><td>'
    .listo($vocxdonintoj, 'uzanto')."</td></tr>\n";
  $nevocxdonintoj = dbElektu('vocxdonantoj',
			     '  propono = '.$_GET['id'].
			     ' AND jam_vocxis = 0 '.
			     ' AND uzanto_forigita < 1 '.
			     'ORDER BY uzanto');
  echo '<tr class="GxenSek"><th>Ne voĉdonis</th><td>'.count($nevocxdonintoj).'</td><td>'.listo($nevocxdonintoj, 'uzanto')."</td></tr>\n";
}

echo "</table>";

switch(count($vocxo)) {
case 1:
  echo "<p>Vi jam voĉdonis pri tiu propono. ";
  if(!$sekreta) {
    ligilon("Montru!", "propono.php?id=".$_GET['id']."&moduso=vidu");
  }
  echo "</p>\n";
  break;
case 0:
  if(!$fermita) {
    $kiuRajtas = $propono['flagoj'] & (2|8|16);
    if ($kiuRajtas & $flagoj) {
      echo "<p>";
      ligilon("Voĉdonu!", "propono.php?id=".$_GET['id']."&moduso=vocxu");
      echo "</p>\n";
    } else {
      echo "<p>Vi ne rajtas voĉdoni pri tiu propono.</p>\n";
    }
  }
  break;
default:  // > 1
      echo "<p>La sistemo renkontis seriozan problemon (#" . __LINE__ .
	"), kiu ne devus okazi! Bonvolu raporti tiun eraron!</p>\n";
  break;  
}


if(estas_GxenSek()) {
  echo "<p>";
  ligilon('Forstreku tiun proponon!',
	  'forstrekuProponon.php?id='.$_GET['id'], 'GxenSek');
  echo "</p>\n";
}


?>
</body>
</html>