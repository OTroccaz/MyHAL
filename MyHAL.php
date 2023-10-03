<?php
/*
 * MyHAL - Vérifiez votre liste de publications - Check your paper list
 *
 * Copyright (C) 2023 Olivier Troccaz (olivier.troccaz@cnrs.fr) and Laurent Jonchère (laurent.jonchere@univ-rennes.fr)
 * Released under the terms and conditions of the GNU General Public License (https://www.gnu.org/licenses/gpl-3.0.txt)
 *
 * Page d'accueil - Home page
 */
 header('Content-Encoding: none;');
//authentification CAS ou autre ?
if (strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
  include('./_connexion.php');
}else{
  require_once('./CAS_connect.php');
	$HAL_USER = phpCAS::getUser();
	$HAL_QUOI = "OverHAL";
	if($HAL_USER != "jonchere" && $HAL_USER != "otroccaz") {include('./Stats_listes_HALUR1.php');}
}

//Nettoyage URL
$redir = "non";
$root = 'http';
if (isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")	{
  $root.= "s";
}
if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
}
$urlnet = $root."://".$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
$urlnet = str_replace(" ", "%20", $urlnet);
while (stripos($urlnet, "%3C") !== false) {
  $redir = "oui";
  $posi = stripos($urlnet, "%3C");
  $posf = stripos($urlnet, "%3E", $posi) + 3;
  $urlnet = substr($urlnet, 0, $posi).substr($urlnet, $posf, strlen($urlnet));
}
if ($redir == "oui") {header("Location: ".$urlnet);}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8" />
	<title>MyHAL - HALUR</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta content="MyHAL is a PHP program to help authors check their publication list in HAL" name="description" />
	<meta content="Coderthemes + Lizuka + OTroccaz + LJonchere" name="author" />
	<!-- App favicon -->
	<link rel="shortcut icon" href="favicon.ico">

	<!-- third party css -->
	<!-- <link href="./assets/css/vendor/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" /> -->
	<!-- third party css end -->

	<!-- App css -->
	<link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
	<link href="./assets/css/app-hal-ur1.min.css" rel="stylesheet" type="text/css" id="light-style" />
	<!-- <link href="./assets/css/app-creative-dark.min.css" rel="stylesheet" type="text/css" id="dark-style" /> -->
	
	<!-- bundle -->
	<script src="./assets/js/vendor.min.js"></script>
	<script src="./assets/js/app.min.js"></script>

	<!-- third party js -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
	<!-- <script src="./assets/js/vendor/Chart.bundle.min.js"></script> -->
	<!-- third party js ends -->
	<script src="./assets/js/pages/hal-ur1.chartjs.js"></script>
	
</head>

<?php
function suppression($dossier, $age) {
  $repertoire = opendir($dossier);
    while(false !== ($fichier = readdir($repertoire)))
    {
      $chemin = $dossier."/".$fichier;
      $age_fichier = time() - filemtime($chemin);
      if($fichier != "." && $fichier != ".." && !is_dir($fichier) && $age_fichier > $age)
      {
      unlink($chemin);
      //echo $chemin." - ".date ("F d Y H:i:s.", filemtime($chemin))."<br>";
      }
    }
  closedir($repertoire);
}

include("./Glob_normalize.php");
include("./MyHAL_codes_coll.php");
include("./MyHAL_docType.php");

function mb_ucwords($str) {
  $str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  return ($str);
}

function prenomCompInit($prenom) {
  $prenom = str_replace("  ", " ",$prenom);
  if (strpos(trim($prenom),"-") !== false) {//Le prénom comporte un tiret
    $postiret = mb_strpos(trim($prenom),'-', 0, 'UTF-8');
    if ($postiret != 1) {
      $prenomg = trim(mb_substr($prenom,0,($postiret-1),'UTF-8'));
    }else{
      $prenomg = trim(mb_substr($prenom,0,1,'UTF-8'));
    }
    $prenomd = trim(mb_substr($prenom,($postiret+1),strlen($prenom),'UTF-8'));
    $autg = mb_substr($prenomg,0,1,'UTF-8');
    $autd = mb_substr($prenomd,0,1,'UTF-8');
    $prenom = mb_ucwords($autg).".-".mb_ucwords($autd).".";
  }else{
    if (strpos(trim($prenom)," ") !== false) {//plusieurs prénoms
      $tabprenom = explode(" ", trim($prenom));
      $p = 0;
      $prenom = "";
      while (isset($tabprenom[$p])) {
        if ($p == 0) {
          $prenom .= mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8')).".";
        }else{
          $prenom .= " ".mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8')).".";
        }
        $p++;
      }
    }else{
      $prenom = mb_ucwords(mb_substr($prenom, 0, 1, 'UTF-8')).".";
    }
  }
  return $prenom;
}

function prenomCompEntier($prenom) {
  $prenom = trim($prenom);
  if (strpos($prenom,"-") !== false) {//Le prénom comporte un tiret
    $postiret = strpos($prenom,"-");
    $autg = substr($prenom,0,$postiret);
    $autd = substr($prenom,($postiret+1),strlen($prenom));
    $prenom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    $prenom = mb_ucwords($prenom);
  }
  return $prenom;
}

function nomCompEntier($nom) {
  $nom = trim(mb_strtolower($nom,'UTF-8'));
  if (strpos($nom,"-") !== false) {//Le nom comporte un tiret
    $postiret = strpos($nom,"-");
    $autg = substr($nom,0,$postiret);
    $autd = substr($nom,($postiret+1),strlen($nom));
    $nom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    $nom = mb_ucwords($nom);
  }
  return $nom;
}

