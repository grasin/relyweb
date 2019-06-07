<?php 
/*
* 	Send mail with custom templates
* 	$template : E-mail template.
* 	$array : Variables for email template.
* 	$subject : E-mail Subject.
* 	$to : E-mail receiver.
*/
function mailing($template,$array,$subject,$to) {
	$cfg = \App\Config::first();
	$array['url'] = url('');
	$array['name'] = $cfg->name;
	$array['address'] = nl2br($cfg->address);
	$array['phone'] = $cfg->phone;
	// Get the template from the database
	$message = \App\Template::where(['code' => $template])->first()->template;
    foreach ($array as $ind => $val) {
        $message = str_replace("{{$ind}}",$val,$message);
    }   
    $message = preg_replace('/\{\{(.*?)\}\}/is','',$message);
	Mail::send(array(), array(), function ($message_object) use ($cfg,$to,$subject,$message) {
	  $message_object->from($cfg->email, $cfg->name)->to($to)->subject($subject)->setBody($message, 'text/html');
	});
    return true;
}
/*
* 	Generate url
* 	$str : The title.
* 	$id : Item ID.
*/
function path($str,$id = false) {
	$path = preg_replace("/[^a-zA-Z0-9\_|+ -]/", '', $str);
	$path = strtolower(trim($path, '-'));
	$path = preg_replace("/[\_|+ -]+/", '-', $path);
	if($id != false){
		$path = $id.'-'.$path;
	}
	return $path;
}
/*
* 	Get image by order
* 	$string : The string.
* 	$order : Image order.
*/
function image_order($string, $order = 0){
	return explode(',',$string)[$order];
}
/*
* 	Smart string cut
* 	$text : The string.
* 	$length : Length of the output.
* 	$end : String to be appended.
*/
function string_cut($text, $length = 100,$end = ''){
	mb_strlen($text);
	if (mb_strlen($text) > $length){
		$text = mb_substr($text, 0, $length);
		return $text.$end;
	} else {
		return $text;
	}
}
/*
*   Get user operation system
*/
function getOS() { 
	$user_agent = request()->server('HTTP_USER_AGENT');
    $os_platform    =   "Unknown OS Platform";
    $os_array       =   array(
                            '/windows nt 10/i'     =>  'Windows 10',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win98/i'              =>  'Windows 98',
                            '/win95/i'              =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/linux/i'              =>  'Linux',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile'
                        );
    foreach ($os_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }
    }   
    return $os_platform;
}
/*
*   Get user browser
*/
function getBrowser() {
	$user_agent = request()->server('HTTP_USER_AGENT');
	$browser		=   "Unknown Browser";
	$browser_array  =   array(
							'/msie/i'	   =>  'Internet Explorer',
							'/firefox/i'	=>  'Firefox',
							'/safari/i'	 =>  'Safari',
							'/chrome/i'	 =>  'Chrome',
							'/edge/i'	   =>  'Edge',
							'/opera/i'	  =>  'Opera',
							'/netscape/i'   =>  'Netscape',
							'/maxthon/i'	=>  'Maxthon',
							'/konqueror/i'  =>  'Konqueror',
							'/mobile/i'	 =>  'Handheld Browser'
						);
	foreach ($browser_array as $regex => $value) { 
		if (preg_match($regex, $user_agent)) {
			$browser	=   $value;
		}
	}
	return $browser;
}
/*
*   Get user country
*/
function getCountry() {
	if (empty($_COOKIE['country'])) {
		$ch = curl_init('http://api.miniapps.design/?ip='.request()->ip().'&url='.env('APP_URL'));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$json = '';
		if (($json = curl_exec($ch)) !== false) {
			if (!empty(json_decode($json,true)['country']['code'])){
				$country = json_decode($json,true)['country']['code'];
			} else {
				$country = 'US';
			}
			setcookie("country",$country,time()+2592000);
		} else {
			$country = 'US';
		}
		curl_close($ch);
	} else {
		$country = $_COOKIE['country'];
	}
	return $country;
}
/*
*   Get referrer
*/
function getReferrer() {
	$user_referrer = request()->server('HTTP_REFERER') != null ? request()->server('HTTP_REFERER'): '';
	$referrer = implode(array_slice(explode('/', preg_replace('/https?:\/\/(www\.)?/', '', $user_referrer)), 0, 1));
	return $referrer;
}
/*
* 	Time difference
* 	$old : The compared time.
* 	$level : Time level.
*/
function timegap($old,$level = 0) {
	$time = time(); 
	$dif = $time-$old;
	$names = array('second','minute','hour','day','week','month','year','decade');
	$length = array(1,60,3600,86400,604800,2630880,31570560,315705600);
	for($v = sizeof($length)-1; ($v >= 0)&&(($no = $dif/$length[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $time-($dif%$length[$v]);
	$no = floor($no); if($no <> 1) $names[$v] .='s'; $gap=sprintf("%d %s ",$no,$names[$v]);
	if(($level > 0)&&($v >= 1)&&(($time-$_tm) > 0)) $gap .= ' and '.timegap($_tm,--$level);
	return $gap;
}
/*
* 	Percentage change
* 	$old : The old figure.
* 	$new : The new figure.
*/
function percentage($old, $new) {
	if (($old != 0) && ($new != 0)) {
		$percentChange = (1 - $old / $new) * 100;
	}
	else {
		$percentChange = 0;
	}
	return number_format($percentChange);
}
/*
* 	Currency
* 	$dollar_price : price.
*/
function currency($dollar_price) {
	if (!isset($_COOKIE['currency'])) {
		// Use default currency
		$currency = \App\Currency::orderby("default","desc")->first()->code;
	} else {
		// Use user currency
		$currency = $_COOKIE['currency'];
	}
	if (\App\Currency::where("code",$currency)->count() > 0) {
		$rate = \App\Currency::where("code",$currency)->first()->rate;
		$price = (!empty($dollar_price) ? $dollar_price*$rate : '');
		return $price.$currency;
	} else {
		$price = (!empty($dollar_price) ? $dollar_price : '');
		return $price.'$';
	}
}
/*
* 	Returns country name .
* 	$iso : country iso code.
*/
function country($iso) {
	$country = \App\Country::where(["iso" => $iso])->first()->nicename;
	return $country;
}
/*
* 	Order status
* 	$status_id : Status id.
*	$return_color : Boolean.
*/
function status($status_id,$return_color = false) {
	$status_array = config('app.statuses');
	if ($return_color){
		return $status_array[$status_id]['color'];
	} else {
		return $status_array[$status_id]['title'];
	}
}
/*
* 	PDO Escape
* 	$word : The word to be escaped.
*/
function escape($word){
	return substr(DB::connection()->getPdo()->quote($word),1,-1);
}
/*
* 	Translation
* 	$word : The word to translate.
*/
function translate($word){
	$cfg = \App\Config::first();
	// Set frontend language
	if (!isset($_COOKIE['lang'])) {
		// Use default language
		$lang = $cfg->lang;
	} else {
		// Use user language
		$lang = $_COOKIE['lang'];
	}
	if ($cfg->translations == 0)
	{
		// Desactivate translation
		return $word;
	}
	else 
	{
		// Fetching for translation
		if (\App\Translation::where(['word' => $word,"lang" => $lang])->count() > 0)
		{
			// Return translation
			$translation = \App\Translation::where(['word' => $word,"lang" => $lang])->select("translation")->first();
			return $translation->translation;
		}
		else
		{
			// Add translation to database and return word
			\App\Translation::insert(["lang" => $lang,"word" => $word,"translation" => $word]);
			return $word;
		}
	}
}
/*
* 	Customer data
* 	$info : The information to be retrieved.
*/
function customer($info){
	if (session('customer') == '') {
		return '';
	}
	$customer = \App\Customer::where(['sid' => session('customer')])->first();
	if (isset($customer->$info)) {
		return $customer->$info;
	} else {
		return '';
	}
}
/*
* 	Flash messages
* 	$status : Flash message status , Ex : success.
* 	$message : Flash message text.
*/
function notices($status = 'success',$message = false){
	if (empty(session('notices'))){
		session(['notices' => array()]);
	}
	if ($message == false) {
		$notices_array = session('notices');
		session()->forget('notices');
		$notices = '';
		foreach ($notices_array as $notice){
			$notices .= '<div class="alert mini alert-'.$notice['status'].'"> '.$notice['message'].'</div>';
		}
		return $notices;
	}
	session()->push('notices', ['status' => $status,'message' => $message]);
}