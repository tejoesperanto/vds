<?php

/**
 * Diversaj iloj rilatantaj al uzanto-redaktado.
 */



/**
 * Ŝanĝas la uzantonomon kaj kreas novan
 * pasvorton por la uzanto.
 *
 * @param string $malnova_uzanto - malnova uzantonomo
 * @param string $nova_uzanto - nova uzantonomo
 *
 * @return true se sukcesis (aŭ nenio estis farenda,
 *      ĉar la nomoj kongruas), alikaze false.
 */
function sxangxuUzantonomon($malnova_uzanto, $nova_uzanto) {
  if ($nova_uzanto == $malnova_uzanto) {
    return true;
  }
  if (count(dbElektu('uzantoj', "uzanto = '$nova_uzanto'")) > 0) {
    echo "Jam ekzistas uzanto kun nomo '{$nova_uzanto}' - ne eblas renomi {$malnova_uzanto} al ĝi.";
    return false;
  }
  $jam = dbElektu('uzantoj', "uzanto = '$malnova_uzanto'");
  if(count($jam) != 1)
  {
    echo "Uzanto {$malnova_uzanto} ne ekzistas (aŭ ekzistas plurfoje)!<br>";
    return false;
  }


  dbMalfermu();

  $rez =
    mysql_query('UPDATE '.dbPrefikso()."uzantoj ".
		"   SET uzanto = '$nova_uzanto' ".
		" WHERE uzanto = '$malnova_uzanto' ")
    &&
    mysql_query("UPDATE " .dbPrefikso()."vocxdonantoj " .
		  "   SET uzanto = '$nova_uzanto' " .
		  " WHERE uzanto = '$malnova_uzanto' ")
    &&
    mysql_query("UPDATE " .dbPrefikso()."vocxdonoj " .
		"   SET uzanto = '$nova_uzanto' " .
		" WHERE uzanto = '$malnova_uzanto' ");
  
  return
    $rez &&
    kreuNovanPasvorton($nova_uzanto);
  
}


/**
 * Ŝanĝas iujn ecojn de uzanto (krom pasvorto kaj uzantonomo).
 *
 * @param string $uzanto    la salutnomo.
 * @param int    $flagoj    la rajtoj de la uzanto.
 * @param string $nomo      la vera nomo de la uzanto.
 * @param string $retadreso la retpoŝtadreso de la uzanto.
 * @param string $funkcio   la salutnomo de la uzanto.
 */
function sxangxuUzanton($uzanto, $flagoj, $nomo, $retadreso, $funkcio)
{
  $db = dbMalfermu();

  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br>';
    return false;
  }


  $rez = mysql_query("START TRANSACTION", $db);
  if(!$rez) {
    echo "Ne sukcesis komenci transakcion.<br/>\n";
    return false;
  }
  

  $jam = dbElektu('uzantoj', "uzanto = '$uzanto'", $db);
  if(count($jam) != 1)
  {
    echo "Uzanto {$uzanto} ne ekzistas (aŭ ekzistas plurfoje)!<br>";
    return false;
  }


  // ŝanĝas la informojn
  $rez = mysql_query('UPDATE '.dbPrefikso()."uzantoj ".
		     "   SET plena_nomo = '$nomo', " .
		     "       retadreso = '$retadreso', " .
		     "       flagoj = $flagoj, " .
		     "       funkcio = '$funkcio' " .
		     " WHERE uzanto = '$uzanto' ", $db)
    // poste aktualigas la liston de voĉdonoj,
    // en kiuj li rajtas partopreni.
    && aktualiguVocxdonojnPorUzanto($flagoj,
				    (int)$jam[0]['flagoj'],
				    $uzanto, $db) &&
    mysql_query("COMMIT", $db);
  
  return $rez;
}


/**
 * kreas novan (hazardan) pasvorton por uzanto, metas ĝin
 * en la datumbazon kaj sendas ĝin al li.
 */
