<?php

namespace Oforge\Engine\Modules\Core\Services;

use Oforge\Engine\Modules\Core\Exceptions\EncryptionException;
use Oforge\Engine\Modules\Core\Helper\ArrayHelper;

/**
 * Class EncryptionService
 *
 * @package Oforge\Engine\Modules\Core\Services
 */
class EncryptionService {
    private const DEFAULT_METHOD = 'aes-256-gcm';
    /**
     * @var array $config
     */
    private $config = [];

    /**
     * Generate secret key for encryption.
     *
     * @return string
     */
    public function generateSecretKey() : string {
        return base64_encode(openssl_random_pseudo_bytes(256));
    }

    /**
     * Encrypt string.
     *
     * @param string|null $plainString
     *
     * @return string Returns null if $plainString is null or encrypted string.
     * @throws EncryptionException
     */
    public function encrypt(?string $plainString) : ?string {
        if (is_null($plainString)) {
            return null;
        }
        $config = $this->initConfig();
        $method = $config['method'];
        $key    = $config['key'];
        $iv     = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $encryptedString = openssl_encrypt($plainString, $method, $key, 0, $iv, $tag);
        if ($encryptedString === false) {
            throw new EncryptionException(sprintf("OpenSSL error: %s", openssl_error_string()));
        }

        return base64_encode($encryptedString . '::::' . $iv . '::::' . $tag);
    }

    /**
     * Decrypt string.
     *
     * @param string|null $encryptedString
     *
     * @return string Returns null if $encryptedString is null or decrypted string.
     * @throws EncryptionException
     */
    public function decrypt(?string $encryptedString) : ?string {
        if (is_null($encryptedString)) {
            return null;
        }
        $config = $this->initConfig();
        list($encryptedString, $iv, $tag) = explode('::::', base64_decode($encryptedString), 3);
        $method      = $config['method'];
        $key         = $config['key'];
        $plainString = openssl_decrypt($encryptedString, $method, $key, 0, $iv, $tag);
        if ($plainString === false) {
            throw new EncryptionException(sprintf("OpenSSL error: %s", openssl_error_string()));
        }

        return $plainString;
    }

    /**
     * Get and check encryption config of config file.
     *
     * @return array
     * @throws EncryptionException
     */
    private function initConfig() : array {
        if (empty($this->config)) {
            $this->config = Oforge()->Settings()->get('encryption');
            $method       = ArrayHelper::get($this->config, 'method', self::DEFAULT_METHOD);

            $this->config['method'] = $method;
        }
        if (empty($this->config)) {
            throw new EncryptionException('Missing encryption config!');
        }
        foreach (['key'] as $key) {
            if (!isset($this->config[$key])) {
                throw new EncryptionException("Missing '$key' in encryption config!");
            }
        }
        if (!in_array($this->config['method'], openssl_get_cipher_methods())) {
            throw new EncryptionException('Unsupported encryption method!');
        }

        return $this->config;
    }

}
