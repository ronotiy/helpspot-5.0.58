<?php

namespace HS\Encryption;

class Rc4Encryption implements EncryptionInterface
{
    /**
     * PEAR Crypt_RC4.
     * @var \Crypt_RC4
     */
    private $crypt_RC4;

    public function __construct(\Crypt_RC4 $crypt_RC4)
    {
        $this->crypt_RC4 = $crypt_RC4;
    }

    /**
     * Set encryption key.
     * @param $key
     * @return Rc4Encryption $this
     */
    public function setKey($key)
    {
        $this->crypt_RC4->setKey($key);

        return $this;
    }

    /**
     * Encrypt plain text to encrypted text.
     * @param $plainText
     * @return string
     */
    public function encrypt($plainText)
    {
        $passByReferencePlainText = $plainText;
        $this->crypt_RC4->crypt($passByReferencePlainText);

        return $passByReferencePlainText;
    }

    /**
     * Decrypt encrypted text to plain text.
     * @param $encryptedText
     * @return string
     */
    public function decrypt($encryptedText)
    {
        $passByReferenceEncryptedText = $encryptedText;
        $this->crypt_RC4->decrypt($passByReferenceEncryptedText);

        return $passByReferenceEncryptedText;
    }
}