function kreuNovanPasvorton($uzanto)
{

  // Ĉu la uzanto fakte ekzistas?
  $jam = dbElektu('uzantoj', "uzanto = '$uzanto'");
  if(count($jam) != 1)
  {
    echo "Uzanto {$uzanto} ne ekzistas (aŭ ekzistas plurfoje)!<br>";
    return false;
  }


  $pasvorto = kreu_hazardan_pasvorton(10);
  $kr_pasvorto = haketuPV($uzanto, $pasvorto);


  $db = dbMalfermu();
  if(!$db)
  {
    echo 'Ne sukcesis malfermi datumbazon!<br>';
    return false;
  }

  // ŝanĝas la informojn
  $rez = mysql_query('UPDATE '.dbPrefikso()."uzantoj ".
		     "   SET pasvorto = '$kr_pasvorto'" .
		     " WHERE uzanto = '$uzanto' ")
    &&
    // informas la uzanton
    sendu_retmesagxon_al_uzanto($jam[0]['uzanto'], $jam[0]['retadreso'],
				$pasvorto);
  
  return $rez;
}




/**
 * Aktualigas la rajtojn de uzanto, aldonante ĝin al kaj/aŭ forprenante
 * ĝin de la nunaj voĉdonoj.
 *
 * @return boolean true, se sukcesis, alikaze false.
 */
function aktualiguVocxdonojnPorUzanto($novaj_rajtoj,
				      $malnovaj_rajtoj,
				      $uzanto, $db)
{
  // ~ funktioniert für Strings nicht wirklich. 
  $novaj_rajtoj = (int)$novaj_rajtoj;
  $malnovaj_rajtoj = (int)$malnovaj_rajtoj;
  $aldonaj_rajtoj = $novaj_rajtoj & ~$malnovaj_rajtoj & ~1;
  $forigitaj_rajtoj = $malnovaj_rajtoj & ~$novaj_rajtoj & ~1;

  $rez = true;
  if ($aldonaj_rajtoj != 0) {
    // li nun havas rajtojn, kiujn li ne havis antaŭe
    // => aldonu erojn en la voĉdonantoj-tabeloj (aŭ marku en
    //    jam ekzistantaj eroj).
    $rez = $rez and
      mysql_query("INSERT INTO ".dbPrefikso()."vocxdonantoj ".
		  " (propono, uzanto, jam_vocxis, uzanto_forigita, uzanto_aldonita) " .
		  "SELECT p.id, '{$uzanto}', 0, 0, 1 " .
		  "  FROM " .dbPrefikso()."proponoj AS p" .
		  "  WHERE " . // nun rajtas
		  "        (p.flagoj & " . $novaj_rajtoj . ") " .
		  //              sed ne rajtis antaŭe
		  "    AND ! (p.flagoj & " . $malnovaj_rajtoj . ") " .
		  //        propono ankoraŭ aktiva
		  "    AND p.limtempo > UNIX_TIMESTAMP() " .
		  "ON DUPLICATE KEY UPDATE " .
		  // aldonita > forigita => nun en stato 'aldonita'.
		  "  uzanto_aldonita = uzanto_forigita+1 ", $db);
  }
  if ($forigitaj_rajtoj != 0) {
    // se nun mankas rajtoj
    // => marku ĉe ĉiuj koncernaj voĉdonoj, ke li ne plu rajtas.
    $rez =
      $rez and
      mysql_query("UPDATE " . dbPrefikso()."vocxdonantoj AS v " .
		  "  JOIN " .dbPrefikso()."proponoj AS p " .
		  "    ON v.propono = p.id " .
		  // forigita > aldonita => nun en stato 'forigita'.
		  "   SET v.uzanto_forigita = v.uzanto_aldonita + 1 " .
		  " WHERE v.uzanto = '{$uzanto}' " .
		  //     nun ne plu rajtas 
		  "   AND ! (p.flagoj & " . $novaj_rajtoj . ") " .
		  //     sed ja rajtis antaŭe
		  "   AND (p.flagoj & " . $malnovaj_rajtoj . ") " .
		  //     ankoraŭ aktiva 
		  "   AND p.limtempo > UNIX_TIMESTAMP() ", $db);
  }
  return $rez;
}


/**
 * Transferas ĉiujn voĉdonrajtojn de ankoraŭ aktivaj voĉdonoj,
 * en kiu la malnova uzanto ankoraŭ ne voĉis, al la nova uzanto,
 * kaj forprenas ilin de la malnova uzanto.
 *
 */
