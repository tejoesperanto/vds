<?php

/**
 * Kreilo por nova propono.
 *
 * Nur uzebla de ĜenSek.
 *
 * La paĝo montras formularon por krei proponon.
 * Post la entajpado de la informoj eblas antaŭrigardi
 * la rezulton, kaj nur el la antaŭrigardo eblas krei
 * la proponon.
 *
 * Informoj senditaj de la formularoj:
 *
 *  $_POST['titolo']   - la titolo de la kreenda propono.
 *  $_POST['enhavo']   - la teksto de la kreenda propono.
 *  $_POST['flagoj']   - la tipo de la kreenda propono
 *  $_POST['ago']      - 'antauxrigardo' aŭ 'konfirmo'.
 *
 * nur el la antaŭrigardo:
 *  $_POST['limtempo'] - la limtempo en formo de unix-tempoŝtampo
 *
 * nur el la redaktado:
 *  $_POST['horo']
 *  $_POST['monato']
 *  $_POST['tago']
 *  $_POST['jaro']
 *     la limtempo en formo de dato+horo en GMT/UTC-tempo
 *
 *  $_POST['iksoj']  - 'JES' == anstataŭigu cx -> ĉ en titolo+enhavo,
 *                     'NE'  == ne anstataŭigu.
 *  
 */

session_start();

include('ink.php');

$flagoj = kontroluUzanton();

metu_kapon("Nova propono", "GxenSek");
laMenuon();

if(!estas_GxenSek())
  die('Nur la ĜenSek povas krei novajn proponojn!');




/**
 * Kreas la datumbazajn informojn por la propono.
 *
 * @param string $titolo la titolo de la propono.
 * @param string $enhavo la teksto de la propono.
 * @param int $flagoj indiko pri la tipo de la propono
 *               (kiu rajtas, kaj ĉu sekreta).
 * @param int $limtempo kiam finiĝas la voĉdontempo (kiel unix-tempoŝtampo)
 * @return string|int erarteksto aŭ identigilo por la kreita
 *                    propono.
 */
function kreuProponon($titolo, $enhavo, $flagoj, $limtempo)
{
  $db = dbMalfermu();
  if(!$db)
  {
    return 'Ne sukcesis malfermi datumbazon!<br>';
  }

  
  $kiuRajtas = $flagoj & (~1);

  //  echo "<code>Rajtas: " . $kiuRajtas . "</code>";

  $komitatanoj = dbElektu('uzantoj', 'flagoj & ' . $kiuRajtas, $db);
  if(!$komitatanoj)
    return "Mankas voĉdonrajtuntoj!";
  //  if(!mysql_query('UPDATE '.dbPrefikso().'gxeneralajxoj SET kio = '.($ajxo[0]['kio'] + 1).
  //    ' WHERE kiu = 0', $db))
  //    return false;
  if(!mysql_query('INSERT INTO '.dbPrefikso()."proponoj ".
		  " SET " .
		  "    titolo = '" . $_POST['titolo']."', ".
		  "    enhavo = '".$_POST['enhavo']."', ".
		  "    flagoj = ".$_POST['flagoj'].", ".
		  "    ekde =".date('U') .", ".
		  "    limtempo =".$_POST['limtempo'].", ".
		  "    jesis = 0, ".
		  "    neis = 0, ".
		  "    sindetenis = 0", $db))
    return mysql_error();

  $id = mysql_insert_id($db);

  
  $sql =
    "INSERT INTO " . dbPrefikso()."vocxdonantoj " .
    "   (propono, uzanto, jam_vocxis, uzanto_forigita, uzanto_aldonita) " .
    " SELECT " . $id . ", u.uzanto, 0, 0, 0 " .
    "   FROM " . dbPrefikso() . "uzantoj AS u " .
    "  WHERE (u.flagoj & " . $kiuRajtas . ") "; // rajtas pri tiu voĉdono

  if (!mysql_query($sql))
    return mysql_error();

  return $id;
}  // kreuProponon()

// --------------- nun ni ekagas ... -----------


