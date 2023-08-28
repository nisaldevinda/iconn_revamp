<?php

namespace App\Traits;

trait Crypter
{

    /**
     * Encrypts the data.
     *
     * @param string $data A string of data to encrypt.
     *
     * @return string (binary) The encrypted data
     */
    public function encrypt($data)
    {
        $encryptMethod = config('app.crypter_method');
        $chiperIvLength = openssl_cipher_iv_length($encryptMethod);
        $iv = '';
        if ($chiperIvLength > 0) {
            $iv = openssl_random_pseudo_bytes($chiperIvLength);
        }
        $padValue = 16 - (strlen($data) % 16);

        return openssl_encrypt(
            str_pad($data, intval(16 * (floor(strlen($data) / 16) + 1)), chr($padValue)),
            $encryptMethod,
            $this->generateKey(config('app.crypter_key')),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
    }

    /**
     * Decrypts the data.
     *
     * @param string $data A (binary) string of encrypted data
     *
     * @return string Decrypted data
     */
    public function decrypt($data)
    {
        $data = openssl_decrypt(
            $data,
            config('app.crypter_method'),
            $this->generateKey(config('app.crypter_key')),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        return rtrim($data, "\x00..\x10");
    }

    /**
     * Create and set the key used for encryption.
     *
     * @param string $seed The seed used to create the key.
     *
     * @return string (binary) the key to use in the encryption process.
     */
    private function generateKey($seed)
    {
        $key = str_repeat(chr(0), 16);
        for ($i = 0, $len = strlen($seed); $i < $len; $i++) {
            $key[$i % 16] = $key[$i % 16] ^ $seed[$i];
        }
        return $key;
    }
}