function transferuVocxdonojn($nova, $malnova, $db) {

  // aldonu la voĉdon-rajtojn de la malnova al la nova
  // voĉdonanto.

  $rez = mysql_query("INSERT INTO " . dbPrefikso()."vocxdonantoj" .
		     "   (propono, uzanto, jam_vocxis, uzanto_forigita, uzanto_aldonita) " .
		     " SELECT v.propono, '$nova', 0, 0, 1 " .
		     "   FROM " .dbPrefikso()."vocxdonantoj AS v " .
		     "   JOIN " .dbPrefikso()."proponoj AS p ".
		     "     ON v.propono = p.id " .
		     "  WHERE v.uzanto = '$malnova' " .
		     "    AND v.jam_vocxis = 0 " .
		     "    AND v.uzanto_forigita <= v.uzanto_aldonita " .
		     "    AND UNIX_TIMESTAMP() < p.limtempo " .
		     "ON DUPLICATE KEY UPDATE " .
		     // aldonita > forigita => nun en stato 'aldonita'.
		     "  uzanto_aldonita = ".
		     dbPrefikso()."vocxdonantoj.uzanto_forigita+1 ", $db);

  // forigu la voĉdon-rajtojn de la malnova voĉdonanto.
  $rez = $rez &&
    mysql_query("UPDATE " . dbPrefikso()."vocxdonantoj AS v " .
		"  JOIN " . dbPrefikso()."proponoj AS p " .
		"    ON v.propono = p.id " .
		"  SET uzanto_forigita = uzanto_aldonita + 1 " .
		"  WHERE v.uzanto = '$malnova' " .
		"    AND UNIX_TIMESTAMP() < p.limtempo ", $db);
  return $rez;
  
}



/**
 * kreas novan uzanton.
 *
 * @param string $uzanto     uzantonomo.
 * @param string $pasvorto   la nova pasvorto.
 * @param int|string $flagoj rajtoj de la nova uzanto,
 *        aŭ 'anst', se tiu uzanto anstataŭas alian (ĉefe ĉe kom. A)
 * @param string $nomo       la plena nomo.
 * @param string $retadreso  
 * @param string $funkcio    
 * @param string $alia       nomo de la alia uzanto, kies lokon (kaj voĉon)
 *                           la nova uzanto transprenas. (Nur se flagoj=anst)
 */
function kreuUzanton($uzanto, $pasvorto, $flagoj,
		     $nomo, $retadreso, $funkcio, $alia)
{
  $db = dbMalfermu();
  if(!$db)
  {
    echo "Ne sukcesis malfermi datumbazon!<br/>\n";
    return false;
  }

  $rez = mysql_query("START TRANSACTION", $db);
  if(!$rez) {
    echo "Ne sukcesis komenci transakcion.<br/>\n";
    return false;
  }

  $jam = dbElektu('uzantoj', "uzanto = '$uzanto'", $db);
  if(count($jam) > 0)
  {
    echo "Uzanto $uzanto jam ekzistas!<br/>\n";
    return false;
  }

  if($flagoj == 'anst') {
    $aliajUzantoj = dbElektu('uzantoj', "uzanto = '$alia'", $db);
    if(count($aliajUzantoj) != 1) {
      echo "Uzanto '$alia' ne ekzistas.<br/>\n";
      return false;
    }
    $aliaUzanto = $aliajUzantoj[0];
    $flagoj = $aliaUzanto['flagoj'];
  }
  else {
    $flagoj = (int)$flagoj;
    $aliaUzanto = null;
  }

  // kriptigita pasvorto.
  $kr_pasvorto = haketuPV($uzanto, $pasvorto);

  $rez =
    mysql_query('INSERT INTO '.dbPrefikso()."uzantoj ".
		"   SET uzanto = '$uzanto', ".
		"       plena_nomo = '$nomo', " .
		"       retadreso = '$retadreso', " .
		"       pasvorto = '$kr_pasvorto',".
		"       funkcio = '$funkcio', " .
		"       flagoj = $flagoj, ".
		"       ekde = " . date('U').'', $db);
  if(!$rez) {
    return false;
  }
  if($aliaUzanto) {
    $rez = transferuVocxdonojn($uzanto, $alia, $db);
    if(!$rez) {
      return false;
    }

    $rez =
      mysql_query('UPDATE '.dbPrefikso().'uzantoj '.
		  '   SET flagoj = 0 ' .
		  " WHERE uzanto = '$alia'", $db) &&
      mysql_query("COMMIT", $db);
    
  }
  else {
    // Decido: novaj uzantoj ne rajtas tuj voĉdoni en aktivaj voĉdonoj.
    $rez = // aktualiguVocxdonojnPorUzanto($flagoj, 0, $uzanto, $db) &&
      mysql_query("COMMIT", $db);
  }

  return $rez;
}

