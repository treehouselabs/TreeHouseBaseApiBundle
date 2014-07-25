<?php

namespace TreeHouse\BaseApiBundle\Controller;

use TreeHouse\BaseApiBundle\Security\SecurityContext;
use TreeHouse\BaseApiBundle\Exception\ValidationException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\ValidatorInterface;

abstract class BaseApiController extends Controller
{
    /**
     * @return UserInterface
     */
    public function getApiUser()
    {
        if ($token = $this->getSecurityContext()->getToken()) {
            return $token->getUser();
        }

        return null;
    }

    /**
     * @param Request $request
     * @param string  $serializeType
     *
     * @return ParameterBag|object
     */
    public function getRequestData(Request $request, $serializeType = null)
    {
        $data = null;

        switch ($request->getMethod()) {
            case 'GET':
                $data = $request->query;
                break;

            case 'POST':
            case 'PUT':
            case 'DELETE':
                $data = $request->getContent();
                break;

            default:
                $data = $request->query;
                break;
        }

        if ($serializeType) {
            $data = $this->getSerializer()->deserialize($data, $serializeType, 'json');
        }

        return $data;
    }

    /**
     * Validates an API request
     *
     * @param object $request
     *
     * @throws ValidationException
     */
    public function validate($request)
    {
        $violations = $this->getValidator()->validate($request);

        if (count($violations) > 0) {
            throw ValidationException::create($violations);
        }
    }

    /**
     * @param integer $statusCode
     *
     * @return Response
     */
    public function createResponse($statusCode = Response::HTTP_OK)
    {
        $response = new JsonResponse();
        $response->setStatusCode($statusCode);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * Renders an successful Api call.
     *
     * @see renderResponse()
     *
     * @param mixed   $result   The result of the call.
     * @param integer $code     The response code.
     * @param array   $groups   JMS\Serializer groups
     * @param array   $metadata Extra metadata to put in the response
     *
     * @return Response
     */
    public function renderOk($result, $code = 200, array $groups = [], array $metadata = [])
    {
        $data = array();

        if (!empty($metadata)) {
            $data['metadata'] = $metadata;
        }

        if (null !== $result) {
            $data['result'] = $result;
        }

        return $this->renderResponse($data, true, $code, $groups);
    }

    /**
     * Renders an Api error.
     *
     * @see renderResponse()
     *
     * @param integer      $code   The response code
     * @param string|array $error  The error
     * @param array        $groups JMS\Serializer groups
     *
     * @return Response
     */
    public function renderError($code = 400, $error, array $groups = [])
    {
        return $this->renderResponse(['error' => $error], false, $code, $groups);
    }

    /**
     * Renders a JSON response in a generic structure:
     *
     * <code>
     * {
     *    "ok": true,
     *    "result": {
     *      [...]
     *    }
     * }
     * </code>
     *
     * Or in case of an error:
     *
     * <code>
     * {
     *    "ok": false,
     *    "error": "message"
     * }
     * </code>
     *
     * @param  array   $result     The result of the call.
     * @param  boolean $ok         Whether the call was successful or not.
     * @param  integer $statusCode The response code.
     * @param  array   $groups     JMS\Serializer groups
     * @return string
     */
    public function renderResponse(array $result = [], $ok, $statusCode, array $groups = [])
    {
        $response = $this->createResponse($statusCode);

        $data = array_merge(
            ['ok' => $ok],
            $result
        );

        $context = SerializationContext::create();
        $context->setSerializeNull(true);

        if ($groups) {
            $context->setGroups($groups);
        }

        return $this->getTemplating()->renderResponse(
            'TreeHouseBaseApiBundle:Default:action.json.twig',
            ['data' => $data, 'context' => $context],
            $response
        );
    }

    /**
     * @return SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->get('tree_house.api.security.context');
    }

    /**
     * @return SerializerInterface
     */
    protected function getSerializer()
    {
        return $this->get('jms_serializer');
    }

    /**
     * @return EngineInterface
     */
    protected function getTemplating()
    {
        return $this->get('templating');
    }

    /**
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }
}
