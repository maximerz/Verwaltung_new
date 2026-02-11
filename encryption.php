<?php
/**
 * DSGVO-konforme Datenverschlüsselung
 * Verwendet AES-256-GCM für sichere Verschlüsselung personenbezogener Daten
 */
class DataEncryption {
    private $key;
    private $cipher = 'aes-256-gcm';
    
    public function __construct() {
        // Verschlüsselungsschlüssel aus Umgebungsvariable oder generieren
        $keyFile = __DIR__ . '/.encryption_key';
        
        if (file_exists($keyFile)) {
            $this->key = file_get_contents($keyFile);
        } else {
            // Neuen Schlüssel generieren
            $this->key = random_bytes(32);
            file_put_contents($keyFile, $this->key);
            chmod($keyFile, 0600); // Nur Owner kann lesen/schreiben
        }
    }
    
    /**
     * Verschlüsselt personenbezogene Daten
     */
    public function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        // IV, Tag und verschlüsselte Daten kombinieren
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Entschlüsselt personenbezogene Daten
     */
    public function decrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        $decoded = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, 16);
        $encrypted = substr($decoded, $ivLength + 16);
        
        return openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
    
    /**
     * Hasht Daten für Suche (DSGVO-konform)
     */
    public function hash($data) {
        return hash_hmac('sha256', $data, $this->key);
    }
}

// Globale Instanz
$encryption = new DataEncryption();
?>