/**
 * Kreas formularon por redakti/krei detalojn de uzanto.
 *
 * @param array $informoj  La informoj pri la uzanto (el la datumbazo),
 *       aŭ nenio.
 */
function kreu_uzantdetaloformularon($informoj="")
{
?>
  <table class='uzantodetaloj'>
<tr><th>Uzanto-nomo</th>
<td>
    <?php if(is_array($informoj)) {  ?>
    <input type="hidden" name="uzanto" value="<?php echo $informoj['uzanto']; ?>"/> 
    <code><?php echo $informoj['uzanto']; ?> </code>
    <?php } else { ?>
    <input type="text" name="uzanto" value=""/>
    Salutnomo por la sistemo. Atentu, ke ĝi estu tajpebla.
    <?php
    $informoj = array('plena_nomo' => '',
		      'retadreso' => '',
		      'funkcio' => '',
		      'flagoj' => '--');
    } ?>
</td></tr>
<tr><th>Plena nomo</th>
<td>
  <input type='text' name='plena_nomo' value="<?php echo $informoj['plena_nomo']; ?>" />
  Plena nomo de la persono.
</td></tr>
<tr><th>Retadreso</th>
<td>
  <input type='text' name='retadreso' value="<?php echo $informoj['retadreso'];?>" />
  Retpoŝtadreso por sendi la pasvorton tien.
</td></tr>
<tr><th>Funkcio</th>
<td>
  <input type='text' name='funkcio' value='<?php echo $informoj["funkcio"]; ?>' />
  (Ekzemple nomo de la sekcio, kiun oni reprezentas.)
</td></tr>
<tr><th>Uzanto-roloj</th>
<td>
<?php

    kreu_rajtoelektilon($informoj['flagoj']);

?>
</td></tr>
</table>
<?php
}




/**
 * Kreas rajtoelektilon (kiel parto de formularo):
 *<pre>
 *   * komitatano A/B
 *   * komitatano Ĉ
 *   * Estrarano
 *   * Observanto
 *</pre>
 * @param $flagoj la nunaj rajtoj de la uzanto por antaŭ-elektado,
 *  kiel bita kombino el:
 * <pre>
 *    1 - ajna komitatano
 *    2 - estrarano
 *    4 - ĜenSek
 *    8 - Komitatano A aŭ B
 *   16 - Komitatano Ĉ
 * </pre>
 * Alternative povas esti "--", kio signifas ke ankoraŭ ne estas ajnaj
 * rajtoj (tio estas la defaŭlto, se oni tute forlasas tion).
 *
 * Se $flagoj ne estas unu el la kombinoj, kiuj gvidas al la supre listigitaj,
 * aldoniĝas plia punkto, kiu ebligas lasi la nunajn rajtojn.
 */