function mise_en_evidence($phrase, $string, $deb, $fin) {
  $non_letter_chars = '/[^\pL]/iu';
  $words = preg_split($non_letter_chars, $phrase);

  $search_words = array();
  foreach ($words as $word) {
    if (strlen($word) > 2 && !preg_match($non_letter_chars, $word)) {
      $search_words[] = $word;
    }
  }

  $search_words = array_unique($search_words);

  $patterns = array(
    /* à répéter pour chaque caractère accentué possible */
    '/(ae|æ)/iu' => '(ae|æ)',
    '/(oe|œ)/iu' => '(oe|œ)',
    '/[aàáâãäåăãąā]/iu' => '[aàáâãäåăãąā]',
		'/[bḃбБ]/iu' => '[bḃбБ]',
    '/[cçčćĉċцЦ]/iu' => '[cçčćĉċцЦ]',
		'/[dďḋđдД]/iu' => '[dďḋđдД]',
    '/[eèéêëĕěėęēэЭ]/iu' => '[eèéêëĕěėęēэЭ]',
		'/[fḟƒфФ]/iu' => '[fḟƒфФ]',
		'/[gğĝġģгГ]/iu' => '[gğĝġģгГ]',
		'/[hĥħ]/iu' => '[hĥħ]',
    '/[iìíîïĩįīiiиИ]/iu' => '[iìíîïĩįīiiиИ]',
		'/[jĵйЙ]/iu' => '[jĵйЙ]',
		'/[kķк]/iu' => '[kķк]',
		'/[lĺľļłлЛ]/iu' => '[lĺľļłлЛ]',
		'/[mṁм]/iu' => '[mṁм]',
    '/[nñńňņн]/iu' => '[nñńňņн]',
    '/[oòóôõöőøōơ]/iu' => '[oòóôõöőøōơ]',
		'/[pṗпП]/iu' => '[pṗпП]',
		'/[rŕřŗ]/iu' => '[rŕřŗ]',
    '/[sšśŝṡşș]/iu' => '[sšśŝṡşș]',
		'/[tťṫţțŧт]/iu' => '[tťṫţțŧт]',
    '/[uùúûüŭųūư]/iu' => '[uùúûüŭųūư]',
		'/[vв]/iu' => '[vв]',
    '/[wẃẁŵẅ]/iu' => '[wẃẁŵẅ]',
		'/[yýÿỳŷ]/iu' => '[yýÿỳŷ]',
    '/[zžźżзЗ]/iu' => '[zžźżзЗ]',
  );

  foreach ($search_words as $word) {
    $search = preg_quote($word);
    $search = preg_replace(array_keys($patterns), $patterns, $search);
    return preg_replace('/\b' . $search . '(e?s)?\b/iu', $deb.'$0'.$fin, $string);
  }
}

//Suppresion des accents
function wd_remove_accents($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    return preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
}

function progression($indice, $iMax, $id, &$iPro, $quoi) {
	$iPro = $indice;
	echo '<script>';
  echo 'var txt = \'Traitement '.$quoi.' '.$indice.' sur '.$iMax.'<br>\';';
	echo 'document.getElementById(\''.$id.'\').innerHTML = txt';
	echo '</script>';
	ob_flush();
	flush();
	ob_flush();
	flush();
}

//Initialisation des variables
$idhal = "";
$teamcode = "";
$preaut = "";//Prénom auteur
$midaut = "";//"Middle name" auteur
$nomaut = "";//Nom auteur
$altaut = "";//Nom alternatif

$anneedeb = "";
$anneefin = "";
$yeardeb = "";
$yearfin = "";
$coll2 = "";

//Suppression des fichiers du dossier HAL créés il y a plus d'une heure
suppression("./HAL", 3600);

//Unicité des fichiers RTF créés
$unicite = time();

$collcodechk = "";
$nofulltextchk = "";
$showfivechk = "checked=\"\"";

