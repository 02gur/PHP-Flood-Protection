<?php

require_once("dos.php");


// Örnek kullanım
$jsonFile = '/var/www/html/floder/blocked_ips.json';
$requestLimiter = new RequestLimiter($jsonFile);

# Kullanım 1: Mesaj ile engelleme
//$message = json_encode(array("status"=>false,"mesaj"=>"Limit Aşıldı Banlandınız : ".time())); 

# Kullanım 2: Limit aşılır ise yönlendirme
$message = json_encode(array("status"=>false,"mesaj"=>"https://www.google.com.tr")); 


//30 saniyede 20 request atılır ise 90 saniye banla ...
$requestLimiter->processRequest(20, 30, 90,$message);

echo 'Website';


