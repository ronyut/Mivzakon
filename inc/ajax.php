<?php
require_once ("inc.php");
require_once ("functions.php");

/************************************************
    Headers
************************************************/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header("Access-Control-Allow-Headers: *");
header("Content-type: application/json; charset=utf-8");

#################################################

$inerval = 30;
$files = glob("../cache/*");
foreach($files as $file) {
	$cacheTime = preg_replace("/[^0-9]/", "", $file);
	
	// if not passed 5 minutes since last cache
	if($cacheTime + $inerval >= time()) {
		$cached = file_get_contents($file);
		die($cached);
	} else {
		unlink($file);
	}
}


$walla_1 = getWalla(1); // first page
$walla_2 = getWalla(2); // second page
$ynet    = getYnet();
$haaretz = getHaaretz();
//$hamas = getHamas();
$hamas = [];
//$saraya = getSaraya();

$articles = array_merge($walla_1, $walla_2, $ynet, $haaretz);


foreach ($articles as $i => $article) {
	$time[$i] = $article['time'];
}

$keywords = null;
if (isset($_GET["keywords"])) {	
	$keywords = getKeywords($articles);
}

// sort news by date desc
array_multisort($time, SORT_DESC, $articles);

//keep top 150 articles
$articles = array_slice($articles, 0, 100);

$output = array("keywords" => $keywords, "articles" => $articles, "hamas" => $hamas);

echo $encoded =  json_encode($output, JSON_UNESCAPED_UNICODE);

// save cache
if (isset($_GET["keywords"])) {	
	file_put_contents("../cache/".time(), $encoded);
	file_put_contents("../archive/".time(), $encoded);
}

?>