if (isset($_POST["soumis"])) {
  $idhal = htmlspecialchars($_POST["idhal"]);
	$teamcode = strtoupper(htmlspecialchars($_POST["teamcode"]));
	$preaut = rawurlencode(ucwords(htmlspecialchars(mb_strtolower($_POST["preaut"], 'UTF-8'))));
	$midaut = rawurlencode(ucwords(htmlspecialchars(mb_strtolower($_POST["midaut"], 'UTF-8'))));
	$nomaut = rawurlencode(ucwords(htmlspecialchars(mb_strtolower($_POST["nomaut"], 'UTF-8'))));
	$altaut = rawurlencode(ucwords(htmlspecialchars(mb_strtolower($_POST["altaut"], 'UTF-8'))));
	if (strpos($nomaut, "-") !== false) {
		$tabnom = explode("-", $nomaut);
		$nomaut = ucfirst($tabnom[0])."-".ucfirst($tabnom[1]);
	}
	if (strpos($preaut, "-") !== false) {
		$tabpre = explode("-", $preaut);
		$preaut = ucfirst($tabpre[0])."-".ucfirst($tabpre[1]);
	}
	if (strpos($altaut, "-") !== false) {
		$tabalt = explode("-", $altaut);
		$altaut = ucfirst($tabalt[0])."-".ucfirst($tabalt[1]);
	}
	$coll = htmlspecialchars($_POST["coll"]);
	$coll2 = htmlspecialchars($_POST["coll2"]);
	if (isset($_POST["collcode"]) && $_POST["collcode"] == "oui") {$collcodechk = "checked=\"\"";}
	if (isset($_POST["nofulltext"]) && $_POST["nofulltext"] == "oui") {$nofulltextchk = "checked=\"\"";}
	if (isset($_POST["showfive"]) && $_POST["showfive"] == "oui") {$showfivechk = "checked=\"\"";}else{$showfivechk = "";}


	//export en RTF
	$Fnm = "./HAL/MyHAL_".$unicite.".rtf";
	require_once ("./lib/phprtflite-1.2.0/lib/PHPRtfLite.php");
	PHPRtfLite::registerAutoloader();
	$rtfic = new PHPRtfLite();
	$sect = $rtfic->addSection();
	$font = new PHPRtfLite_Font(12, 'Corbel', '#000000', '#FFFFFF');
	$fontlien = new PHPRtfLite_Font(12, 'Corbel', '#0000FF', '#FFFFFF');
	$fonth3 = new PHPRtfLite_Font(14, 'Corbel', '#000000', '#FFFFFF');
	$fonth2 = new PHPRtfLite_Font(16, 'Corbel', '#000000', '#FFFFFF');
	$parFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_JUSTIFY);

	if (isset($_POST['anneedeb']) & $_POST['anneedeb'] != "") {$anneedeb = "01/01/".$_POST['anneedeb'];}
  if (isset($_POST['anneefin']) & $_POST['anneefin'] != "") {$anneefin = "31/12/".$_POST['anneefin'];}
	
  // si anneedeb et anneefin non définies, on force anneedeb au 01/01/anneeencours et anneefin au 31/12/anneeencours
  if ($anneedeb == '' && $anneefin == '') {
		$anneeencours = date('Y', time());
    $anneedeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, ($anneeencours-5)));
    $anneefin = date('d/m/Y', mktime(0, 0, 0, 12, 31, $anneeencours));
  }
  // si anneedeb défini mais pas anneefin, on force anneefin à aujourd'hui
  if ($anneedeb != '' && $anneefin == '') {$anneefin = date('d/m/Y', time());}
  // si anneefin défini mais pas anneedeb, on force anneedeb au 1er janvier de l'année de anneefin
  if ($anneedeb == '' && $anneefin != '') {
    $tabanneefin = explode('/', $anneefin);
    $anneedeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, $tabanneefin[2]));
  }
  // si anneedeb est postérieure à anneefin, on inverse les deux
  if ($anneedeb != '' && $anneefin != '') {
    $tabanneedeb = explode('/', $anneedeb);
    $tabanneefin = explode('/', $anneefin);
    $timedeb = mktime(0, 0, 0, $tabanneedeb[1], $tabanneedeb[0], $tabanneedeb[2]);
    $timefin = mktime(0, 0, 0, $tabanneefin[1], $tabanneefin[0], $tabanneefin[2]);
    if ($timefin < $timedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}
  }
	$tabanneedeb = explode('/', $anneedeb);
  $tabanneefin = explode('/', $anneefin);
	$yeardeb = $tabanneedeb[2];
	$yearfin = $tabanneefin[2];
	
	//Recherche des résultats
	$atesteropt = "";
	
	//Conversion des dates au format HAL ISO 8601 jj/mm/aaaa > aaaa-mm-jjT00:00:00Z
  $tabanneedeb = explode('/', $anneedeb);
  $anneedebiso = $tabanneedeb[2].'-'.$tabanneedeb[1].'-'.$tabanneedeb[0].'T00:00:00Z';
  $tabanneefin = explode('/', $anneefin);
  $anneefiniso = $tabanneefin[2].'-'.$tabanneefin[1].'-'.$tabanneefin[0].'T00:00:00Z';
  $specificRequestCode = '%20AND%20producedDate_tdate:['.$anneedebiso.'%20TO%20'.$anneefiniso.']';
	
	//IdHAL, teamcode ou auteur ?
	if (isset($idhal) && $idhal != "") {
		 $atester = "authIdHal_s:".$idhal;
	}else{
		if (isset($teamcode) && $teamcode != "") {
			$atester = "collCode_s:".$teamcode;
		}else{
			//auteur_exp=soizic chevance,s chevance,s. chevance,sm chevance,s.m. chevance
			$atester = "(";
			
			$atester .= "authFullName_t:\"".$preaut." ".$nomaut."\"%20OR%20";
			$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".$nomaut."\"%20OR%20";
			$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".$nomaut."\"%20OR%20";
			if ($midaut != "") {
				$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".$nomaut."\"%20OR%20";
			}
			//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
			if (strpos($nomaut, " ") !== false) {
				$atester .= "authFullName_t:\"".$preaut." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				if ($midaut != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				}
			}
			//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
			if (strpos($nomaut, "-") !== false) {
				$atester .= "authFullName_t:\"".$preaut." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				if ($midaut != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				}
			}

			//Réitérer les tests avec prénoms 'nettoyés' des caractères accentués
			$preautnet = wd_remove_accents($preaut);
			$midautnet = wd_remove_accents($midaut);
			
			$atester .= "authFullName_t:\"".$preautnet." ".$nomaut."\"%20OR%20";
			$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".$nomaut."\"%20OR%20";
			$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".$nomaut."\"%20OR%20";
			if ($midautnet != "") {
				$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".$nomaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".$nomaut."\"%20OR%20";
			}
			//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
			if (strpos($nomaut, " ") !== false) {
				$atester .= "authFullName_t:\"".$preautnet." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".str_replace(" ", "-", $nomaut)."\"%20OR%20";
				}
			}
			//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
			if (strpos($nomaut, "-") !== false) {
				$atester .= "authFullName_t:\"".$preautnet." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $nomaut))."\"%20OR%20";
				}
			}
			
			//Réitérer les tests avec nom 'nettoyé' des caractères accentués
			$nomautnet = wd_remove_accents($nomaut);
			
			$atester .= "authFullName_t:\"".$preaut." ".$nomautnet."\"%20OR%20";
			$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".$nomautnet."\"%20OR%20";
			$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".$nomautnet."\"%20OR%20";
			if ($midautnet != "") {
				$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".$nomautnet."\"%20OR%20";
			}
			//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
			if (strpos($nomautnet, " ") !== false) {
				$atester .= "authFullName_t:\"".$preaut." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				}
			}
			//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
			if (strpos($nomautnet, "-") !== false) {
				$atester .= "authFullName_t:\"".$preaut." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				}
			}
			
			//Réitérer les tests avec prénoms et nom 'nettoyés' des caractères accentués
			$preautnet = wd_remove_accents($preaut);
			$midautnet = wd_remove_accents($midaut);
			$nomautnet = wd_remove_accents($nomaut);
			
			$atester .= "authFullName_t:\"".$preautnet." ".$nomautnet."\"%20OR%20";
			$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".$nomautnet."\"%20OR%20";
			$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".$nomautnet."\"%20OR%20";
			if ($midautnet != "") {
				$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".$nomautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".$nomautnet."\"%20OR%20";
			}
			//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
			if (strpos($nomautnet, " ") !== false) {
				$atester .= "authFullName_t:\"".$preautnet." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".str_replace(" ", "-", $nomautnet)."\"%20OR%20";
				}
			}
			//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
			if (strpos($nomautnet, "-") !== false) {
				$atester .= "authFullName_t:\"".$preautnet." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $nomautnet))."\"%20OR%20";
				}
			}
			
			//Réitérer si présence d'un nom alternatif
			if (isset($altaut) && $altaut != "") {
				$atester .= "authFullName_t:\"".$preaut." ".$altaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".$altaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".$altaut."\"%20OR%20";
				if ($midaut != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".$altaut."\"%20OR%20";
				}
				//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
				if (strpos($altaut, " ") !== false) {
					$atester .= "authFullName_t:\"".$preaut." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					if ($midaut != "") {
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					}
				}
				//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
				if (strpos($altaut, "-") !== false) {
					$atester .= "authFullName_t:\"".$preaut." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					if ($midaut != "") {
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					}
				}
				
				//Réitérer les tests avec prénoms 'nettoyés' des caractères accentués
				$preautnet = wd_remove_accents($preaut);
				$midautnet = wd_remove_accents($midaut);
				
				$atester .= "authFullName_t:\"".$preautnet." ".$altaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".$altaut."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".$altaut."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".$altaut."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".$altaut."\"%20OR%20";
				}
				//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
				if (strpos($altaut, " ") !== false) {
					$atester .= "authFullName_t:\"".$preautnet." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".str_replace(" ", "-", $altaut)."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".str_replace(" ", "-", $altaut)."\"%20OR%20";
					}
				}
				//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
				if (strpos($altaut, "-") !== false) {
					$atester .= "authFullName_t:\"".$preautnet." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $altaut))."\"%20OR%20";
					}
				}
				
				//Réitérer les tests avec nom 'nettoyé' des caractères accentués
				$altautnet = wd_remove_accents($altaut);
				
				$atester .= "authFullName_t:\"".$preaut." ".$altautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".$altautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".$altautnet."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".$altautnet."\"%20OR%20";
				}
				//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
				if (strpos($altautnet, " ") !== false) {
					$atester .= "authFullName_t:\"".$preaut." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					}
				}
				//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
				if (strpos($altautnet, "-") !== false) {
					$atester .= "authFullName_t:\"".$preaut." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut))." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preaut)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preaut." ".substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preaut)).substr($midaut, 0, 1)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preaut).substr($midaut, 0, 1).". ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					}
				}

				//Réitérer les tests avec prénom et nom 'nettoyés' des caractères accentués
				$preautnet = wd_remove_accents($preaut);
				$midautnet = wd_remove_accents($midaut);
				$altautnet = wd_remove_accents($altaut);
				
				$atester .= "authFullName_t:\"".$preautnet." ".$altautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".$altautnet."\"%20OR%20";
				$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".$altautnet."\"%20OR%20";
				if ($midautnet != "") {
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".$altautnet."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".$altautnet."\"%20OR%20";
				}
				//Si présence d'espaces dans le nom, tester aussi en les remplaçant par des tirets
				if (strpos($altautnet, " ") !== false) {
					$atester .= "authFullName_t:\"".$preautnet." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".str_replace(" ", "-", $altautnet)."\"%20OR%20";
					}
				}
				//Si présence de tirets dans le nom, tester aussi en les remplaçant par des espaces
				if (strpos($altautnet, "-") !== false) {
					$atester .= "authFullName_t:\"".$preautnet." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet))." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					$atester .= "authFullName_t:\"".prenomCompInit($preautnet)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					if ($midautnet != "") {
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".$preautnet." ".substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".str_replace(".", "", prenomCompInit($preautnet)).substr($midautnet, 0, 1)." ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
						$atester .= "authFullName_t:\"".prenomCompInit($preautnet).substr($midautnet, 0, 1).". ".ucwords(str_replace("-", " ", $altautnet))."\"%20OR%20";
					}
				}
			}
			
			$atester = substr($atester, 0, (strlen($atester) - 8));
			$atester .= ")";
		}
	}
	
	//Collection
	if (isset($coll) && $coll != "-") {
		$collection_exp = array_search($coll, $CODCOLL_LISTE);
		$atesteropt .= "%20AND%20collCode_s:".$collection_exp;
	}
	if (isset($coll2) && $coll2 != "") {
		if (isset($coll) && $coll != "-") {
			$atesteropt .= "%20OR%20collCode_s:".$coll2;
		}else{
			$atesteropt .= "%20AND%20collCode_s:".$coll2;
		}
	}
	
	$reqAPI = "https://api.archives-ouvertes.fr/search/?q=".$atester.$atesteropt.$specificRequestCode."&rows=100000&fl=citationFull_s,label_s,docType_s,title_s,producedDateY_i,collCode_s,files_s,authFullName_s,docid,linkExtId_s,linkExtUrl_s,arxivId_s,proceedings_s,status_i,doiId_s,halId_s,docid&sort=docType_s%20ASC,proceedings_s%20DESC,producedDateY_i%20DESC,auth_sort%20ASC";
	$reqAPI = str_replace('"', '%22', $reqAPI);
	$reqAPI = str_replace(" ", "%20", $reqAPI);
	//echo $reqAPI;
	
	$contents = file_get_contents($reqAPI);
	//$contents = utf8_encode($contents);
	$results = json_decode($contents);
	$numFound = 0;
	if (isset($results->response->numFound)) {$numFound=$results->response->numFound;}
}