if($_POST['ago'] == 'antauxrigardo')
{
  // ----------- antaŭrigardo -----------

  $titolo = senSlasxigu($_POST['titolo']);
  $enhavo = senSlasxigu($_POST['enhavo']);
  if($_POST['iksoj'] == 'JES') {
    // anstataŭo de la supersignoj el iks-kodigo.
    $anst = array('cx' => 'ĉ', 'Cx' => 'Ĉ', 'CX' => 'Ĉ',
		  'gx' => 'ĝ',	'Gx' => 'Ĝ', 'GX' => 'Ĝ',
		  'hx' => 'ĥ',	'Hx' => 'Ĥ', 'HX' => 'Ĥ',
		  'jx' => 'ĵ',	'Jx' => 'Ĵ', 'JX' => 'Ĵ',
		  'sx' => 'ŝ',	'Sx' => 'Ŝ', 'SX' => 'Ŝ',
		  'ux' => 'ŭ',	'Ux' => 'Ŭ', 'UX' => 'Ŭ',
		  );
    $titolo = strtr($titolo, $anst);
    $enhavo = strtr($enhavo, $anst);
  }

  if(strlen($titolo) > 0)
  {
    $limtempo = gmmktime($_POST['horo'], 0, 0, $_POST['monato'], $_POST['tago'], $_POST['jaro']);
    $nun = time();
    if($_POST['flagoj'] & 1)
      $tipo = 'sekreta';
    else
      $tipo = 'publika';
    ?>
    <form action="novpropono.php" method="post">
    <h2>Propono por <?php echo $tipo; ?> voĉdono</h2>
<dl>
<dt>Titolo</dt>
<dd><?php echo $titolo; ?></dd>
<dt>Enhavo</dt>
<dd><?php echo nl2br($enhavo); ?></dd>
<dt>Limtempo</dt>
<dd><?php echo formatuTempon($limtempo);
    
    ?><br/>
  (nun estas <?php echo formatuTempon($nun);
   ?>, tio estas entute <?php
   $tdiff = ($limtempo - $nun);
   $tdiff_tagoj = (int)($tdiff / (60 * 60 * 24)) ;
   $tdiff_horoj = ($tdiff / (60 * 60) ) % 24;
   echo $tdiff_tagoj . " tagoj kaj " . $tdiff_horoj . " horoj";
   ?>)</dd>
<dt>Sekreteco</dt>
<dd><?php echo $tipo ?></dd>
<dt>Kiu rajtas?</dt>
<dd><?php echo kiuRajtas($_POST['flagoj']);
   ?>:<br/>
   <?php // rajtanto-listo
   $listo = dbElektu("uzantoj", 'flagoj & '.( ~1 & (int)$_POST['flagoj']));
   if($listo) {
     echo listo($listo, 'uzanto');
   }
   else {
     echo "(problemo: " . mysql_error() . ")";
   }
   ?></dd>
</dl>
<p>
    <input type="hidden" name="titolo" value="<?php echo $titolo; ?>">
    <input type="hidden" name="enhavo" value="<?php echo $enhavo; ?>">
    <input type="hidden" name="flagoj" value="<?php echo $_POST['flagoj']; ?>">
    <input type="hidden" name="limtempo" value="<?php echo $limtempo; ?>">
    <input type="hidden" name="ago" value="konfirmo">
    <input type="submit" value="Kreu proponon!">
</p>
    </form>
    <hr/>
    <?php
  }
  else {
    echo "<p>Propono devas havi titolon!</p>\n";
  }

  // por la formularo
  $jaro = $_POST['jaro'];
  $monato = $_POST['monato'];
  $tago = $_POST['tago'];
  $horo = $_POST['horo'];
  //  $titolo = $_POST['titolo'];
  //  $enhavo = $_POST['enhavo'];

}
else if($_POST['ago'] == 'konfirmo')
{
  $id = kreuProponon($_POST['titolo'], $_POST['enhavo'], $_POST['flagoj'], $_POST['limtempo']);
  if(is_int($id))
  {
    echo '<p>Kreis ';
    ligilon('proponon #' . $id . " (".$_POST['titolo'].")",
	    'propono.php?id='.$id);
    die('!</p></body></html>');
  }
  else{
    echo '<p>Ne sukcesis krei proponon: </p>';
    echo "<p>" . $id . "</p>";
  }
}
else {
  //  kiom da tempo ni donu?
  //   ==> Komitata reglamento 5.6:
  //       La balotado daŭras 28 tagojn. La estraro anoncu la rezulton plej
  //       malfrue unu semajnon post la fino de la balotado.

  $horo = 12; // 12:00 de la elektita tago.

  // 28 tagoj kaj 12 horoj ekde nun, 
  // ==> ekde 12:00 ni iros al la sekva tago.
  // ==> la daŭro estas inter 28.0 kaj preskaŭ 29.0 tagoj.
  $tempo = (int)(time() + (28*24 + (24-$horo)) * 60 * 60);
  $jaro = gmdate('Y', $tempo);
  $monato = gmdate('m', $tempo);
  $tago = gmdate('d', $tempo);
  $titolo = '';
  $enhavo = '';
}

