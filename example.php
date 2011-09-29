<?php 
//1.include php sdk
require 'src/fun.php';

//2.基本設定
$config = array(
	'appId'  => '407',                              //your app id
	'secret' => '34f7b4bbd93d6de19d0080a2f477bf87'  //you app secret
	//'redirect_uri' => 'YOUR_URL' 			//implement if your are using for website, and make sure its value is equal the value while setting app
);

//3.實體化
$fun = new FUN($config);

//4.取得並夾帶access token
$session = $fun->getSession();      
if(!$session)
	die ("使用者未登入FUN名片");


echo "<h4>取得好友</h4>";
try {
	//5.調用api(取得好友)
	$friends = $fun->Api('/v1/me/friends/app/','GET',array("start"=>0,"count"=>10));
	print_r($friends);
} catch (ApiException $e) {
	echo "錯誤代碼：".$e->getCode() . "<br/>";
	echo "說明：".$e->getMessage();
	exit();
}