?>

<body class="loading" data-layout="topnav" >

<noscript>
<div class='text-primary' id='noscript'><strong>ATTENTION !!! JavaScript est désactivé ou non pris en charge par votre navigateur : cette procédure ne fonctionnera pas correctement.</strong><br>
<strong>Pour modifier cette option, voir <a target='_blank' rel='noopener noreferrer' href='http://www.libellules.ch/browser_javascript_activ.php'>ce lien</a>.</strong></div><br>
</noscript>

        <!-- Begin page -->
        <div class="wrapper">

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">
								
								<?php
								include "./Glob_haut.php";
								?>
								
								<!-- Start Content-->
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <div class="page-title-right">
                                        <nav aria-label="breadcrumb">
                                            <ol class="breadcrumb bg-light-lighten p-2">
																								<li><a href="https://halur1.univ-rennes1.fr/MyHAL.php?logout="><i class="uil-power"></i> Déconnexion CAS CCSD</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>
                                                <li class="breadcrumb-item"><a href="index.php"><i class="uil-home-alt"></i> Accueil HALUR</a></li>
                                                <li class="breadcrumb-item active" aria-current="page">My<span class="font-weight-bold">HAL</span></li>
                                            </ol>
                                        </nav>
                                    </div>
                                    <h4 class="page-title">Check your paper list</h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                        <div class="row">
                            <div class="col-xl-8 col-lg-6 d-flex">
                                <!-- project card -->
                                <div class="card d-block w-100 shadow-lg">
                                    <div class="card-body">
                                        
                                        <!-- project title-->
                                        <h2 class="h1 mt-0">
                                            <i class="mdi mdi mdi-account-card-details text-primary"></i>
                                            <span class="font-weight-light">My</span><span class="text-primary">HAL</span>
                                        </h2>
                                        <h5 class="badge badge-primary badge-pill">Outline</h5>
																				
																				<img src="./img/elizabeth-jamieson-viaduct-france-unsplash.jpg" alt="Accueil MyHAL" class="img-fluid"><br>
																				<p class="font-italic">Photo : Viaduct France by Elizabeth Jamieson on Unsplash (détail)</p>

                                        <p class=" mb-2 text-justify">
                                           MyHAL is a PHP program to help authors check their publication list in HAL, made by by Olivier Troccaz (design & coding) and Laurent Jonchère (design). If you need help, please contact <a target="_blank" href="https://scienceouverte.univ-rennes.fr/interlocuteurs/laurent-jonchere">Laurent Jonchère</a> or <a target="_blank" href="https://scienceouverte.univ-rennes.fr/interlocuteurs/olivier-troccaz">Olivier Troccaz</a>. Its code is available <a target='_blank' rel='noopener noreferrer' href="https://github.com/OTroccaz/MyHAL">on GitHub</a> under the <a target='_blank' rel='noopener noreferrer' href="https://www.gnu.org/licenses/gpl-3.0.fr.html">GPLv3</a> license.
                                        </p>


                                    </div> <!-- end card-body-->
                                    
                                </div> <!-- end card-->

                            </div> <!-- end col -->
                            <div class="col-lg-6 col-xl-4 d-flex">
                                <div class="card shadow-lg w-100">
                                    <div class="card-body">
                                        <h5 class="badge badge-primary badge-pill">Instructions</h5>
                                        <div class=" mb-2">
                                            <ul class="list-group">
                                                <li class="list-group-item">
                                                    <a target="_blank" rel="noopener noreferrer" href="https://scienceouverte.univ-rennes1.fr/votre-bilan-crac-ribac">Your CRAC / RIBAC</a>
                                                </li>
																								<li class="list-group-item">
																										<a href="MyHAL_Tutorial.pdf"><i class="mdi mdi-file-pdf-box-outline mr-1"></i> MyHAL Tutorial</a>
																								</li>
                                            </ul> 
                                        </div>
                                    </div>
                                </div>
                                <!-- end card-->
                            </div>
                        </div>
                        <!-- end row -->

                        <div class="row">
                            <div class="col-12 d-flex">
                                <!-- project card -->
                                <div class="card w-100 d-block shadow-lg">
                                    <div class="card-body">
                                        
                                        <h5 class="badge badge-primary badge-pill">Settings</h5>
																				<form method="POST" accept-charset="utf-8" name="myhal" action="MyHAL.php?id=ok" class="form-horizontal">
																						<div class="border border-dark rounded p-2 mb-2">
																								<?php
																									if (!isset($_GET['id'])) {
																								?>
																								<script>
																									myWindow = window.open("https://univ-rennes.hal.science/submit/addfile/docid/XXXXXX", "_blank", "width=200, height=100");
																									let startDate = new Date().getTime();
																									let endDate = startDate;

																									// Added 3 sec timer
																									while (endDate < startDate + 3000) {
																										 endDate = new Date().getTime();
																									}
																									myWindow.close();
																									window.location.assign("#?id=ok");
																								</script>
																								<!--
																								<div id="clickhere" class="form-group row mb-1 justify-content-center d-flex">
																									<span class="col-12 col-md-12 col-form-label font-weight-bold justify-content-center d-flex">
																										Click&nbsp;<a href='?id=ok' onclick='openWin(); document.getElementById("clickhere").style.visibility="hidden"; closeWin();'>HERE</a>&nbsp;to get started
																									</span>
																								</div>
																								-->
																								<?php
																									}
																								?>
																								<div class="form-group row mb-1">
																										<span class="col-12 col-md-2 col-form-label font-weight-bold">
																										Enter your :
																										</span>
																										
																										<div class="col-12 col-md-10">
																												<div class="row mb-2">
																														<div class="col-md-6 form-inline">
																																<label for="preaut" class="d-block mr-2 w-30 font-weight-bold">First name : 
																																		</label>
																																<input type="text" id="preaut" name="preaut" class="form-control" value="<?php echo rawurldecode($preaut);?>" onkeydown="document.getElementById('idhal').value = ''; document.getElementById('teamcode').value = '';">
																																<br><br> <span class="small text-primary">(including accents and special characters!)</span>
																														</div>
																														<div class="col-md-6 form-inline">
																																<label for="midaut" class="d-block mr-2 w-30 font-weight-bold">Middle name : 
																																		<br> <span class="small text-info">Optional</span>
																																		</label>
																																<input type="text" id="midaut" name="midaut" class="form-control" value="<?php echo rawurldecode($midaut);?>" onkeydown="document.getElementById('idhal').value = ''; document.getElementById('teamcode').value = '';">
																														</div>
																												</div>

																												<div class="row">
																														<div class="col-md-6 form-inline">
																																<label for="nomaut" class="d-block mr-2 w-30 font-weight-bold">Last name : 
																																		</label>
																																<input type="text" id="nomaut" name="nomaut" class="form-control" value="<?php echo rawurldecode($nomaut);?>" onkeydown="document.getElementById('idhal').value = ''; document.getElementById('teamcode').value = '';">
																																<br><br> <span class="small text-primary">(including accents and special characters!)</span>
																														</div>
																														<div class="col-md-6 form-inline">
																																<label for="altaut" class="d-block mr-2 w-30 font-weight-bold">Alternate name : 
																																		<br> <span class="small text-info">Optional</span>
																																		</label>
																																		<div class="input-group">
																																				<div class="input-group-prepend">
																																						<button type="button" tabindex="0" class="btn btn-info" data-html="false" data-toggle="popover" data-trigger="focus" title="" data-content='eg. Maiden or hyphenated name' data-original-title="" data-placement="top">
																																						<i class="mdi mdi-help text-white"></i>
																																						</button>
																																				</div>
																																<input type="text" id="altaut" name="altaut" class="form-control" value="<?php echo rawurldecode($altaut);?>" onkeydown="document.getElementById('idhal').value = ''; document.getElementById('teamcode').value = '';">
																														</div>
																																
																														</div>
																												</div>

																												
																										</div>
																								</div> <!-- .form-group -->
																								
																								<div class="form-group row mb-1">
																										<div class="col-12">
																												<h3 class="d-inline-block border-bottom border-primary text-primary">OR</h3>
																										</div>
																								</div> <!-- .form-group -->

																								<div class="form-group row mb-2">
																										<label for="idhal" class="col-12 col-md-2 col-form-label font-weight-bold">
																										Your idHAL if you have one:
																										</label>
																										
																										<div class="col-12 col-md-10">
																												<div class="input-group">
																														<div class="input-group-prepend">
																																<button type="button" tabindex="0" class="btn btn-info" data-html="false" data-toggle="popover" data-trigger="focus" title="" data-content='HAL personal identifier. (eg. olivier-troccaz)' data-original-title="" data-placement="top">
																																<i class="mdi mdi-help text-white"></i>
																																</button>
																														</div>
																														<input type="text" id="idhal" name="idhal" class="form-control"  value="<?php echo $idhal;?>" onkeydown="document.getElementById('nomaut').value = ''; document.getElementById('midaut').value = ''; document.getElementById('preaut').value = ''; document.getElementById('altaut').value = ''; document.getElementById('teamcode').value = '';">
																												<a class="ml-2 small" target="_blank" rel="noopener noreferrer" href="#1">Create your IdHAL</a>
																												</div>
																										</div>
																								</div>
																								
																								<div class="form-group row mb-1">
																										<div class="col-12">
																												<h3 class="d-inline-block border-bottom border-primary text-primary">OR</h3>
																										</div>
																								</div> <!-- .form-group -->
																								
																								<div class="form-group row mb-2">
																										<label for="teamcode" class="col-12 col-md-2 col-form-label font-weight-bold">
																										Your team code (optionnal):
																										</label>
																										
																										<div class="col-6 col-md-10">
																												<div class="input-group">
																														<input type="text" id="teamcode" name="teamcode" class="form-control"  value="<?php echo $teamcode;?>" onkeydown="document.getElementById('nomaut').value = ''; document.getElementById('midaut').value = ''; document.getElementById('preaut').value = ''; document.getElementById('altaut').value = ''; document.getElementById('idhal').value = '';">
																												</div>
																										</div>
																										
																								</div> <!-- .form-group -->
																						</div> <!-- .form-group -->		
																						
																						<div class="form-group row mb-1">
																								<div class="form-group col-sm-2">
																									<label for="anneedeb">Publication Date : From</label>
																									<select id="anneedeb" class="custom-select" name="anneedeb">
																									<?php
																									$moisactuel = date('n', time());
																									if ($moisactuel >= 10) {$i = date('Y', time())+1;}else{$i = date('Y', time());}
																									while ($i >= date('Y', time()) - 50) {
																										if (isset($yeardeb) && $yeardeb == $i) {$txt = "selected";}else{$txt = "";}
																										echo '<option value='.$i.' '.$txt.'>'.$i.'</option>' ;
																										$i--;
																									}
																									?>
																									</select>
																								</div>
																								<div class="form-group col-sm-2">
																									<label for="anneefin">To</label>
																									<select id="anneefin" class="custom-select" name="anneefin">
																									<?php
																									$moisactuel = date('n', time());
																									if ($moisactuel >= 10) {$i = date('Y', time())+1;}else{$i = date('Y', time());}
																									while ($i >= date('Y', time()) - 50) {
																										if (isset($yearfin) && $yearfin == $i) {$txt = "selected";}else{$txt = "";}
																										echo '<option value='.$i.' '.$txt.'>'.$i.'</option>';
																										$i--;
																									}
																									?>
																									</select>
																								</div>
																								<!--
																								<div class="col-12 form-inline">
																										<span class="nameField font-weight-bold">Publication Date :&nbsp;</span>
																										<label for="anneedeb">From&nbsp;<i>(AAAA)</i>&nbsp;</label>
																										<input type="text" class="form-control mr-2" id="anneedeb" name="anneedeb" value="<?php echo $yeardeb;?>">
																										<label for="anneefin">To&nbsp;<i>(AAAA)</i>&nbsp;</label>
																										<input type="text" class="form-control" id="anneefin" name="anneefin" value="<?php echo $yearfin;?>">
																								</div>
																								-->
																						</div> <!-- .form-group -->
																						
																						<div class="form-group row mb-2">
																								<div class="col-12 col-md-7 form-inline">
																										<label for="coll" class="mr-2">Your lab (optional): </label>
																										<div class="input-group">
																												<div class="input-group-prepend">
																														<button type="button" tabindex="0" class="btn btn-info" data-html="false" data-toggle="popover" data-trigger="focus" title="" data-content='Optional but may be useful if you have namesakes (homonymes)' data-original-title="" data-placement="top">
																														<i class="mdi mdi-help text-white"></i>
																														</button>
																												</div>
																												<select id="coll" class="custom-select" size="1" name="coll" style="padding: 0px;">
																														<option value="-">-</option>
																														<?php
																														foreach ($CODCOLL_LISTE as $v) {
																															if (isset($coll) && $coll == $v) {$sel = "selected";}else{$sel = "";}
																															echo "<option ".$sel." value=\"".$v."\">".$v."</option>";
																														}
																														?>
																												</select>
																										</div>

																								</div>
																								<div class="col-12 col-md-4 form-inline">
				 
																										<label for="coll2">and/or your HAL collection code (optional): </label>
																										<input type="text" id="coll2" name="coll2" class="form-control" value="<?php echo $coll2;?>">
																										
																								</div>
																						</div> <!-- .form-group -->
																						
																						<div class="form-group row mb-1">
																								<div class="form-group col-sm-12">
																									<div class="custom-control custom-checkbox">
																										<input type="checkbox" id="collcode" class="custom-control-input" name="collcode" value="oui" <?php echo $collcodechk;?>>
																										<label for="collcode" class="custom-control-label">
																										Check if your papers are included in your lab Hceres list (optional)
																										</label>
																										<button type="button" tabindex="0" class="btn btn-info btn-sm" data-html="false" data-toggle="popover" data-trigger="focus" title="" data-content='Some papers may not bear the right affiliation, and thus not be included in your lab Hceres list' data-original-title="" data-placement="top">
																														<i class="mdi mdi-help text-white"></i>
																										</button>
																									</div>
																								</div>
																						</div> <!-- .form-group -->
																						
																						<div class="form-group row mb-1">
																								<div class="form-group col-sm-12">
																									<div class="custom-control custom-checkbox">
																										<input type="checkbox" id="nofulltext" class="custom-control-input" name="nofulltext" value="oui" <?php echo $nofulltextchk;?>>
																										<label for="nofulltext" class="custom-control-label">
																										Show only records without full text (optional)
																										</label>
																									</div>
																								</div>
																						</div> <!-- .form-group -->
																						
																						<div class="form-group row mb-1">
																								<div class="form-group col-sm-12">
																									<div class="custom-control custom-checkbox">
																										<input type="checkbox" id="showfive" class="custom-control-input" name="showfive" value="oui" <?php echo $showfivechk;?>>
																										<label for="showfive" class="custom-control-label">
																										Show 5 first authors et al.
																										</label>
																									</div>
																								</div>
																						</div> <!-- .form-group -->
																								
																						
																						<div class="form-group row mt-4">
                                                <div class="col-12 justify-content-center d-flex">
                                                    <input type="submit" class="btn btn-md btn-primary btn-lg" value="Submit" name="soumis">
                                                </div>
                                            </div>
																						
																				</form>
																				
																				</div> <!-- end card-body-->
                                    
                                </div> <!-- end card-->

                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                    </div>
                    <!-- container -->

