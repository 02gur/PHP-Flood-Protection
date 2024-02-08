<?php
/**
 * PHP FLood Protection
 *
 * Bu dosya PHP FLood Protection'nın bir parçasıdır.
 *
 * @category   PHP
 * @package    floodprotection
 * @author     Özgür Şahin <0zgur>
 * @license    https://raw.githubusercontent.com/02gur/PHP-Flood-Protection/main/LICENSE  APACHE Lisansı
 * @link       https://raw.githubusercontent.com/02gur/PHP-Flood-Protection
 */
class throwExction{
}
class RequestLimiter
{
    private $jsonFile;
    private $blockedIPs;

    public function __construct($jsonFile)
    {
        $this->jsonFile = $jsonFile;
        $this->loadBlockedIPs();
        $this->cleanExpiredEntries();
    }

    private function cleanExpiredEntries()
    {
        $now = time();
        $expirationTime = 10;//4 * 60 * 60; // 4 saat (saniye cinsinden)

        $expiredEntries = array_filter(
            $this->blockedIPs,
            function ($data) use ($now, $expirationTime) {
                return $data['unblock_time'] > 0 && $data['unblock_time'] <= ($now - $expirationTime);
            }
        );

        foreach ($expiredEntries as $ip => $entry) {
            unset($this->blockedIPs[$ip]);
        }

        $this->saveBlockedIPs();
    }

    public function processRequest($limit, $timeout, $blockTime, $message)
    {
        $ip = $this->getClientIP();
        $now = time();

        // IP kontrolü
        if ($this->isIPBlocked($ip)) {
            $json_dec   = json_decode($message)->mesaj;
            if(strstr($json_dec,"https://") || strstr($json_dec,"http://")){
                exit(@header("location: $json_dec"));
            }else{
                exit($message);
            }
        }

        // İstek kontrolü
        $requestCount = $this->getRequestCount($ip, $now - $timeout);

        if ($requestCount >= $limit) {
            $this->blockIP($ip, $now + $blockTime);

            if(strstr($message,"https://") || strstr($message,"http://")){
                exit(@header("location: $message"));
            }else{
                exit($message);
            }
        }

        // İstek kaydı
        $this->recordRequest($ip, $now);
    }

    private function getClientIP()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    private function loadBlockedIPs()
    {
        if (file_exists($this->jsonFile)) {
            $content = file_get_contents($this->jsonFile);
            $this->blockedIPs = json_decode($content, true);
        } else {
            $this->blockedIPs = [];
        }
    }

    private function saveBlockedIPs()
    {
      try{
        $content = json_encode($this->blockedIPs, JSON_PRETTY_PRINT);
        $content  = file_put_contents($this->jsonFile, $content);
        if($content){
        }else{
            $error = json_encode(array("status"=>false,"mesaj"=>"Dosya oluşturulurken bir hata oluştu, klasör izinlerini kontrol ediniz."));
            exit($error);
        }
      }catch(Exception $e){
        exit($e->getMessage());
      }
    }

    private function isIPBlocked($ip)
    {
        return isset($this->blockedIPs[$ip]) && $this->blockedIPs[$ip]['unblock_time'] > time();
    }

    private function blockIP($ip, $unblockTime)
    {
        $this->blockedIPs[$ip] = [
            'unblock_time' => $unblockTime,
            'request_count' => 0,
        ];
        $this->saveBlockedIPs();
    }

    private function getRequestCount($ip, $since)
    {
        $count = 0;

        if (!isset($this->blockedIPs[$ip])) {
            return $count;
        }

        if ($this->blockedIPs[$ip]['unblock_time'] > 0 && $this->blockedIPs[$ip]['unblock_time'] <= time()) {
            unset($this->blockedIPs[$ip]);
            $this->saveBlockedIPs();
            return $count;
        }

        $count = $this->blockedIPs[$ip]['request_count'];

        return $count;
    }

    private function recordRequest($ip, $time)
    {
        if (!isset($this->blockedIPs[$ip])) {
            $this->blockedIPs[$ip] = [
                'unblock_time' => 0,
                'request_count' => 0,
            ];
        }

        $this->blockedIPs[$ip]['request_count']++;
        $this->saveBlockedIPs();
    }
}


?>
