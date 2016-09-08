<?php

 // ĉi tie metu pasvorton por ebligi instaladon. Forigu ĝin poste.
$instalPasvorto = '';

// la uzantnomo kaj komenca pasvorto de la administranta uzanto.
$gxensek = 'GxenSek';
$gxensekPasvorto = 'yyy';
$gxensekPlenaNomo = "Ĝenerala Sekretario";


// ---------- Post ĉi tie vi ne devos ŝanĝi ion. ---------------

require_once('ink.php');

metu_kapon("Instalado de la sistemo", 'GxenSek');

echo "</div>\n\n";

if ('' == $instalPasvorto) {
  // la paĝo estas malŝaltita.
?>
<p>
  Ĉi tiu paĝo povas instali la sistemon.

Se vi vere volas fari tion (eble ĝi jam estas instalita?),
redaktu la dosieron
kaj enmetu instal-Pasvorton komence de ĝi. Poste revoku la paĝon.
</p>
</body>
</html>
<?php
    exit();
}


if(!isset($_POST['pasvorto']) || $_POST['pasvorto'] != $instalPasvorto)
{
  $testo = dbElektu("uzantoj", " uzanto = 'GxenSek' ");
  $jam_instalita = (count($testo) == 1);

  ?>
<p>
    Ĉi tiu paĝo preparas la sistemon antaŭ la unua uzo, malinstalas
    ĝin aŭ helpas ĉe forgesita pasvorto.
</p>
  <form action="lancxu.php" method="post">
<p>
    Por lanĉi la sistemon, elektu la moduson kaj enmetu la
    instaladan pasvorton.
</p>
<p>
    <input name='moduso' type='radio' value='instali' />
    Instali la sistemon.</p>
<?php
   if ($jam_instalita) { ?>
    <p><strong>Averto:</strong>
    Aspektas, ke la sistemo jam estas preparita.
    Se vi elektas tiun ĉi opcion,
    ĉiuj datenoj estos forstrekitaj,
    kaj la sistemo estos lanĉita denove!
</p>
<?php } ?>
<p>
   <input name='moduso' type='radio' value='gxisdatigi' />
   Ĝisdatigi la datumbazajn tabelojn al la nova sistemo
   sen forigi la enhavon.  (Tion nur faru, se vi scias, kion
			    vi faras - do nur, se vi estas Paŭlo.)
</p>
<p>
    <input name='moduso' type='radio' value='malinstali' />
    Forigi ĉiujn datumbazajn tabelojn (malinstalo)
</p>
<p>
    <input name='moduso' type='radio' value='ghensek' />
    Nur rekreu la ĜenSek-konton kun la defaŭlta pasvorto
    (uzenda kiam ĜenSek forgesis sian pasvorton).<br/>
    (Tiun pasvorton eblas redakti antaŭe en la PHP-dosiero.)
</p>
<p>
    Pasvorto: <input name="pasvorto" type="password">
    <button value="lanchu" type="submit">Lanĉu</button>
</p>
  </form>
</body>
</html>
  <?php
    exit();
}

