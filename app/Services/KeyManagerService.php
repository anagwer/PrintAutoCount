<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class KeyManagerService
{
    public function generateKeyPair(): array
    {
        // $openssl_cnf_path = 'D:/YOSHI/WORK/BACKEND/api-hrms/openssl.cnf';
        $openssl_cnf_path = 'C:\xampp\php\extras\ssl\openssl.cnf';

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $openssl_cnf_path,
        ]);

        if ($keyPair === false) {
            $error = '';
            while ($msg = openssl_error_string()) {
                $error .= $msg . "\n";
            }
            throw new \Exception("Gagal membuat keypair: " . $error);
        }

        if (!openssl_pkey_export($keyPair, $privateKey, null, ['config' => $openssl_cnf_path])) {
            throw new \Exception("Gagal mengekspor private key.");
        }

        $details = openssl_pkey_get_details($keyPair);
        if ($details === false) {
            throw new \Exception("Gagal mengambil detail public key.");
        }

        return [
            'public_key' => $details['key'],
            'private_key' => $privateKey,
        ];
    }

    public function encryptPrivateKey(string $privateKey, string $key, string $iv): string
    {
        return openssl_encrypt($privateKey, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }


public function decryptPrivateKey(string $encryptedPrivateKey, string $key, string $iv): string
{
    return openssl_decrypt($encryptedPrivateKey, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

    public function generateSalt(): string
    {
        return random_bytes(16); // 128-bit salt
    }

    // app/Services/KeyManagerService.php

public function generateMasterKey(): string
{
    return random_bytes(32); // 256-bit master key
}

public function derivePinKey(string $pin, string $salt): string
{
    return hash_pbkdf2('sha256', $pin, $salt, 10000, 32, true);
}

public function generateRecoveryKey(): string
{
    return strtoupper(bin2hex(random_bytes(16))); // Contoh: A1B2C3D4E5F6G7H8I9J0KLMNOPQRSTUVWXYZ
}

// public function verifyPin(string $pin, string $storedPinHash): bool
// {
//     $inputPinKey = $this->derivePinKey($pin);
//     return hash_equals($storedPinHash, $inputPinKey);
// }


    public function decryptMasterKey(string $encryptedMasterKey, string $pinKey, string $iv): string
    {
        // Convert iv from hex to binary
        $iv = hex2bin($iv);

        // Decrypt the master key using AES-256-CBC
        return openssl_decrypt(
            base64_decode($encryptedMasterKey),  // Dekripsi dengan base64_decoded master key
            'aes-256-cbc',
            $pinKey,  // Kunci yang dihasilkan dari PIN
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    public function hashPin(string $pin, string $salt): string
    {
        return hash_hmac('sha256', $pin, $salt);
    }


    public function decryptSessionKey(string $encryptedSessionKeyBase64, string $privateKey): string
    {
        $encryptedSessionKey = base64_decode($encryptedSessionKeyBase64);
        $sessionKey = null;

        if (!openssl_private_decrypt($encryptedSessionKey, $sessionKey, $privateKey)) {
            throw new \Exception("Gagal mendekripsi session key.");
        }

        return $sessionKey;
    }


}