/**
 * Kreas elektilon (radio-butonon kun teksto) por unu el la tipoj.
 * @param int $valoro la sendenda valoro, se tiu ĉi estas elektita.
 * @param string $nomo priskribo por la tipo - montrata, sed ne sendata.
 * @param boolean $default indikas, ke tiu estu uzata kiam $_POST['flagoj']
 *    ne estas donita.
 */
function tipoelektilo($valoro, $nomo, $default=false) {
  echo "<input type='radio' name='flagoj' value='" .$valoro. "'";
  if ($_POST['flagoj'] == $valoro or
      (!isset($_POST['flagoj']) and $default))
    {
      echo " checked='checked'";
    }
  echo " />";
  echo $nomo;
  echo "<br/>\n";
}  // tipoelektilo

?>

<p>Por krei novan proponon, plenigu la jenajn kampojn kaj klaku.</p>

<form action="novpropono.php" method="post">
<table>
<tr><th>Titolo</th>
<td>
  <input type="text" name="titolo" value="<?php echo $titolo; ?>" />
</td></tr>
<tr><th>Tipo</th>
<td>
<?php
  ;
tipoelektilo(  2+8+16, "normala publika voĉdono", "defaŭlta");
tipoelektilo(1+2+8+16, "normala sekreta voĉdono");
tipoelektilo(1  +8+16,
	     "elekto de Estraro (Estraranoj ne rajtas voĉdoni, sekreta)");
tipoelektilo(1  +8,
	     "elekto de Komitatano Ĉ (nur Komitatanoj A/B rajtas voĉdoni,".
	     " sekreta)");
tipoelektilo(  2,
	      "publika voĉdono nur por la Estraro.");
tipoelektilo(1+2,
	      "sekreta voĉdono nur por la Estraro.");
?>
</td></tr>
<tr><th>La propono</th>
<td>
<textarea rows="10" cols="70" name="enhavo"><?php
  echo $enhavo;
?></textarea><br/>
<input type='hidden' name='iksoj' value='NE' />
<input type='checkbox' name='iksoj' value='JES' <?php
  ;
if($_POST['iksoj'] != 'NE') {
  echo " checked='checked' ";
}
?> /> Konvertu cx → ĉ.
</td></tr>
<tr><th>Limtempo</th>
<td>
jaro: <input type="text" name="jaro" value="<?php echo $jaro; ?>" size='5' />
monato: <input type="text" name="monato" value="<?php echo $monato; ?>" size='3' />
tago: <input type="text" name="tago" value="<?php echo $tago; ?>" size='3' />
horo: <input type="text" name="horo" value="<?php echo $horo; ?>" size='3' />
  (laŭ GMT/UTC)
<br/>
  (Nun estas <?php echo formatuTempon(time()); ?>.)
</td></tr>
</table>
<input type="hidden" name="ago" value="antauxrigardo">
<input type="submit" value="Antaŭrigardo">
</form>

</body>
</html>