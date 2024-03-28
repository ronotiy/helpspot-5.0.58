<?php

namespace HS\Encryption;

interface EncryptionInterface
{
    /**
     * Set encryption key.
     * @param $key
     * @return EncryptionInterface $this
     */
    public function setKey($key);

    /**
     * Encrypt plain text to encrypted text.
     * @param $plainText
     * @return string
     */
    public function encrypt($plainText);

    /**
     * Decrypt encrypted text to plain text.
     * @param $encryptedText
     * @return string
     */
    public function decrypt($encryptedText);
}
