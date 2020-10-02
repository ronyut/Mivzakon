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

$walla_1 = getWalla(1); // first page
$walla_2 = getWalla(2); // second page
$ynet    = getYnet();
$haaretz = getHaaretz();

$articles = array_merge($walla_1, $walla_2, $ynet, $haaretz);

$allText = "";
foreach ($articles as $i => $article) {
	$time[$i] = $article['time'];
	$allText .= $article["title"]." ";
	//$allText .= $article["body"]." ";
}
	
$stopwords = file_get_contents("stopwords.json");
$stopwords = json_decode($stopwords);

//$allText = "  בקורונה הקורונה מקורונה לקורונה וקורונה שהקורונה קורונה";

$replace_to_none = ['"', "&quot;", "&apos;"];
$clean = str_replace($replace_to_none, "", $allText);

$replace_to_space = [",", "-", "?", ".", "(", ")", "+", ":", "=", "/", "\\", "@", "#", "[", "]", "%", "^", "&", "*", ";"];
$clean = str_replace($replace_to_space, " ", $clean);
$clean = trimmer($clean);
$words = explode(" ", $clean);

$keywords = array();
foreach($words as $word) {
	// remove single hebrew letters or numbers
	if(is_numeric($word) || (mb_strlen($word) == 1 && !is_numeric($word) && !ctype_alpha($word))) {
		continue;
	}
	
	
	
	if (array_key_exists($word, $keywords)) {
		$keywords[$word] += 1;
		
	} else if (!in_array($word, $stopwords)) {
		$found = findSimilar($keywords, $word);
		
		if ($found == false) {
			$keywords[$word] = 1;
		} else {
			if ($found["replace"] === false) {
				$keywords[$found["found"]] += 1;
			} else {
				$keywords[$found["found"]] = $keywords[$found["replace"]] + 1;
				unset($keywords[$found["replace"]]);
			}
		}
	}
}
arsort($keywords);
//keep top 50 keywords
$keywords = array_slice($keywords, 0, 50);

// sort news by date desc
array_multisort($time, SORT_DESC, $articles);

//keep top 150 articles
$articles = array_slice($articles, 0, 100);

$output = array("keywords" => $keywords, "articles" => $articles);

echo json_encode($output, JSON_UNESCAPED_UNICODE);
?>