function kreu_rajtoelektilon($flagoj="--")
{
  if (!isset($flagoj))
    $flagoj = "--";

  // la kutimaj rajtoj
  $rajtoj = array(array(1+8, "Komitatano A/B (rajtas partopreni ĉiujn voĉdonojn)"),
		  array(1+16, "Komitatano Ĉ (ne rajtas elekti Komiatantojn Ĉ)"),
		  array(1+2, "Estrarano (ne rajtas elekti Komitatanojn Ĉ kaj Estraranojn)"),
		  array(0, "Observanto (ne rajtas voĉdoni entute)"));

  // ni rigardas, ĉu $flagoj estas inter ili.
  foreach($rajtoj AS $rajto) {
    if ($rajto[0] == $flagoj) {
      $mia_rajto = $rajto;
    }
  }
  if ($flagoj == "--") {
    // ne estas rajtoj ĝis nun => prenu Komitatano A/B.
    $mia_rajto = $rajtoj[0];
  }
  
  // niaj rajtoj estas iel specialaj => aldonu ĝin al la listo.
  if (!isset($mia_rajto)) {
    $mia_rajto = array($flagoj,
		       "La nunaj roloj (".
		       implode(", ", roloTekstoj($flagoj)) . ")" );
      $rajtoj[]= $mia_rajto;
  }

  // kaj nun ni printas la liston.
  foreach($rajtoj AS $i => $rajto)
    {
      if ($i > 0) {
	echo "<br />\n";
      }
      echo "<input type='radio' name='flagoj' value='" . (int)$rajto[0] . "'";
      if ($rajto == $mia_rajto) {
	echo " checked='checked' ";
      }
      echo " />\n" . $rajto[1];
    }
  if($flagoj == '--') {
    echo "<br/>\n";
    echo "<input type='radio' name='flagoj' value='anst' />\n";
    echo "Posteulo de nuna komitatanto A: ";
    echo "<select name='anstataux'>\n";
    $listo = dbElektu("uzantoj", "flagoj & 8");
    echo "<!--" ;
    var_export($listo);
    echo "-->";
    foreach( $listo AS $ero) {
      echo "<option value='".$ero['uzanto']."'>".$ero['uzanto'].": ".
	$ero['plena_nomo'] . " (" . $ero['funkcio'].")</option>\n";
    }
    echo "</select>\n <br/>";
    echo "(Transprenas la voĉdonrajtojn de la elektita K-ano &ndash; en nun ".
      "aktivaj voĉdonoj nur, se la antaŭulo ankoraŭ ne voĉis.)";
  }

} // kreu_rajtoelektilon



/**
 * kreas hazardan literĉenon de la donita longeco.
 * Tio estas hazarda nombro inter 0 kaj 36^(longeco) -1
 */
function kreu_hazardan_pasvorton($longeco=6) {
  
  // kreas hazardan nombron inter 36^5 kaj 36^6 kaj konvertas ĝin
  // en la sistemo je bazo 36 (kie ĝi do havas 6 ciferojn).

  // kiom da ciferoj ni povas ekhavi en unu voko?
  $eblas  = (int)floor(log(mt_getrandmax(), 36));
  $bazo = pow(36, $eblas);

  //  echo "<pre>" . $eblas . "/" . $bazo . "</pre>\n";

  $havas = 0;
  $jam = "";

  while ($havas < $longeco) {
    $pvNombro = mt_rand(0, $bazo);
    $pvTeksto = str_pad(base_convert($pvNombro, 10, 36),
			$eblas, '0', STR_PAD_LEFT);
    //    echo "<pre>" . $pvNombro . "/" . $pvTeksto . "/".$jam."</pre>";
    $jam .= $pvTeksto;
    $havas += $eblas;
  }

  $pasvorto = substr($jam, 0, $longeco);
  
  return $pasvorto;
}  // kreu_hazardan_pasvorton





function sendu_retmesagxon_al_uzanto($nomo, $retadreso, $pasvorto)
{
  $mesagxo = <<<MESAGXOFINO
Saluton kaj bonvenon al la reta voĉdonsistemo de TEJO!

Via uzantonomo estas "{$nomo}".

Via pasvorto estas "{$pasvorto}".

Bonvolu iri al http://www.tejo.org/vds/, ensaluti kaj ŝanĝi
la pasvorton ĉe "Mia konto".

Kaze de problemoj sendu mesaĝon al gxen-sek@tejo.org (sed
ne respondu tiun ĉi, kaj ne sendu vian pasvorton al iu).

MESAGXOFINO
 ;
			     
$rez = mail($retadreso, 
	    "uzantonomo kaj pasvorto",
	    $mesagxo,
	    "From: gxen-sek@tejo.org\r\n". //TODO: prenu el DB
	    "Content-Type: text/plain; charset=utf-8"
	    );
			     
if($rez) {
  echo "<p>Sendis retmesaĝon al la nova uzanto!</p>\n";
}
else {
  echo "<p>La mesaĝo ne estis akceptita por sendado.</p>\n";
}
return $rez;

}  // sendu_retmesagxon_al_uzanto

