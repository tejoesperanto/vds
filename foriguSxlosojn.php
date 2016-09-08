<?php

/**
 * Forigas ĉiujn ŝlosojn el la datumbazo.
 *
 * Tion nur faru, se estas iuj problemoj pri la funkciado,
 * ekzemple iu ne povas voĉdoni (kaj simple atendi minuton
 * ne helpas).
 *
 * Nur uzanto kun ĜenSek-rajtoj rajtas voki tiun ĉi dosieron,
 * kaj la simpla voko jam sufiĉas.
 */


session_start();

include('ink.php');

$flagoj = kontroluUzanton('foriguSxlosojn.php');

metu_kapon("Forigi ŝlosojn");

if(! estas_GxenSek())
  die('Nur la ĜenSek povas forigi la ŝlosojn!');


$db = dbMalfermu();
if(!$db)
  die('Ne sukcesis malfermi la datumbazon!<br>');

$rez = mysql_query('DELETE FROM '.dbPrefikso().'sxlosoj WHERE true', $db);
if($rez)
  die('Forigis ŝlosojn!<br>');
else
  die('Ne sukcesis forigi ŝlosojn!<br>' .
      mysql_error($db));

