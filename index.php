<?php
/*作者信息:
	作者：HeeeCaI
	如需转载本文&代码，记得署名：HeeeCaI
	我是HeeeCaI希望你每天开心(^_^)
*/


/*配置信息：*/
$login_user="账号";
$login_password="密码";
//没有设置问题无需修改此项
$login_question="0";
$login_answer=link_urldecode("");
/*关注pushplus公众号获取自己的token，完成推送*/
$pushplus_token="pushplus上申请的token";


/*函数列表：*/

	//获取时间戳
	function getSecond() {
		list($t1, $t2) = explode(' ', microtime());
		return (float)sprintf('%.0f',(floatval($t1)+floatval($t2)));
	}
	
	//登录
	function login(){
		/*变量的声明*/
		global $login_user,$login_password,$login_question,$login_answer,$pushplus_token;
		$curl  =  curl_init ();
		curl_setopt($curl,CURLOPT_URL,  "http://bbs.eyuyan.com/login.php" ) ;
		curl_setopt($curl,CURLOPT_HEADER ,1);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER ,  1 ) ;
		$res=getSecond();
		$cookietest="e5da7_ol_offset=3880; e5da7_ck_info=%2F%09; e5da7_ipstate=".$res."; e5da7_lastvisit=0%09".$res."%09%2Findex.php; e5da7_c_stamp=".$res."; e5da7_lastpos=index";
		$rescooke="";
		curl_setopt($curl,CURLOPT_COOKIE ,  $cookietest) ;
		curl_setopt($curl,CURLOPT_REFERER,"http://bbs.eyuyan.com/");
		curl_setopt($curl,CURLOPT_POSTFIELDS ,"forward=&jumpurl=http%3A%2F%2Fbbs.eyuyan.com%2Findex.php&step=2&lgt=0&pwuser=".$login_user."&pwpwd=".$login_password."&question=".$login_question."&customquest=&answer=".$login_answer."&hideid=0&cktime=0&submit=");
		$data=curl_exec($curl);
		curl_close($curl);
		if($data==false){
            echo "get_content_null";exit();
        }
		//echo $data;
		$preg_cookie = '/Set-Cookie: (.*?);/m';
		if(preg_match_all($preg_cookie,$data,$rescooke)){
			$rescooke = implode(';', $rescooke['1']);
			echo "get_cookie:ok!\n";
			return $rescooke;
		}else {
			echo "err-no cookie;";
			return "err";
		}
	}
	
	//获取认证值
	function get_index($cookie){
		$curl = curl_init("http://bbs.eyuyan.com/index.php") ; 
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true) ; 
		$header = array('Connection: keep-alive','Host: bbs.eyuyan.com','Content-Type: application/json','User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36','Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9');
		curl_setopt($curl,CURLOPT_COOKIE ,  $cookie) ;
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true) ; 
		$output = curl_exec($curl);
		curl_close($curl);
		$output = mb_convert_encoding($output, 'utf-8','GBK');
		$verifyhash=getSubstr($output,"var verifyhash = '","';");
		if($verifyhash!=""){
			echo "get_verifyhash:".$verifyhash."\n";
			return $verifyhash;
		}else {
			echo htmlentities($output);
			return "err";
		}
	}
	
	//签到打卡
	function get_money($verifyhash,$cookie){
		$res=getSecond();
		$curl  =  curl_init ();
		curl_setopt($curl,CURLOPT_URL,  "http://bbs.eyuyan.com/jobcenter.php?action=punch&verify=".$verifyhash."&nowtime=".$res."&verify=".$verifyhash ) ;
		curl_setopt($curl,CURLOPT_HEADER ,1);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER ,  1 ) ;
		curl_setopt($curl,CURLOPT_COOKIE ,  $cookie) ;
		curl_setopt($curl,CURLOPT_REFERER,"http://bbs.eyuyan.com/");
		curl_setopt($curl,CURLOPT_POSTFIELDS ,"step=2");
		$data=curl_exec($curl);
		curl_close($curl);
		$data=mb_convert_encoding($data, 'utf-8','GBK');
		$resmessage=getSubstr($data,"message\":'","',");
		echo "res_message:".$resmessage."\n";
		return $resmessage;
	}
	
	//pushplus推送
	function pushplus($content){
		global $pushplus_token;
		$curl = curl_init();
		$url="http://pushplus.hxtrip.com/send?token=".$pushplus_token."&title=论坛每日打卡&content=".$content."&template=html";
		curl_setopt($curl,CURLOPT_URL,link_urldecode($url));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true) ; 
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true) ;  
		$output = curl_exec($curl);
		curl_close($curl);
		$data=getSubstr($output,"data\":\"","\",");
		if($data!=""){
			echo "PUSHPLUS:".$data."\n";
		}else 
			echo htmlentities($output);
	}
	
	//url编码链接的中文
	function link_urldecode($url) {
		$uri = '';
		$cs = unpack('C*', $url);
		$len = count($cs);
		for ($i=1; $i<=$len; $i++) {
		$uri .= $cs[$i] > 127 ? '%'.strtoupper(dechex($cs[$i])) : $url{$i-1};
		}
		return $uri;
	}
	
	//取文本中间
	function getSubstr($str, $leftStr, $rightStr)
	{
		$left = strpos($str, $leftStr);
		$right = strpos($str, $rightStr,$left);
		if($left < 0 or $right < $left) return '';
		return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
	}
	
	//主函数
	function main_handler($event, $context) {
		$rcookis=login();	
		//获取cookies
		$verifyhash=get_index($rcookis);	
		//获取认证值
		$content=get_money($verifyhash,$rcookis);	
		//获取打卡情况
		pushplus($content);		
		//推送微信信息
	}

?>