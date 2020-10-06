<?php
    
    date_default_timezone_set('Asia/Jerusalem');
    
    /**************************************************************
		Global Vars
	**************************************************************/
    $ROOT_PATH = getProtocol()."://$_SERVER[HTTP_HOST]";
    $BASE_PATH = getProtocol()."://$_SERVER[HTTP_HOST]"."/biochem";
    $ACTUAL_LINK = $ROOT_PATH.$_SERVER["REQUEST_URI"];

    /**************************************************************
		isSecureConn
		check if https
	**************************************************************/
	function isSecureConn() {
      return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
    }
    
    /**************************************************************
		getProtocol
		return http or htts
	**************************************************************/
    function getProtocol(){
        if(isSecureConn()){
            return "https";
        }
        return "http";
    }
    
    
	/**************************************************************
		escape
		escape illegal chars
	**************************************************************/
	function escape($string) {
		global $db;
		return mysqli_real_escape_string($db, $string);
	}

	/**************************************************************
		query:
		Perform Mysqli query
	**************************************************************/
	function query($sql) {
		global $db;
		$query = mysqli_query($db, $sql) or die(mysqli_error($db));
		return $query;
	}
    
    /**************************************************************
		compare:
		Compare which number is bigger
	**************************************************************/
    function compare($a, $b) {
        return $a->percent < $b->percent;
    }
    
    /**************************************************************
		intToBool:
		Int to bool
	**************************************************************/
    function intToBool($int) {
        if($int == 0) {
            return false;
        } else {
            return true;
        }
    }
    
    /**************************************************************
		textToBool:
		Text to bool
	**************************************************************/
    function textToBool($text) {
        return $text == "true";
    }
    
    /**************************************************************
		boolToInt:
		bool to int
	**************************************************************/
    function boolToInt($bool) {
        if($bool) {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**************************************************************
		contains:
		Check if string has subtring
	**************************************************************/
    function contains($str, $needle) {
        return strpos($str, $needle) !== false;
    }
    
    /**************************************************************
		trimmer:
		Trim spaces and remove multipule spaces
	**************************************************************/
    function trimmer($input) {
        return trim(preg_replace("/\s+/u", " ", $input));
    }
    
    /**************************************************************
        getValueFromDB:
        Get single value from db via sql query
	**************************************************************/
    function getValueFromDB($sql, $col) {
        $query = query($sql);
        if(mysqli_num_rows($query) > 0) {
            while($row = mysqli_fetch_array($query)){
                return $row[$col];
            }                
        }
        return false;
    }
    
    /**************************************************************
        cleanWords:
        cleanWords
	**************************************************************/
    function cleanWords($words) {
        $newArr = array();
        foreach($words as $word){
            $clean = trimmer($word);
            array_push($newArr);
        }
        return $newArr;
    }
    
    /**************************************************************
        getHash:
        getHash
	**************************************************************/
    function getHashWithSalt($email) {
        $SALT = "$#W87hGFXC)_O&^RTFLKMGHVFDX$%SE09i;lm,GHV45esL:09iHYUJVG".time();
        return password_hash($email.$salt, PASSWORD_ARGON2I);
    }
    
    /****************************************************************************
        curl:
        Get a web file (HTML, XHTML, XML, image, etc.) from a URL. Return an
        array containing the HTTP server response header fields and content.
	****************************************************************************/
    function curl($url)
    {
        require_once "simple_html_dom.php";
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "spider", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $dom = new simple_html_dom(null, true, true, DEFAULT_TARGET_CHARSET, true, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $dom->load($content, true, true);
        return $header;
    }

    /**************************************************************
        getWalla:
        Crawl Walla
	**************************************************************/
    function getWalla($page) {
        $articles = array();
        
        $url = 'https://news.walla.co.il/breaking?page='.$page;
        $html = curl($url)["content"];
        // Find all article blocks
        $date = date('Y-m-d', strtotime("now"));
        
        foreach($html->find('ul.breaking-list > li') as $article) {
            $class = $article -> getAttribute('class');
            if(!empty($class)) {
                if ($class == "date") {
                    $date = trimmer($article -> find('.date-part-1', 0) -> plaintext);
                    $date = explode(" ", $date)[2];
                    $date = date("Y-m-d", strtotime($date));
                }
                continue;
            }
            
            $item['title'] = trimmer($article -> find('.title', 0) -> plaintext);
            $item['body'] = trimmer($article -> find('.content', 0) -> plaintext);
            $item['hour'] = $article -> find('.time', 0) -> plaintext;
            $item['time'] = $date." ".$item['hour'];
            $item['date'] = $date;
            $item['source'] = "וואלה!";
            $item['img'] = "walla";
            
            $wallaID = $article -> find('.body > a', 0) -> href;
            $item['articleID'] = $wallaID;
            
            $articles[] = $item;
        }
        return $articles;
    }
    
    /**************************************************************
        getYnet:
        Crawl Ynet
	**************************************************************/
    function getYnet() {
        $articles = array();
        
        $url = 'https://www.ynet.co.il/home/0,7340,L-184,00.html';
        $html = curl($url)["content"];
        // Find all article blocks
        foreach($html->find('.smallheader') as $article) {
            $item['title'] = trimmer($article -> plaintext);
            $time_zevel = $article -> parent -> plaintext;
            
            $fulltime = substr(substr($time_zevel, -17), 0, -1);
            $fulltime = str_replace(',','', $fulltime);
            $onlyTime = substr($fulltime, 0, 5);
            $onlyDate = substr(substr($fulltime, -8), 0, -2).date('Y');
            $onlyDate = str_replace('.', '-', $onlyDate);
            $newDate = date("Y-m-d", strtotime($onlyDate));
            $item['date'] = $newDate;
            $item['time'] = $newDate." ".$onlyTime;
            $item['hour'] = date('H:i', strtotime($item['time']));
            $item['source'] = "ynet";
            $item['body'] = "";
            $item['img'] = "ynet";
                    
            $item['articleID'] = $article -> href;
            $articles[] = $item;
        }
        return $articles;
    }
    
    /**************************************************************
        getHaaretz:
        Crawl Haaretz
	**************************************************************/
    function getHaaretz() {
        //$time_zevel = $article -> parent -> parent -> nextSibling() -> plaintext;
        
        $articles = array();
        
        $url = 'https://www.haaretz.co.il/misc/breaking-news';
        $html = curl($url)["content"];

        $count = 0;
        // Find all article blocks
        foreach($html->find('main article > div > div > div') as $day) {
            if ($count == 40) {
                break;
            }

            $date = trimmer($day -> find('div > time', 0) -> plaintext);
            $date = str_replace("/", "-", $date);
            $date = date("Y-m-d", strtotime($date));
            
            foreach($day->find('li') as $article) {
                

                $text = trimmer($article -> plaintext);
                $text = preg_replace("/\([^)]+\)/", "", $text); // remove parenthesis

                $item['title'] = substr($text, 5);
                $item['hour'] = date('H:i', strtotime(substr($text, 0, 5)));
                $item['date'] = $date;
                $item['time'] = $date." ".$item['hour'];
                $item['source'] = "הארץ";
                $item['body'] = "";
                $item['img'] = "haaretz";
                        
                $item['articleID'] = "";
                $articles[] = $item;
            }
            $count++;
        }
        return $articles;
    }
    
    // https://www.maariv.co.il/breaking-news
    // https://www.mako.co.il/news-news-flash
    // https://13news.co.il/news/news-flash/
    // https://www.haaretz.co.il/misc/breaking-news
    
    
    function findSimilar($array, $input) {
        $result = array("found" => false, "replace" => false);
        
        foreach ($array as $word => $count) {
            if(mb_strlen($word) >= 2 && mb_strlen($input) >= 2) {
                           
                $sim = similar_text($input, $word, $perc);
                if($perc > 85 && (contains($input, $word) || contains($word, $input))) {
                    $result["found"] = $word;
                    if (mb_strlen($word) > mb_strlen($input)) {
                        $result["found"] = $input;
                        $result["replace"] = $word;
                    }
                    return $result;
                }
            }
        }
        
        if (!$result["found"]) {
            return false;
        }
    }
?>