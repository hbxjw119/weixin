<?php
include __DIR__ . '/../conf/config.php';

class WeixinApi
{
	public $uuid = '';
	public $skey = '';
	public $sid = '';
	public $uin = '';
	public $pass_ticket = '';
	public $deviceId = 'e1615250492';
	public $cookie = 'cookie.cookie';
	public function _get_uuid() 
	{
		$url = 'https://login.weixin.qq.com/jslogin';
		$params = [
			'appid' => APPID,
			//'redirect_url' => '',
			'fun' => 'new',
			'lang' => 'zh_CN',
			'_' => time()
		];
		$res = $this->_get($url,$params);

		$regx = '/window.QRLogin.code = (\d+); window.QRLogin.uuid = "(\S+?)"/';
		if(preg_match($regx,$res,$pm)) {
			$code = $pm[1];
			$uuid = $pm[2];
			$this->uuid = $uuid;
			return $uuid;
		} else {
			return false;
		}
	}

	public function _get_QRcode()
	{
		$url = 'https://login.weixin.qq.com/qrcode/'.$this->uuid;
		$params = [
			't' => 'webwx',
			'_' => time()
		];
		//$res = $this->_get($url,$params);
		//return $res;
		print_r($url);
	}

	public function wait_for_login()
	{
		sleep(2);
		$url = 'https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login';
		$params = [
			//'loginicon' => 'true',
			'uuid' => $this->uuid,
			'tip' => '1',
			//'r' => '',
			'_' => time()
		];
		$res = $this->_get($url,$params);
		preg_match('/window.code=(\d+);/',$res,$pm);
		$code = $pm[1];
		print_r($code);
		if($code == '200') {
			preg_match('/window.redirect_uri="(\S+?)";/',$res,$pm);
			$redirect_uri = $pm[1] . '&fun=new';
			$this->redirect_uri = $redirect_uri;
			return true;
		} else {
			print_r($code);
			print_r('\n');
			return false;
		}

	}

	public function login()
	{
		$res = $this->_get($this->redirect_uri);
		$wx_ret = (array)simplexml_load_string($res, 'SimpleXMLElement',LIBXML_NOCDATA);
		print_r($wx_ret);
		$this->skey = $wx_ret['skey'];
		$this->sid = $wx_ret['wxsid'];
		$this->uin = $wx_ret['wxuin'];
		$this->pass_ticket = $wx_ret['pass_ticket'];
		file_put_contents('key',serialize([
			'skey' => $this->skey,
			'wxsid' => $this->sid,
			'wxuin' => $this->uin,
			'pass_ticket' => $this->pass_ticket,
			'deviceId' => $this->deviceId,
		]));
	}


	public function web_init() 
	{
		$url = sprintf('https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket=%s&skey=%s&r=%s',$this->pass_ticket,$this->skey,time());
		$params = [
			'BaseRequest' => [
				'Uin' => intval($this->uin),
				'Sid' => $this->sid,
				'Skey' => $this->skey,
				'DeviceId' => $this->deviceId
			]
		];

		$ret = $this->_post($url,$params);
		print_r($ret);
	}
	private function _get($url,$params=[])
	{
		$curl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($curl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		$header = [
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.109 Safari/537.36',
			'Referer: https://wx.qq.com/'
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		if(!empty($params)) {
			$url .= '?'.http_build_query($params);
		}
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($curl, CURLOPT_TIMEOUT, 36);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie);
		$res = curl_exec($curl);
		$stat = curl_getinfo($curl);
		curl_close($curl);
		return $res;
	}

	private function _post($url,$params) 
	{
		$curl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		$params = json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		$header = [
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.109 Safari/537.36',
			'Referer: https://wx.qq.com/',
			'Content-Type: application/json; charset=UTF-8'
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($curl, CURLOPT_POST,true);
		curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie);
		$res = curl_exec($curl);
		curl_close($curl);
		return $res;
	}

}
