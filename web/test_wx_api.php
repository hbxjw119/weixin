<?php

include __DIR__ .'/../lib/wx.api.php';

$api = new WeixinApi();
$uuid = $api->_get_uuid();
$api->_get_QRcode();
while(true) {
	if(!$api->wait_for_login()){
		continue;
	} else {
		break;
	}
}
echo 'start login...';
$api->login();
$api->web_init();


