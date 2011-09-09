<?php
require './fun.php';

//基本設定
$config = array(
  'appId'  => '409',
  'secret' => '27ca737d509bebfbd926f77f11cca800',
);
$fun = new FUN($config);

//取得access_token
$session = $fun->getSession();
if (!$session)
	echo "使用者未登入FUN名片";
?>
