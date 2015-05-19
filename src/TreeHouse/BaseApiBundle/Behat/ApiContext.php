<?php

namespace TreeHouse\BaseApiBundle\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
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
     * @BeforeScenario
     */
    public function onBefore()
    {
        static::$authToken = null;
        static::$userToken = null;
        static::$response = null;
    }

    /**
     * @When I get/GET to :path
     */
    public function iGetTo($path)
    {
        $this->request('GET', $path);
    }

    /**
     * @When I request the api with :method :path
     */
    public function iRequestTheApi($method, $path)
    {
        $this->request($method, $path);
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
                Assert::stringStartsWith('application/json'),
                Assert::stringStartsWith('text/javascript')
            )
        );

        Assert::assertJson($this->getResponseContent());
    }

    /**
     * @Then the response should contain the following json/JSON:
     * @Then the response contains the following json/JSON:
     */
    public function theResponseShouldContainJson($jsonData)
    {
        $json     = $this->getJsonData($jsonData);
        $response = $this->getJsonData($this->getResponseContent());

        Assert::assertEquals($json, $response);
    }

    /**
     * @Then the response should contain the following jsonp/JSONP:
     * @Then the response contains the following jsonp/JSONP:
     */
    public function theResponseShouldContainJsonp(PyStringNode $jsonData)
    {
        $json  = preg_replace('~\s~', '', $jsonData->getRaw());
        $jsonp = preg_replace(['~/\*\*/~', '~\s~'], '', $this->getResponseContent());

        Assert::assertEquals($json, $jsonp);
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
     * @Then the api/API result key :key should equal:
     */
    public function theResultKeyShouldEqualNode($key, $node)
    {
        $json = $this->getJsonData($node);

        $result = $this->getApiResult();
        Assert::assertArrayHasKey($key, $result);
        Assert::assertEquals($json, $result[$key]);
    }

    /**
     * @Then the api error should contain ":text"
     */
    public function theApiErrorShouldContain($text)
    {
        $error = $this->getApiError();
        Assert::assertEquals($text, $error);
    }

    /**
     * @Then the api/API result should contain a key with:
     */
    public function theResultShouldContainNode($node)
    {
        $json = $this->getJsonData($node);

        $result = $this->getApiResult();
        foreach ($result as $value) {
            if ($value == $json) {
                return;
            }
        }

        Assert::fail(sprintf('No result was found containing: %s', json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)));
    }

    /**
     * @param string|PyStringNode|TableNode $data
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function getJsonData($data)
    {
        $json = null;

        if ($data instanceof PyStringNode) {
            $json = json_decode($data->getRaw(), true);
        }

        if ($data instanceof TableNode) {
            $json = $data->getRowsHash();
        }

        if (is_string($data)) {
            $json = json_decode($data, true);
        }

        if (null === $json) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid json data class ("%s")',
                get_class($data)
            ));
        }

        return $json;
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

        return parent::request($method, $uri, $data, $headers, $server);
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
        $response = $this->getApiResponse();

        Assert::assertArrayHasKey('result', $response);

        return $response['result'];
    }

    /**
     * Returns the "error" key of the last api response.
     *
     * @return mixed
     */
    protected function getApiError()
    {
        $response = $this->getApiResponse();

        Assert::assertArrayHasKey('error', $response);

        return $response['error'];
    }
}