<?php
$test = "non";
if (isset($_POST["soumis"]) && (($_POST["preaut"] == "" || $_POST["nomaut"] == "") && $_POST["idhal"] == "" && $_POST["teamcode"] == "")) {
	echo '<div id="warning-alert-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">';
		echo '<div class="modal-dialog modal-md modal-center">';
				echo '<div class="modal-content">';
						echo '<div class="modal-body p-4">';
								echo '<div class="text-center">';
										echo '<i class="dripicons-warning h1 text-warning"></i>';
										echo '<h4 class="mt-2">Warning</h4>';
										echo '<p class="mt-3"><strong>Name, team code or idHAL missing!!!<br>Please fill in your First and Last names or your team code or your idHAL!</strong></p>';
										echo '<button type="button" class="btn btn-warning my-2" data-dismiss="modal">Continuer</button>';
								echo '</div>';
						echo '</div>';
				echo '</div><!-- /.modal-content -->';
		echo '</div><!-- /.modal-dialog -->';
	echo '</div><!-- /.modal -->';
	echo '<script type="text/javascript">';
	echo '		(function($) {';
	echo '				"use strict";';
	echo '				$("#warning-alert-modal").modal(';
	echo '						{"show": true, "backdrop": "static"}';
	echo '								)';
	echo '		})(window.jQuery)';
	echo '</script>';
}else{
	$test = "oui";
}
								
