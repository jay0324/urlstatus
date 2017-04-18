<?php
//==================================================
//網站檢查程式
//Date: 20170418
//Program: Jay Hsu
//==================================================
ignore_user_abort(true); //允許背景處理機制
set_time_limit(0);	//不限制處理時間

//param===========================================================================================
$action = (isset($_GET["action"]) && !empty($_GET["action"]))?$_GET["action"]:"";
$check = (isset($_REQUEST["check"]) && !empty($_REQUEST["check"]))?trim(urldecode($_REQUEST["check"])):"";
$page = (isset($_REQUEST["page"]) && !empty($_REQUEST["page"]))?trim(urldecode($_REQUEST["page"])):"";
$url = (strstr($page,'http://') || strstr($page,'https://'))?$page:'http://'.$page;
$siteDNS = (substr($url,-1) != "/")?$url."/":$url;
$insiteLink = array($url);
$matchNote = array();

//function===========================================================================================
//using file_get_contents get front end html content
function fnHGet($url) {
	$status = get_headers($url);
	//var_dump($status);
	if (!$status){
		return false;
	}else{
		return $status[0];
	}
	
}

//curl
function getstatus($url) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_HEADER, true);
    curl_setopt($c, CURLOPT_NOBODY, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($c, CURLOPT_URL, $url);
    curl_exec($c);
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return $status;
}

//ssl https
function my_get_headers($url) {
       $url_info=parse_url($url);
       if (isset($url_info['scheme']) && $url_info['scheme'] == 'https') {
           $port = 443;
           @$fp=fsockopen('ssl://'.$url_info['host'], $port, $errno, $errstr, 10);
       } else {
           $port = isset($url_info['port']) ? $url_info['port'] : 80;
           @$fp=fsockopen($url_info['host'], $port, $errno, $errstr, 10);
       }

       //var_dump($url_info);

       if($fp) {
           stream_set_timeout($fp, 10);
           $head = "HEAD ".@$url_info['path']."?".@$url_info['query'];
           $head .= " HTTP/1.0\r\nHost: ".@$url_info['host']."\r\n\r\n";
           fputs($fp, $head);
           while(!feof($fp)) {
               if($header=trim(fgets($fp, 1024))) {
                       $sc_pos = strpos( $header, ':' );
                       if( $sc_pos === false ) {
                           $headers['status'] = $header;
                       } else {
                           $label = substr( $header, 0, $sc_pos );
                           $value = substr( $header, $sc_pos+1 );
                           $headers[strtolower($label)] = trim($value);
                       }
               }
           }
           return $headers['status'];
       }
       else {
           return false;
       }
   }

//action===========================================================================================
if ($action == "check"){

	if (isset($page) && !empty($page)) {

		$check = my_get_headers($url); //using curl to get page content

		if (!$check) {
			$checkEcho = "[FAIL]連線失敗";
			$class = 'not-ok';
		}else{
			if (preg_match("/200/i", $check)){
				$checkEcho = "[".$check."]網址運作中";
				$class = 'ok';
      }else if (preg_match("/404/i", $check)){
        $checkEcho = "[".$check."]網址停止運作中";
        $class = 'not-ok';
      }else {
        $redirArry = array('300', '301', '302');
        $find = false;
        foreach ($redirArry as $find) {
          if (preg_match("/\b$find\b/", $stringToCheck)) {
            $find = false;
            break;
          }
        }
        if ($find){
          $checkEcho = "[".$check."]轉址運作中";
          $class = 'ok-redirect';
        }else{
          $checkEcho = "[".$check."]網址有問題";
          $class = 'not-ok';
        }
			}
		}

    $return = array(
      'class'=>$class,
      'status'=>$checkEcho,
      'time'=>date("Y-m-d H:i:s"),
      'url'=>$url
    );

    echo json_encode($return);
	}
}

?>