<?php

namespace TreeHouse\BaseApiBundle\Features\Context;

use Behat\Gherkin\Node\PyStringNode;
use PHPUnit_Framework_Assert as Assert;

class ApiContext extends BaseFeatureContext
{
    /**
     * @var string
     */
    protected static $authToken;

    /**
     * @var string
     */
    protected static $userToken;

    /**
     * @When I get/GET to :path
     */
    public function iGetTo($path)
    {
        $this->request('GET', $path);
    }

    /**
     * @When I head/HEAD to :path
     */
    public function iHeadTo($path)
    {
        $this->request('HEAD', $path);
    }

    /**
     * @When I post/POST to :path with:
     */
    public function iPostToWith($path, PyStringNode $string)
    {
        $this->request('POST', $path, $string);
    }

    /**
     * @When I put/PUT to :path with:
     */
    public function iPutToWith($path, PyStringNode $string)
    {
        $this->request('PUT', $path, $string);
    }

    /**
     * @When I delete/DELETE to :path
     */
    public function iDeleteTo($path)
    {
        $this->request('DELETE', $path);
    }

    /**
     * @Then the response should not contain :content
     */
    public function theResponseShouldNotContain($content)
    {
        Assert::assertNotContains($content, $this->getResponseContent());
    }

    /**
     * @Then the response status code should be :code
     */
    public function theResponseStatusCodeShouldBe($code)
    {
        Assert::assertEquals($code, static::$response->getStatusCode());
    }

    /**
     * @Then the response header :header should contain :value
     * @Then the response header :header contains :value
     */
    public function theResponseHeaderContains($header, $value)
    {
        if (false === static::$response->headers->has($header)) {
            throw new \Exception(
                sprintf('Request does not contain %d header', $header)
            );
        }

        Assert::assertContains($value, (string) static::$response->headers->get($header));
    }

    /**
     * @Then the response should contain :arg1
     * @Then the response contains :arg1
     */
    public function theResponseShouldContain($arg1)
    {
        Assert::assertContains($arg1, $this->getResponseContent());
    }

    /**
     * @Then the response should be xml/XML
     * @Then the response is xml/XML
     */
    public function theResponseIsXml()
    {
        $this->theResponseHeaderContains('Content-type', 'application/xml');

        if (false === simplexml_load_string($this->getResponseContent())) {
            throw new \Exception(
                sprintf(
                    'The response is not valid XML. This was the body: "%s"',
                    $this->getResponseContent()
                )
            );
        }
    }

    /**
     * @Then the response should be json/JSON
     * @Then the response is json/JSON
     */
    public function theResponseIsJson()
    {
        Assert::assertThat(
            static::$response->headers->get('Content-Type'),
            Assert::logicalOr(
                Assert::equalTo('application/json'),
                Assert::equalTo('text/javascript')
            )
        );

        Assert::assertJson($this->getResponseContent());
    }

    /**
     * @Given I have a valid token
     */
    public function iHaveAValidToken()
    {
        $this->iHaveAValidTokenForUsernameAndPassword('admin', '1234');
    }

    /**
     * @Given I have a valid token for :user with password :pass
     * @Given I have a valid token for username :user and password :pass
     */
    public function iHaveAValidTokenForUsernameAndPassword($user, $pass)
    {
        $server = [
            'HTTP_HOST' => $this->getContainer()->getParameter('tree_house.api.token_host')
        ];

        $data = [
            'auth' => [
                'passwordCredentials' => [
                    'username' => $user,
                    'password' => $pass,
                ]
            ]
        ];

        $response = parent::request('POST', '/tokens', json_encode($data), [], $server);
        $result   = json_decode($response->getContent(), true);

        static::$authToken = $result['access']['token']['id'];
    }

    /**
     * @Given I have a valid user token
     */
    public function iHaveAValidUserToken()
    {
        $this->iHaveAValidUserTokenForUsernameAndPassword('admin', '1234');
    }

    /** @Given I have a valid user token for username :user and password :pass
     */
    public function iHaveAValidUserTokenForUsernameAndPassword($user, $pass)
    {
        $data = [
            'username' => $user,
            'password' => $pass,
        ];

        $response = $this->request('POST', '/login/', json_encode($data));
        $result = json_decode($response->getContent(), true);

        static::$userToken = $result['result']['token'];
    }

    /**
     * @Then the api/API result should be ok
     */
    public function theResultShouldBeOk()
    {
        $this->theResponseIsJson();

        $response = $this->getApiResponse();
        Assert::assertArrayHasKey('ok', $response);
        Assert::assertTrue($response['ok']);
    }

    /**
     * @Then the api/API result should be not ok
     */
    public function theResultShouldBeNotOk()
    {
        $this->theResponseIsJson();

        $response = $this->getApiResponse();
        Assert::assertArrayHasKey('ok', $response);
        Assert::assertFalse($response['ok']);
    }

    /**
     * @Then the api/API result should contain :count item(s)
     * @Then the api/API result contains :count item(s)
     */
    public function theResultContainsExactlyItems($count)
    {
        $result = $this->getApiResult();

        Assert::assertContainsOnly('array', $result);
        Assert::assertEquals($count, count($result));
    }

    /**
     * @Then the api/API result should contain at least :count item(s)
     * @Then the api/API result contains at least :count item(s)
     */
    public function theResultContainsAtLeastItems($count)
    {
        $result = $this->getApiResult();

        Assert::assertContainsOnly('array', $result);
        Assert::assertGreaterThanOrEqual($count, count($result));
    }

    /**
     * @Then the api/API result should contain key :key
     */
    public function theResultShouldContainKey($key)
    {
        $result = $this->getApiResult();
        Assert::assertArrayHasKey($key, $result);
    }

    /**
     * @Then the api/API result key :key should not equal null
     */
    public function theResultKeyShouldNotEqualNull($key)
    {
        $result = $this->getApiResult();
        Assert::assertArrayHasKey($key, $result);
        Assert::assertNotNull($result[$key]);
    }

    /**
     * @Then the api/API result key :key should equal :value
     */
    public function theResultKeyShouldEqual($key, $value)
    {
        $result = $this->getApiResult();
        Assert::assertArrayHasKey($key, $result);
        Assert::assertEquals($value, $result[$key]);
    }

    /**
     * @inheritdoc
     */
    protected function request($method, $uri, $data = null, array $headers = [], array $server = [])
    {
        if (!array_key_exists('HTTP_HOST', $server)) {
            $server['HTTP_HOST'] = $this->getContainer()->getParameter('tree_house.api.host');
        }

        if (null !== static::$authToken) {
            $headers['X-Auth-Token'] = static::$authToken;
        }

        if (null !== static::$userToken) {
            $headers['X-User-Token'] = static::$userToken;
        }

        // TODO version should be configurable
        return parent::request($method, '/v1/' . ltrim($uri, '/'), $data, $headers, $server);
    }

    /**
     * Returns the complete response from the last api call, decoded to an array.
     *
     * @return array
     */
    protected function getApiResponse()
    {
        return json_decode($this->getResponseContent(), true);
    }

    /**
     * Returns the "result" key of the last api response, json decoded.
     *
     * @return mixed
     */
    protected function getApiResult()
    {
        return $this->getApiResponse()['result'];
    }
}
