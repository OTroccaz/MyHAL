<?php
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

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">
<html lang="fr">
<head>
  <title>MyHAL : tool for retrieving an author's HAL publications</title>
  <meta name="Description" content="MyHAL : tool for retrieving an author's HAL publications">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" type="type/ico" href="favicon.ico">
  <script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>
  <script type='text/x-mathjax-config'>
    MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['$$','$$']]}});
  </script>
  <link rel="stylesheet" href="./MyHAL.css">
  <link rel="stylesheet" href="./bootstrap.min.css">
  <script src="./lib/jscolor-2.0.4/jscolor.js"></script>
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

include("./normalize.php");
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

//Initialisation des variables
$idhal = "";
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
$showfivechk = "checked=\"\"";

if (isset($_POST["soumis"])) {
  $idhal = htmlspecialchars($_POST["idhal"]);
	$preaut = ucwords(htmlspecialchars(mb_strtolower($_POST["preaut"], 'UTF-8')));
	$midaut = ucwords(htmlspecialchars(mb_strtolower($_POST["midaut"], 'UTF-8')));
	$nomaut = ucwords(htmlspecialchars(mb_strtolower($_POST["nomaut"], 'UTF-8')));
	$altaut = ucwords(htmlspecialchars(mb_strtolower($_POST["altaut"], 'UTF-8')));
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
	if ((isset($_POST["showfive"]) && $_POST["showfive"] == "oui")) {$showfivechk = "checked=\"\"";}else{$showfivechk = "";}


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

  /*
	//Extraction sur un IdHAL > auteur à mettre en évidence
  if (isset($evhal) && $evhal != "") {
		$listenominit = "~";
		$listenomcomp1 = "~";
		$listenomcomp2 = "~";
		$listenomcomp3 = "~";
		$arriv = "~";
		$depar = "~";
		$listTab = explode("~", $evhal);
		$listI = 0;
		while (isset($listTab[$listI])) {
			$list = explode(" ", $listTab[$listI]);
			$listenomcomp1 .= str_replace("_", " ", nomCompEntier($list[1]))." ".str_replace("_", " ", prenomCompEntier($list[0]))."~";
			$listenomcomp2 .= str_replace("_", " ", prenomCompEntier($list[0]))." ".str_replace("_", " ", nomCompEntier($list[1]))."~";
			$listenomcomp3 .= mb_strtoupper(nomCompEntier($list[1]), 'UTF-8')." (".prenomCompEntier($list[0]).")~";
			//si prénom composé et juste les ititiales
			$prenom = prenomCompInit($list[0]);
			$listenominit .= str_replace("_", " ", nomCompEntier($list[1]))." ".$prenom.".~";
			$arriv .= "1900~";
			$moisactuel = date('n', time());
			if ($moisactuel >= 10) {$idepar = date('Y', time())+1;}else{$idepar = date('Y', time());}
			$depar .= $idepar."~";
			$listI++;
		}
  }
	*/

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
	
	//IdHAL ou auteur ?
	if (isset($idhal) && $idhal != "") {
		 $atester = "authIdHal_s:".$idhal;
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
	
	//Collection
	if (isset($coll) && $coll != "") {
		$collection_exp = array_search($coll, $CODCOLL_LISTE);
		$atesteropt .= "%20AND%20collCode_s:".$collection_exp;
	}
	if (isset($coll2) && $coll2 != "") {
		if (isset($coll) && $coll != "") {
			$atesteropt .= "%20OR%20collCode_s:".$coll2;
		}else{
			$atesteropt .= "%20AND%20collCode_s:".$coll2;
		}
	}
	
	$reqAPI = "https://api.archives-ouvertes.fr/search/?q=".$atester.$atesteropt.$specificRequestCode."&rows=100000&fl=citationFull_s,label_s,docType_s,title_s,producedDateY_i,collCode_s,files_s,authFullName_t,docid,linkExtId_s,arxivId_s&sort=docType_s%20ASC,producedDateY_i%20DESC,auth_sort%20ASC";
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

<body style="font-family:calibri,verdana">

<noscript>
<div align='center' id='noscript'><font color='red'><b>ATTENTION !!! JavaScript est désactivé ou non pris en charge par votre navigateur : cette procédure ne fonctionnera pas correctement.</b></font><br>
<b>Pour modifier cette option, voir <a target='_blank' href='http://www.libellules.ch/browser_javascript_activ.php'>ce lien</a>.</b></div><br>
</noscript>

<table width="100%">
<tr>
<td style="text-align: left;"><img alt="MyHAL" height="69px" title="ExtrHAL" src="./img/logo_Myhal.png"></td>
<td style="text-align: right;"><img alt="Université de Rennes 1" title="Université de Rennes 1" width="150px" src="./img/logo_UR1_gris_petit.jpg"></td>
</tr>
</table>
<hr style="color: #467666; height: 1px; border-width: 1px; border-top-color: #467666; border-style: inset;">

<p>MyHAL is a PHP program by <a target="_blank" href="https://ecobio.univ-rennes1.fr/personnel.php?qui=Olivier_Troccaz">Olivier Troccaz</a> (ECOBIO - OSUR) to help you check your publication list in HAL.
<br>If you need help, please contact <a target="_blank" href="https://openaccess.univ-rennes1.fr/interlocuteurs/laurent-jonchere">Laurent Jonchère</a> or <a target="_blank" href="https://ecobio.univ-rennes1.fr/personnel.php?qui=Olivier_Troccaz">Olivier Troccaz</a>.</p>

<form method="POST" accept-charset="utf-8" name="myhal" action="MyHAL.php">
<p class="form-inline"><b><label for="auteur">Enter your : </label></b>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<b>First name</b> (<font color=red>including accents and special characters!</font>) <input type="text" id="preaut" name="preaut" class="form-control" style="height: 25px; width:150px" value="<?php echo $preaut;?>" onkeydown="document.getElementById('idhal').value = '';">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Middle name (optional) <input type="text" id="midaut" name="midaut" class="form-control" style="height: 25px; width:150px" value="<?php echo $midaut;?>" onkeydown="document.getElementById('idhal').value = '';"></p>
<p class="form-inline">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<b>Last name</b> <input type="text" id="nomaut" name="nomaut" class="form-control" style="height: 25px; width:150px" value="<?php echo $nomaut;?>" onkeydown="document.getElementById('idhal').value = '';">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Alternate name (optional)<a class=info onclick='return false' href="#"><img src="./img/pdi.jpg"><span>eg. maiden or hyphenated name</span></a> <input type="text" id="altaut" name="altaut" class="form-control" style="height: 25px; width:150px" value="<?php echo $altaut;?>" onkeydown="document.getElementById('idhal').value = '';">
</p>
<h3><b><u>or</u></b></h3>
<p class="form-inline"><b><label for="idhal">your idHAL if you have one</label></b> <a class=info onclick='return false' href="#"><img src="./img/pdi.jpg"><span>HAL personal identifier</span></a> <i>(eg. olivier-troccaz)</i> :
<input type="text" id="idhal" name="idhal" class="form-control" style="height: 25px; width:300px" value="<?php echo $idhal;?>" onkeydown="document.getElementById('nomaut').value = ''; document.getElementById('midaut').value = ''; document.getElementById('preaut').value = ''; document.getElementById('altaut').value = '';">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="https://hal.archives-ouvertes.fr/page/mon-idhal">Create my IdHAL</a></p>
<br>
<!--Période-->
<table>
<tr class="form-inline"><td><label class="nameField" for="periode">Publication Date :&nbsp;</label></td>
<td>

<label for="anneedeb">From <i>(AAAA)</i>&nbsp;</label><input type="text" class="form-control" id="anneedeb" style="width:100px; height:25px;" name="anneedeb" value="<?php echo $yeardeb;?>">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<label for="anneefin">To <i>(AAAA)</i>&nbsp;</label>
<input type="text" class="form-control" id="anneefin" size="1" style="width:100px; height:25px;" name="anneefin" value="<?php echo $yearfin;?>">
</select></td></tr>
</table><br><br>
<p class="form-inline"><b><label for="coll">Your lab <a class=info onclick='return false' href="#"><img src="./img/pdi.jpg"><span>optional but may be useful if you have namesakes (homonymes)</span></a> : </label></b>
<select id="coll" class="form-control" size="1" name="coll" style="padding: 0px;">
<option value=""></option>
<?php
foreach ($CODCOLL_LISTE as $v) {
	if (isset($coll) && $coll == $v) {$sel = "selected";}else{$sel = "";}
	echo ("<option ".$sel." value=\"".$v."\">".$v."</option>");
}
?>
</select>
<!--Or your HAL collection code-->
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
and/or your HAL collection code : <input type="text" id="coll2" name="coll2" class="form-control" style="height: 25px; width:150px" value="<?php echo $coll2;?>">
<p class="form-inline"><b><label for="collcode">Check if your papers are included in your lab Hceres list</label></b> <a class=info onclick='return false' href="#"><img src="./img/pdi.jpg"><span>Some papers may not bear the right affiliation, and thus not be included in your lab Hceres list</span></a> :
<input type="checkbox" id="collcode" value="oui" name="collcode" class="form-control" style="height:15px;" <?php echo $collcodechk;?>></p>
<p class="form-inline"><b><label for="showfive">Show 5 first authors et al.</label></b> :
<input type="checkbox" id="showfive" value="oui" name="showfive" class="form-control" style="height:15px;" <?php echo $showfivechk;?>></p>
<input type="submit" class="btn btn-md btn-primary" value="Submit" name="soumis">
</form>

<?php

if (isset($_POST["soumis"])) {
	
	echo '<br><br>';
	if ($numFound == 0) {//Y-a-t-il au moins un résultat ?
		echo ('No result<br>');
		echo ('<font color="red">>>>> Please check if your first and last names are stated correctly, including accents and special characters</font>');
	}else{
		echo '<b>'.$numFound.' paper(s) for '.$yeardeb.'-'.$yearfin.'</b><br><a href="#export">Export list</a>';
		$i = 1;
		$docType = $results->response->docs[0]->docType_s;
		$year = $results->response->docs[0]->producedDateY_i;
		echo '<br><br><h4><b>'.$DOCTYPE_LISTE[$docType].'</b></h4>';
		$sect->writeText($DOCTYPE_LISTE[$docType]."<br><br>", $fonth2);
		echo '<h6><b>'.$year.'</b></h6>';
		$sect->writeText('<b>'.$year.'</b><br>', $fonth3);
		foreach($results->response->docs as $entry){
			if ($docType != $entry->docType_s) {//Nouveau type de document
				$docType = $entry->docType_s;
				echo '<br><h4><b>'.$DOCTYPE_LISTE[$docType].'</b></h4>';
				$sect->writeText("<br><br>".$DOCTYPE_LISTE[$docType]."<br><br>", $fonth2);
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
			//Si demandé, afficher la liste complète des auteurs
			if (!isset($_POST["showfive"])) {
				$listAut = "";
				$autEtal = "";
				$iAut = 0;
				foreach($entry->authFullName_t as $aut){
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
				echo ("&nbsp;<a target='_blank' href='".$entry->files_s[0]."'><img src='./img/pdf.png'></a>");
			}else{
				if ($entry->docType_s == "ART") {
					if (isset($entry->linkExtId_s) && $entry->linkExtId_s == "arxiv") {
					}else{
						echo ("&nbsp;<a target='_blank' href='https://hal-univ-rennes1.archives-ouvertes.fr/submit/addfile/docid/".$entry->docid."'><img alt='Add paper' title='Add paper' src='./img/add.png'></a>");
					}
				}
			}
			if (isset($entry->linkExtId_s) && $entry->linkExtId_s == "arxiv") {
				echo ("&nbsp;<a target='_blank' href='https://arxiv.org/abs/".$entry->arxivId_s."'><img src='./img/arxiv.png'></a>");
			}
			echo ('<br><br>');
			$sect->writeText($labelS, $font);
			$sect->writeText("<br><br>", $font);
			$i++;
		}
		$rtfic->save($Fnm);
		echo '<center><b><a name="export" href="'.$Fnm.'">Export to RTF (Word / LibreOffice)</a></b></center>';
		echo '<br><br>';
	}
	
}
?>
<?php
include('./bas.php');
?>