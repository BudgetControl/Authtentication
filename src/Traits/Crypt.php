<?php
namespace Budgetcontrol\Authentication\Traits;

use Illuminate\Support\Facades\Crypt as FacadesCrypt;

trait Crypt
{
    private function generateIv($text) {
        return substr(md5($text), 0, 16);
    }
    
    public function encrypt($text) {
        $key = env("APP_KEY");
        $cipher = 'aes-256-cbc';
        $iv = $this->generateIv($key);
        $encrypted = openssl_encrypt($text, $cipher, base64_decode(substr($key, 7)), 0, $iv);
        return $encrypted;
    }
    
    public function decrypt($encrypted) {
        $key = env("APP_KEY");
        $cipher = 'aes-256-cbc';
        $iv = $this->generateIv($key);
        $decrypted = openssl_decrypt($encrypted, $cipher, base64_decode(substr($key, 7)), 0, $iv);
        return $decrypted;
    }
    
}