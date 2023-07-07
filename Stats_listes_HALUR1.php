<?php
/*
 * Zip2HAL - Importez vos publications dans HAL - Import your publications into HAL
 *
 * Copyright (C) 2023 Olivier Troccaz (olivier.troccaz@cnrs.fr) and Laurent Jonchère (laurent.jonchere@univ-rennes.fr)
 * Released under the terms and conditions of the GNU General Public License (https://www.gnu.org/licenses/gpl-3.0.txt)
 *
 * Statistiques HALUR - HALUR statistics
 */
 
$Fnm = "./Stats_HALUR1.php";
include $Fnm;
array_multisort($STATS_LISTE, SORT_DESC);

$ajout = count($STATS_LISTE);
$STATS_LISTE[$ajout]["quand"] = time();
$STATS_LISTE[$ajout]["qui"] = $HAL_USER;
$STATS_LISTE[$ajout]["quoi"] = $HAL_QUOI;

$total = count($STATS_LISTE);
array_multisort($STATS_LISTE, SORT_DESC);

$inF = fopen($Fnm,"w");
fseek($inF, 0);
$chaine = "";
$chaine .= '<?php'.chr(13);
$chaine .= '$STATS_LISTE = array('.chr(13);
fwrite($inF,$chaine);
foreach($STATS_LISTE AS $i => $valeur) {
	$chaine = $i.' => array(';
	$chaine .= '"quand"=>"'.$STATS_LISTE[$i]["quand"].'", ';
	$chaine .= '"qui"=>"'.$STATS_LISTE[$i]["qui"].'", ';
	$chaine .= '"quoi"=>"'.$STATS_LISTE[$i]["quoi"].'")';
	if ($i != $total-1) {$chaine .= ',';}
	$chaine .= chr(13);
	fwrite($inF,$chaine);
}
$chaine = ');'.chr(13);
$chaine .= '?>';
fwrite($inF,$chaine);
fclose($inF);
?>