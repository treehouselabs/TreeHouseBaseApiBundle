<?php

namespace TreeHouse\BaseApiBundle\Tests\Security;

use TreeHouse\BaseApiBundle\Security\UserEncoder;

class UserEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserEncoder
     */
    protected $encoder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->encoder = new UserEncoder('mys4cr3t');
    }

    public function testEncodeDecodeHash()
    {
        // encode hash
        $hash = ['foo', 'bar', 'baz'];
        $encodedHash = $this->encoder->encodeHash($hash);
        $this->assertInternalType('string', $encodedHash);

        // decoding the hash should match the original
        $decodedHash = $this->encoder->decodeHash($encodedHash);
        $this->assertInternalType('array', $decodedHash);
        $this->assertEquals($hash, $decodedHash);

        // decoding something else should not match the original
        $decodedHash = $this->encoder->decodeHash('foo');
        $this->assertNotEquals($hash, $decodedHash);
    }

    public function testGenerateHash()
    {
        $hash = $this->encoder->generateHash('foouser', 'barpass');
        $this->assertInternalType('string', $hash);
    }

    public function testCompareHashes()
    {
        $encoder = new UserEncoder('mys4cr3t');

        $this->assertTrue($encoder->compareHashes('supercryptohash', 'supercryptohash'));
        $this->assertFalse($encoder->compareHashes('supercryptohash', 'foo'));
    }

    public function testGenerateTokenValue()
    {
        $token = $this->encoder->generateTokenValue('foouser', 'barpass');
        $this->assertInternalType('string', $token);
    }
}