// nun ni vere faras ion ...


   /**
    * Malfermas la datumbazon.
    */
  function malfermuDatumbazon() {
    $db = dbMalfermu();
    if($db === false)
      die('Malsukcesis malfermi la datumbazon!');
    return $db;
  }


  /**
   * Forigas ĉiujn tabelojn kun ilia enhavo.
   */
  function foriguTabelojn($db) {

    $sukcese =
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'gxeneralajxoj', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'uzantoj', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'proponoj', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'vocxdonoj', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'sxlosoj', $db) && 
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'vocxdonantoj', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'nevocxdonintoj', $db);

    if($sukcese) {
      echo '<p>Forigis la datumbazajn tabelojn!</p>';
      return true;
    }
    else {
      echo '<p>Renkontis eraron - ne sukcesis forstreki ĉiujn datumojn!</p>';
      echo "<pre>" . mysql_error() . "</pre>";
      return false;
    }
  }

  /**
   * Adaptas la datumbazstrukturon el la strukturo de novembro 2009 al
   * la strukturo de junio 2010 sen forigi la datumojn.
   *
   * Nur faru tion, kiam ne estas aktivaj voĉdonoj.
   */
  function adaptuTabelojn($db) {
    $rez =
      mysql_query('ALTER DATABASE COLLATE utf8_esperanto_ci',
  		$db) &&
      mysql_query('ALTER TABLE `'.dbPrefikso().'uzantoj` ' . 
  		'  ADD COLUMN `funkcio`     varchar(100) NOT NULL'.
  		'  AFTER `pasvorto`', $db) &&
      mysql_query('ALTER TABLE `'.dbPrefikso().'vocxdonantoj` ' .
  		'  ADD COLUMN `jam_vocxis`      tinyint  NOT NULL,'.
  		'  ADD COLUMN `uzanto_forigita` tinyint  NOT NULL,'.
  		'  ADD COLUMN `uzanto_aldonita` tinyint  NOT NULL', $db) &&
      mysql_query('DROP TABLE IF EXISTS '.dbPrefikso().'nevocxdonintoj', $db);
    if($rez) {
      echo '<p>La sistemo bonorde estis ŝanĝita.</p>';
      return true;
    }
    else {
      echo '<p>Renkontis eraron - la sistemo ne lanĉiĝis!</p>';
      echo "<pre>" . mysql_error() . "</pre>";
      return false;
    }
    
  }  // adaptuTabelojn()

  /**
   * Kreas la datumbazajn tabelojn por la sistemo.
   */
  function kreuTabelojn($db) {
    mysql_query('ALTER DATABASE COLLATE utf8_esperanto_ci',
		$db);
    if(mysql_query('CREATE TABLE '.dbPrefikso(). 'uzantoj ' .
		   '   (uzanto     varchar( 30)  PRIMARY KEY,' .
		   '    plena_nomo varchar(100)                   NOT NULL,'.
		   '    retadreso  varchar(100) COLLATE ascii_bin NOT NULL,'.
		   '    pasvorto      char( 40) COLLATE ascii_bin NOT NULL,'.
		   '    funkcio    varchar(100)                   NOT NULL,'.
		   '    flagoj         int                        NOT NULL,'.
		   '    ekde           int                        NOT NULL)',
		   $db) &&
       mysql_query('CREATE TABLE '.dbPrefikso(). 'proponoj '.
		   '   (id         int PRIMARY KEY AUTO_INCREMENT, '.
		   '    titolo varchar(50) UNIQUE NOT NULL, '.
		   '    enhavo    text            NOT NULL, '.
		   '    flagoj     int            NOT NULL, '.
		   '    ekde       int            NOT NULL, '.
		   '    limtempo   int            NOT NULL,'.
		   '    jesis      int            NOT NULL,'.
		   '    neis       int            NOT NULL,'.
		   '    sindetenis int            NOT NULL)',
		   $db) &&
       mysql_query('CREATE TABLE '.dbPrefikso().'vocxdonoj '.
		   '   (propono    int, '.
		   '    uzanto varchar(30),'.
		   '    vocxo  tinyint NOT NULL,'.
		   '    kiam       int NOT NULL,'.
		   '    PRIMARY KEY (propono, uzanto))',
		   $db) &&
       mysql_query('CREATE TABLE '.dbPrefikso().'sxlosoj '.
		   '   (propono    int     PRIMARY KEY,'.
		   '    uzanto varchar(30) NULL)',
		   $db) &&
       mysql_query('CREATE TABLE '.dbPrefikso().'vocxdonantoj '.
		   '   (propono             int, '.
		   '    uzanto          varchar(30),' .
		   /* 1 = jam vocxdonis. */
		   '    jam_vocxis      tinyint     NOT NULL, ' .
		   /********************************************************
		    * a) uzanto_forigita == 0 && uzanto_aldonita == 0:
		    *  ==> nenio ŝanĝo pri rajtoj dum la vivotempo
		    *     de tiu propono.
		    * b) uzanto_forigita < uzanto_aldonita:
		    *  ==> la uzanto (aŭ la rajto voĉdoni pri tiu propono)
		    *     estis aldonita dum la vivotempo de tiu propono
		    *     (kaj do li rajtas voĉdoni).
		    * c) uzanto_aldonita < uzanto_forigita:
		    *  ==> la uzanto (aŭ la rajto voĉdoni pri tiu propono)
		    *     estis forigita dum la vivotempo de tiu propono
		    *     (kaj do li ne rajtas voĉdoni).
		    */
		   '    uzanto_forigita tinyint     NOT NULL, ' .
		   '    uzanto_aldonita tinyint     NOT NULL, '.
		   '    PRIMARY KEY (propono, uzanto) )', $db) ) {
      echo '<p>Kreis malplenajn datumbaztabelojn</p>';
      return true;
    }
    else {
      echo '<p>Renkontis eraron - la sistemo ne lanĉiĝis!</p>';
      echo "<pre>" . mysql_error() . "</pre>";
      return false;
    }
  }

  function foriguĜenSek($db) {
    if (mysql_query('DELETE FROM ' . dbPrefikso() . "uzantoj where uzanto = '".
		    $GLOBALS['gxensek'] . "'")){
      echo "<p>Forigis malnovan ĜenSek-konton.</p>";
      return true;
    }
    else {
      echo "<p>Ne sukcesis forigi ĜenSek-konton.</p>";
      return false;
    }
  }

  function kreuĜenSek($db) {
    $ĝensek = $GLOBALS['gxensek'];
    $kr_pasvorto = haketuPV($ĝensek, $GLOBALS['gxensekPasvorto']);

    $sql =
      'INSERT INTO '.dbPrefikso()."uzantoj " .
      "   SET uzanto = '" . $ĝensek . "', ".
      "       pasvorto = '" . $kr_pasvorto."', ".
      "       funkcio = 'Speciala konto por administrado', ".
      "       flagoj = 4, ".
      "       ekde = " . date('U');
    //    echo "<code>" . $sql . "</code>";

    if (mysql_query($sql, $db)) {
      echo "<p>Kreis novan ĜenSek-konton</p>\n";
      echo "<p>Bonvolu <a href='ensalutu.php'>ensaluti</a> " .
	"per la ĜenSek-konto kaj poste ŝanĝu la pasvorton " . 
	"(ĉe <em>Mia konto</em>).</p>";
      return true;
    }
    else {
      echo "<p>Io missukcesis, ne povis krei ĜenSek-konton.</p>";
      return false;
    }
  }

  $db = malfermuDatumbazon();

  switch($_POST['moduso']) {
  case 'instali':
    foriguTabelojn($db) &&
      kreuTabelojn($db) &&
      kreuĜenSek($db);
    break;
  case 'gxisdatigi':
    adaptuTabelojn($db);
    break;
  case 'malinstali':
    foriguTabelojn($db);
    break;
  case 'ghensek':
    foriguĜenSek($db) &&
      kreuĜenSek($db);
    break;
  default:
    echo "<p>Bonvolu reprovi, kaj tiam elektu unu el la tri modusoj!</p>";
    echo "</body></html>";
    return;
  }


?>
<p>Bonvolu redakti la dosieron kaj forigu la instaladan pasvorton.
La linioj komence de la dosiero aspektu tiel:
<pre style='border: thin solid; padding:1ex;'>
&lt;?php

 // ĉi tie metu pasvorton por ebligi instaladon. Forigu ĝin poste.
$instalPasvorto = '';

</pre>
<p>Tio malebligas postan fuŝadon de la sistemo fare de ne-bonuloj.</p>

</body>
</html>