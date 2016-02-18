<?php

namespace TreeHouse\BaseApiBundle\Tests\Controller;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TreeHouse\BaseApiBundle\DependencyInjection\TreeHouseBaseApiExtension;
use TreeHouse\BaseApiBundle\Security\SecurityContext;
use TreeHouse\BaseApiBundle\Tests\Mock\ApiControllerMock;

class BaseApiControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetNoApiUser()
    {
        $container = $this->getContainer();

        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $this->assertNull($controller->doGetApiUser());
    }

    public function testGetApiUser()
    {
        $securityContext = $this->getSecurityContextMock();
        $container = $this->getContainer(['tree_house.api.security.security_context' => $securityContext]);

        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $user = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMockForAbstractClass();

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->setMethods(['getUser'])
            ->getMockForAbstractClass()
        ;
        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user))
        ;

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $this->assertSame($user, $controller->dogetApiUser());
    }

    public function testGetRequestData()
    {
        $container = $this->getContainer();
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $query = ['foo' => 'bar'];
        $request = Request::create('/foo', 'GET', $query);
        $data = $controller->doGetRequestData($request);
        $this->assertEquals($query, $data->all(), '->getRequestData for GET request');

        $request = Request::create('/foo', 'POST', [], [], [], [], 'foo');
        $data = $controller->doGetRequestData($request);
        $this->assertEquals('foo', $data, '->getRequestData for POST request');

        $request = Request::create('/foo', 'PUT', [], [], [], [], 'foo');
        $data = $controller->doGetRequestData($request);
        $this->assertEquals('foo', $data, '->getRequestData for POST request');

        $request = Request::create('/foo', 'DELETE', [], [], [], [], 'foo');
        $data = $controller->doGetRequestData($request);
        $this->assertEquals('foo', $data, '->getRequestData for POST request');

        $query = ['foo' => 'bar'];
        $request = Request::create('/foo', 'UNDEF', $query);
        $data = $controller->doGetRequestData($request);
        $this->assertEquals($query, $data->all(), '->getRequestData for undefined request');
    }

    public function testGetSerializedRequestData()
    {
        $serializer = $this->getSerializerMock();
        $container = $this->getContainer(['jms_serializer' => $serializer]);
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->will($this->returnValue('deserialized!'))
        ;

        $request = Request::create('/foo', 'GET', ['foo' => 'bar']);
        $data = $controller->doGetRequestData($request, 'test');
        $this->assertEquals('deserialized!', $data, '->getRequestData for GET request, serialized');
    }

    public function testValidate()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $validator = $this->getValidatorMock();
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->will($this->returnValue(new ConstraintViolationList([])))
        ;

        $container = $this->getContainer(['validator' => $validator]);
        $controller = new ApiControllerMock();
        $controller->setContainer($container);
        $controller->doValidate($request);
    }

    /**
     * @expectedException \TreeHouse\BaseApiBundle\Exception\ValidationException
     */
    public function testValidateWithError()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $violation = $this
            ->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $validator = $this->getValidatorMock();
        $validator
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->will($this->returnValue(new ConstraintViolationList([$violation])))
        ;

        $container = $this->getContainer(['validator' => $validator]);
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $controller->doValidate($request);
    }

    public function testCreateResponse()
    {
        $container = $this->getContainer();
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        // create default response
        $response = $controller->doCreateResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // create response with different code
        $response = $controller->doCreateResponse(Response::HTTP_I_AM_A_TEAPOT);
        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $response->getStatusCode());
    }

    public function testRenderResponse()
    {
        $serializer = $this->getSerializerMock();
        $container  = $this->getContainer(['jms_serializer' => $serializer]);
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->will($this->returnCallback(function ($data) {
                return json_encode($data);
            }))
        ;

        $request = Request::create('/foo');

        $response = $controller->doRenderResponse($request, ['foo' => 'bar'], true, Response::HTTP_OK);
        $data = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('ok', $data);
        $this->assertTrue($data['ok']);
        $this->assertArrayHasKey('foo', $data);
        $this->assertEquals('bar', $data['foo']);
    }

    public function testRenderOk()
    {
        /** @var ApiControllerMock|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this
            ->getMockBuilder('TreeHouse\BaseApiBundle\Tests\Mock\ApiControllerMock')
            ->setMethods(['renderResponse'])
            ->getMockForAbstractClass()
        ;

        $request = Request::create('/foo');

        $data = ['foo' => 'bar'];
        $code = Response::HTTP_OK;
        $meta = ['metafoo' => 'metabar'];

        $result = [
            'metadata' => $meta,
            'result'   => $data
        ];

        $controller
            ->expects($this->once())
            ->method('renderResponse')
            ->with($request, $result, true, $code)
        ;

        $controller->doRenderOk($request, $data, $code, [], $meta);
    }

    public function testRenderError()
    {
        /** @var ApiControllerMock|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this
            ->getMockBuilder('TreeHouse\BaseApiBundle\Tests\Mock\ApiControllerMock')
            ->setMethods(['renderResponse'])
            ->getMockForAbstractClass()
        ;

        $request = Request::create('/foo');

        $code  = Response::HTTP_FORBIDDEN;
        $error = 'oh noes!';

        $result = [
            'error' => $error
        ];

        $controller
            ->expects($this->once())
            ->method('renderResponse')
            ->with($request, $result, false, $code)
        ;

        $controller->doRenderError($request, $code, $error);
    }

    public function testCorsHeader()
    {
        $container = $this->getContainer();
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        // create default response
        $response = $controller->doCreateResponse();

        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertEquals('acme.org', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testJsonp()
    {
        $serializer = $this->getSerializerMock();
        $container  = $this->getContainer(['jms_serializer' => $serializer]);
        $controller = new ApiControllerMock();
        $controller->setContainer($container);

        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->will($this->returnCallback(function ($data) {
                return json_encode($data);
            }))
        ;

        $request = Request::create('/foo', 'GET', ['callback' => 'foo']);
        $response = $controller->doRenderResponse($request, ['foo' => 'bar'], true, Response::HTTP_OK);

        $this->assertEquals(
            '/**/foo({"ok":true,"foo":"bar"});',
            $response->getContent()
        );
    }

    public function testJsonpInvalidCallback()
    {
        $controller = new ApiControllerMock();
        $controller->setContainer($this->getContainer(['jms_serializer' => $this->getSerializerMock()]));

        $request = Request::create('/foo', 'GET', ['callback' => '(function xss(x) {evil()})']);
        $response = $controller->doRenderResponse($request, ['foo' => 'bar'], true, Response::HTTP_OK);

        $this->assertEquals(
            Response::HTTP_BAD_REQUEST,
            $response->getStatusCode()
        );
    }

    /**
     * @param array  $gets
     * @param string $file
     * @param array  $parameters
     * @param bool   $debug
     *
     * @return ContainerBuilder
     */
    protected function getContainer(array $gets = [], $file = 'complete.yml', $parameters = [], $debug = false)
    {
        $container = new ContainerBuilder(new ParameterBag(array_merge($parameters, ['kernel.debug' => $debug])));
        $container->registerExtension(new TreeHouseBaseApiExtension());

        $locator = new FileLocator(__DIR__ . '/../Fixtures');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file);

        foreach ($gets as $id => $service) {
            $container->set($id, $service);
        }

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }

    /**
     * @return SecurityContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityContextMock()
    {
        return $this
            ->getMockBuilder('TreeHouse\BaseApiBundle\Security\SecurityContext')
            ->getMock()
        ;
    }

    /**
     * @return SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSerializerMock()
    {
        return $this
            ->getMockBuilder('JMS\Serializer\SerializerInterface')
            ->getMock()
        ;
    }

    /**
     * @return ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getValidatorMock()
    {
        return $this
            ->getMockBuilder('Symfony\Component\Validator\ValidatorInterface')
            ->getMock()
        ;
    }
}