if (isset($_POST["soumis"]) && $test == "oui") {
	echo '<br>';
	echo '<div class="container-fluid">';
	echo '<div class="row">';
	echo '<div class="col-12">';
	echo '<div class="card shadow-lg w-100">';
  echo '<div class="card-body">';
	echo '<div id=\'cpt\'></div>';
	if ($numFound == 0) {//Y-a-t-il au moins un résultat ?	
		echo '<a target=\'_blank\' href=\''.$reqAPI.'\'>API request link</a>';
		echo '<br><br>No result<br>';
		echo '<span class="text-primary">>>>> Please check if your first and last names are stated correctly, including accents and special characters</span>';	
	}else{
		//Si demandé, ne récupérer que les notices sans texte intégral associé
		if (isset($_POST["nofulltext"]) && $_POST["nofulltext"] == "oui") {
			$numFoundinit = $numFound;
			$tabAff = array();
			$tabHid = array();
			$tab = 0;
			$numFound = 0;
			$docType = $results->response->docs[0]->docType_s;
			$subType = "";
			$i = 1;
			foreach($results->response->docs as $entry){
				progression($i, $numFoundinit, 'cpt', $iPro, 'notice');
				if (isset($entry->files_s[0]) && $entry->files_s[0] != "") {
				}else{
					if ($docType != $entry->docType_s) {//Nouveau type de document
						$docType = $entry->docType_s;
						if ($docType != "COMM") {
						}else{
							if (isset($entry->proceedings_s) && $entry->proceedings_s == "1") {$subTypeN = "Proceedings papers";}else{$subTypeN = "Conference abstracts";}
							if ($subTypeN != $subType) {//Nouveau type de document parmi les COMM
								$subType = $subTypeN;
							}
						}
					}
					if ($docType == "COMM") {
						if (isset($entry->proceedings_s) && $entry->proceedings_s == "1") {$subTypeN = "Proceedings papers";}else{$subTypeN = "Conference abstracts";}
						if ($subTypeN != $subType) {//Nouveau type de document parmi les COMM
							$subType = $subTypeN;
						}
					}
					if ($entry->docType_s == "ART" || ($entry->docType_s == "COMM" && $subTypeN == "Proceedings papers")) {
						if (isset($entry->linkExtId_s) && $entry->linkExtId_s == "arxiv") {
						}else{
							if (isset($entry->doiId_s)) {
								$reqCRAC = "https://api.archives-ouvertes.fr/crac/hal/?q=doiId_s:%22".$entry->doiId_s."%22%20AND%20status_i:%220%22&fl=submittedDate_s";
							}else{
								$reqCRAC = "https://api.archives-ouvertes.fr/crac/hal/?q=title_s:%22".rawurlencode($entry->title_s[0])."%22%20AND%20status_i:%220%22&fl=submittedDate_s";
							}
							$reqCRAC = str_replace('"', '%22', $reqCRAC);
							$reqCRAC = str_replace(" ", "%20", $reqCRAC);
							//echo $reqCRAC.'<br>';
							
							$contCRAC = file_get_contents($reqCRAC);
							//$contCRAC = utf8_encode($contCRAC);
							$resCRAC = json_decode($contCRAC);
							$numFCRAC = 0;
							if (isset($resCRAC->response->numFound)) {$numFCRAC = $resCRAC->response->numFound;}
							if ($numFCRAC == 0) {//Pas de texte intégral associé
								$tabHid[$tab] = $entry->halId_s;
								$tabAff[$tab] = "oui";
								$numFound++;
							}
						}
					}
				}
				$tab++;
				$i++;
			}
		}else{
			//Vérification préalable > Si 2 notices ont des liens HAL identiques, n'en afficher qu'une seule (avec priorité pour celle qui a un PDF le cas échéant)
			$tabAff = array();
			$tabHid = array();
			$tabFil = array();
			$tab = 0;
			foreach($results->response->docs as $entry){
				if (!in_array($entry->halId_s, $tabHid)) {
					$tabHid[$tab] = $entry->halId_s;
					$tabAff[$tab] = "oui";
					if (isset($entry->files_s[0]) && $entry->files_s[0] != "") {$tabFil[$tab] = "oui";}else{$tabFil[$tab] = "non";}
				}else{
					$numFound--;
					$key = array_search($entry->halId_s, $tabHid);
					if ($tabFil[$key] == "oui") {$tabAff[$tab] = "non";}else{$tabAff[$key] = "non"; $tabAff[$tab] = "oui";}
				}
				$tab++;
			}
		}
		//var_dump($tabHid);
		//echo '<div class="alert alert-warning" role="alert"><strong><i class="mdi mdi-exclamation-thick"></i>Be sure to be <a target="_blank" rel="noopener noreferrer" href="https://hal.archives-ouvertes.fr/">logged in HAL</a> before adding files (ADD button) / Vous devez d\'abord être <a target="_blank" rel="noopener noreferrer" href="https://hal.archives-ouvertes.fr/">connecté à HAL</a> pour ajouter des fichiers (bouton ADD)</strong></div>';

		echo '<b>'.$numFound.' paper(s) for '.$yeardeb.'-'.$yearfin.'</b><br>';
		echo '<a href="#export">Export list <img src=\'./img/export_list.jpg\'></a>';
		echo ' / ';
		echo '<a target=\'_blank\' href=\''.$reqAPI.'\'>API request link</a>';
		
		$tab = 0;
		$i = 1;
		$docType = $results->response->docs[0]->docType_s;
		$subType = "";
		$year = $results->response->docs[0]->producedDateY_i;
		if ($docType != "COMM") {
			echo '<br><br><h4><b>'.$DOCTYPE_LISTE[$docType].'</b></h4>';
			$sect->writeText($DOCTYPE_LISTE[$docType]."<br><br>", $fonth2);
		}else{
			if (isset($results->response->docs[0]->proceedings_s) && $results->response->docs[0]->proceedings_s == "1") {$subTypeN = "Proceedings papers";}else{$subTypeN = "Conference abstracts";}
			if ($subTypeN != $subType) {//Nouveau type de document parmi les COMM
				$subType = $subTypeN;
				echo '<br><h4><b>'.$subType.'</b></h4>';
				$sect->writeText("<br><br>".$subType."<br><br>", $fonth2);
			}
		}
		echo '<h6><b>'.$year.'</b></h6>';
		$sect->writeText('<b>'.$year.'</b><br>', $fonth3);
		foreach($results->response->docs as $entry){
			if (isset($tabAff[$tab]) && $tabAff[$tab] == "oui") {
				if ($docType != $entry->docType_s) {//Nouveau type de document
					$docType = $entry->docType_s;
					if ($docType != "COMM" && isset($DOCTYPE_LISTE[$docType])) {
						echo '<br><br><h4><b>'.$DOCTYPE_LISTE[$docType].'</b></h4>';
						$sect->writeText($DOCTYPE_LISTE[$docType]."<br><br>", $fonth2);
					}else{
						if (isset($entry->proceedings_s) && $entry->proceedings_s == "1") {$subTypeN = "Proceedings papers";}else{$subTypeN = "Conference abstracts";}
						if ($subTypeN != $subType) {//Nouveau type de document parmi les COMM
							$subType = $subTypeN;
							echo '<br><h4><b>'.$subType.'</b></h4>';
							$sect->writeText("<br><br>".$subType."<br><br>", $fonth2);
						}
					}
				}
				if ($docType == "COMM") {
					if (isset($entry->proceedings_s) && $entry->proceedings_s == "1") {$subTypeN = "Proceedings papers";}else{$subTypeN = "Conference abstracts";}
					if ($subTypeN != $subType) {//Nouveau type de document parmi les COMM
						$subType = $subTypeN;
						echo '<br><h4><b>'.$subType.'</b></h4>';
						$sect->writeText("<br><br>".$subType."<br><br>", $fonth2);
					}
				}
				if ($year != $entry->producedDateY_i) {//Année différente
					$year = $entry->producedDateY_i;
					echo '<h6><b>'.$year.'</b></h6>';
					$sect->writeText('<b>'.$year.'</b><br>', $fonth3);
				}
				echo $i.". ";
				$sect->writeText($i.". ", $font);
				//Codes collection
				if ($collcodechk == "checked=\"\"" && isset($entry->collCode_s)) {
					$collCodeList = "";
					foreach($entry->collCode_s as $collCode){
						if (array_key_exists($collCode, $CODCOLL_LISTE) && strpos($collCodeList, $collCode) === false) {
							$collCodeList .= $collCode." - ";
							echo "<font color=red>".$collCode." - </font>";
							$sect->writeText($collCode." - ", $font);
						}
					}
					//HAL collection code
					if (isset($coll2) && $coll2 != "") {
						echo "<font color=red>".$coll2." - </font>";
						$sect->writeText($coll2." - ", $font);
					}
				}
				
				$citFull = $entry->citationFull_s;
				$labelS = $entry->label_s;
				//Si demandée, afficher la liste complète des auteurs
				if (!isset($_POST["showfive"])) {
					$listAut = "";
					$autEtal = "";
					$iAut = 0;
					foreach($entry->authFullName_s as $aut){
						$iAut++;
						if ($iAut == 6) {$autEtal = $listAut. 'et al.';}
						$listAut .= $aut.', ';
					}
					$listAut = substr($listAut, 0, (strlen($listAut) - 2));
					$citFull = str_replace($autEtal, $listAut, $citFull);
					$labelS = str_replace($autEtal, $listAut, $labelS);
				}
				echo str_replace($entry->title_s[0], "<font color=red>".$entry->title_s[0]."</font>", $citFull);
				if (isset($entry->files_s[0]) && $entry->files_s[0] != "") {
					echo "&nbsp;<a target='_blank' href='".$entry->files_s[0]."'><img style='width: 50px;' src='./img/pdf_grand.png'></a>";
				}else{
					if ($entry->docType_s == "ART" || ($entry->docType_s == "COMM" && $subTypeN == "Proceedings papers")) {
						if (isset($entry->linkExtId_s) && $entry->linkExtId_s == "arxiv") {
						}else{
							//Recherche d'une éventuelle notice avec le même DOI ou le même titre dans HAL CRAC > PDF soumis en attente de validation
							if (isset($entry->doiId_s)) {
								$reqCRAC = "https://api.archives-ouvertes.fr/crac/hal/?q=doiId_s:%22".$entry->doiId_s."%22%20AND%20status_i:%220%22&fl=submittedDate_s";
							}else{
								$reqCRAC = "https://api.archives-ouvertes.fr/crac/hal/?q=title_s:%22".rawurlencode($entry->title_s[0])."%22%20AND%20status_i:%220%22&fl=submittedDate_s";
							}
							$reqCRAC = str_replace('"', '%22', $reqCRAC);
							$reqCRAC = str_replace(" ", "%20", $reqCRAC);
							//echo $reqCRAC;
							
							$contCRAC = file_get_contents($reqCRAC);
							//$contCRAC = utf8_encode($contCRAC);
							$resCRAC = json_decode($contCRAC);
							$numFCRAC = 0;
							if (isset($resCRAC->response->numFound)) {$numFCRAC = $resCRAC->response->numFound;}
							if ($numFCRAC != 0) {
								$subDate = "";
								if (isset($resCRAC->response->docs[0]->submittedDate_s)) {$subDate = "<span class='text-third small'> on ".$resCRAC->response->docs[0]->submittedDate_s."</span>";}
								echo "&nbsp;<a href='#'><img alt='PDF already submitted to HAL' title='PDF already submitted to HAL' data-toggle=\"popover\" data-trigger='hover' data-content='Waiting to be processed before going online, yet subject to validation by HAL' data-original-title='' style='width: 50px;' src='./img/dep_grand.png'></a>".$subDate;
							}else{
								echo "&nbsp;<a target='_blank' href='https://univ-rennes.hal.science/submit/addfile/docid/".$entry->docid."'><img alt='Add paper' title='Add paper' data-toggle=\"popover\" data-trigger='hover' data-content='Important! DO NOT add the DOI number under \"Chargez les métadonnées à partir d&apos;un identifiant\" in the filling form. It would erase the existing metadata' data-original-title='' style='width: 50px;' src='./img/add_grand.png'></a>";
							}
						}
					}
				}
				if (isset($entry->linkExtId_s) && $entry->linkExtId_s == "arxiv") {
					echo "&nbsp;<a target='_blank' href='".$entry->linkExtUrl_s."'><img style='width: 50px;' src='./img/arxiv_grand.png'></a>";
				}
				echo '<br><br>';
				$sect->writeText($labelS, $font);
				$sect->writeText("<br><br>", $font);
				progression($i, $numFound, 'cpt', $iPro, 'notice sans texte intégral');
				$i++;
			}
			$tab++;
		}
		$rtfic->save($Fnm);
		echo '<center><b><a name="export" class="btn btn-secondary mt-2" href="'.$Fnm.'">Export to RTF (Word / LibreOffice)</a></b></center>';
		echo '<br>';
	}
	echo '</div> <!-- end card-body--> </div> <!-- end card--> </div> <!-- end col --> </div> <!-- end row --></div> </div>  <!-- end container -->';
	echo '<script>';
	echo 'document.getElementById(\'cpt\').style.display = \'none\';';
	echo '</script>';
}
?>
								
								</div>
                <!-- content -->
								
								<?php
								include('./Glob_bas.php');
								?>
								
								</div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
				
				<button id="scrollBackToTop" class="btn btn-primary"><i class="mdi mdi-24px text-white mdi-chevron-double-up"></i></button>
        <!-- END wrapper -->
				
				<!-- bundle -->
				<!-- <script src="./assets/js/vendor.min.js"></script> -->
				<script src="./assets/js/app.min.js"></script>

				<!-- third party js -->
				<!-- <script src="./assets/js/vendor/Chart.bundle.min.js"></script> -->
				<!-- third party js ends -->
				<script src="./assets/js/pages/hal-ur1.chartjs.js"></script>
				
				<script>
            (function($) {
                'use strict';
                $('#warning-alert-modal').modal(
                    {'show': true, 'backdrop': 'static'}    
                    
                        );
                $(document).scroll(function() {
                  var y = $(this).scrollTop();
                  if (y > 200) {
                    $('#scrollBackToTop').fadeIn();
                  } else {
                    $('#scrollBackToTop').fadeOut();
                  }
                });
                $('#scrollBackToTop').each(function(){
                    $(this).click(function(){ 
                        $('html,body').animate({ scrollTop: 0 }, 'slow');
                        return false; 
                    });
                });
            })(window.jQuery)
        </script>

		</body>
</html>
                                    