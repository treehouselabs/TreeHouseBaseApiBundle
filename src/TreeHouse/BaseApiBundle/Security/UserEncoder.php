<?php

namespace TreeHouse\BaseApiBundle\Security;

use Symfony\Component\Security\Core\Util\StringUtils;

class UserEncoder
{
    const HASH_DELIMITER = ':';

    /**
     * @var string
     */
    protected $secret;

    /**
     * @param string $secret
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $hash
     *
     * @return array
     */
    public function decodeHash($hash)
    {
        return explode(self::HASH_DELIMITER, base64_decode($hash));
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    public function encodeHash(array $parts)
    {
        return base64_encode(implode(self::HASH_DELIMITER, $parts));
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    public function generateHash($username, $password)
    {
        return sha1($username . $password . $this->secret);
    }

    /**
     * @see StringUtils::equals
     */
    public function compareHashes($hash1, $hash2)
    {
        return StringUtils::equals($hash1, $hash2);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    public function generateTokenValue($username, $password)
    {
        return $this->encodeHash([
            base64_encode($username),
            $this->generateHash($username, $password),
        ]);
    }
}
