<?php
	
	function login($login, $passs, $cookie){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://admin.stforex.com/login");
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		if (curl_errno($ch)) die(curl_error($ch));
		$dom = new DomDocument();
		$dom->loadHTML($response);
		$tokens = $dom->getElementsByTagName("meta");
		for ($i = 0; $i < $tokens->length; $i++)
		{
		    $meta = $tokens->item($i);
		    if($meta->getAttribute('name') == 'csrf-token')
		    $token = $meta->getAttribute('content');
		}
		$postinfo = "LoginForm[email]=".$login."&LoginForm[password]=".$passs."&_csrf-backend=".$token."";

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$html = curl_exec($ch);

		curl_close($ch);
	}

	function Read1($post, $cookie){
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, "https://admin.stforex.com/account/trade-operations?login=".$post);
	   curl_setopt($ch, CURLOPT_REFERER, 'https://admin.stforex.com/login');
	   curl_setopt($ch, CURLOPT_POST, 0);
	   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
	   $result = curl_exec($ch);
	   curl_close($ch);
	   return $result;
	}

	function diff(DateTime $datetime1, DateTime $datetime2 = null) {
	    if (!isset($datetime2)) {
	        $datetime2 = new DateTime('now');
	    }
	    $interval = $datetime1->diff($datetime2, false);
	    $days = $interval->days;
	    $interval->s = $datetime2->getTimestamp() - $datetime1->getTimestamp();
	    $interval->i = floor($interval->s / 60);
	    $interval->h = floor($interval->s / (60 * 60));
	    $interval->d = $days;
	    $interval->m = floor($days / $datetime1->format('t'));
	    return $interval;
  	}

?>