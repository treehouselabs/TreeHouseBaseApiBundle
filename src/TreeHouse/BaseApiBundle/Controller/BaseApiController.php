<?php

namespace TreeHouse\BaseApiBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TreeHouse\BaseApiBundle\Security\SecurityContext;
use TreeHouse\BaseApiBundle\Exception\ValidationException;

abstract class BaseApiController extends Controller
{
    /**
     * @param int $statusCode
     *
     * @return JsonResponse
     */
    protected function createResponse($statusCode = Response::HTTP_OK)
    {
        $response = new JsonResponse();
        $response->setStatusCode($statusCode);
        $response->headers->set('Access-Control-Allow-Origin', $this->container->getParameter('tree_house.api.allowed_origins'));

        return $response;
    }

    /**
     * Renders an successful Api call.
     *
     * @see renderResponse()
     *
     * @param Request              $request
     * @param mixed                $result   The result of the call.
     * @param int                  $code     The response code.
     * @param array                $groups   JMS\Serializer groups
     * @param array                $metadata Extra metadata to put in the response
     * @param SerializationContext $context  The context to use for serializing the result data
     *
     * @return JsonResponse
     */
    protected function renderOk(Request $request, $result, $code = 200, array $groups = [], array $metadata = [], SerializationContext $context = null)
    {
        $data = array();

        if (!empty($metadata)) {
            $data['metadata'] = $metadata;
        }

        if (null !== $result) {
            $data['result'] = $result;
        }

        return $this->renderResponse($request, $data, true, $code, $groups, $context);
    }

    /**
     * Renders an Api error.
     *
     * @see renderResponse()
     *
     * @param Request              $request
     * @param int                  $code    The response code
     * @param string|array         $error   The error
     * @param array                $groups  JMS\Serializer groups
     * @param SerializationContext $context The context to use for serializing the result data
     *
     * @return JsonResponse
     */
    protected function renderError(
        Request $request,
        $code = 400,
        $error,
        array $groups = [],
        SerializationContext $context = null
    ) {
        return $this->renderResponse($request, ['error' => $error], false, $code, $groups, $context);
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
     * @param Request              $request
     * @param array                $result     The result of the call.
     * @param bool                 $ok         Whether the call was successful or not.
     * @param int                  $statusCode The response code.
     * @param array                $groups     JMS\Serializer groups
     * @param SerializationContext $context    The context to use for serializing the result data
     *
     * @return JsonResponse
     */
    protected function renderResponse(
        Request $request,
        array $result = [],
        $ok,
        $statusCode,
        array $groups = [],
        SerializationContext $context = null
    ) {
        $response = $this->createResponse($statusCode);

        $data = array_merge(
            ['ok' => $ok],
            $result
        );

        if ($context === null) {
            $context = SerializationContext::create();
        }

        if ($groups) {
            $context->setGroups($groups);
        }

        $context->setSerializeNull(true);

        // the json response needs to have data set as an array, rather than setting the content directly.
        // this is because other options use that to overwrite the content (like jsonp).
        // so unfortunately we have to double-convert the data, since the serializer won't convert to
        // arrays just yet :(
        $json = $this->getSerializer()->serialize($data, 'json', $context);
        $response->setData(json_decode($json, true));

        // handle JSON-P requests
        $callback = $request->query->get('callback', '');
        if (!empty($callback)) {
            try {
                $response->setCallback($callback);
            } catch (\InvalidArgumentException $e) {
                // remove the callback from the query parameters, and render an error
                $request->query->remove('callback');

                return $this->renderError($request, Response::HTTP_BAD_REQUEST, $e->getMessage(), $groups, $context);
            }
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param string  $serializeType
     *
     * @return ParameterBag|object
     */
    protected function getRequestData(Request $request, $serializeType = null)
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
    protected function validate($request)
    {
        $violations = $this->getValidator()->validate($request);

        if (count($violations) > 0) {
            throw ValidationException::create($violations);
        }
    }

    /**
     * @return UserInterface
     */
    protected function getApiUser()
    {
        if ($token = $this->getSecurityContext()->getToken()) {
            return $token->getUser();
        }

        return null;
    }

    /**
     * @return SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->get('tree_house.api.security.security_context');
    }

    /**
     * @return SerializerInterface
     */
    protected function getSerializer()
    {
        return $this->get('jms_serializer');
    }

    /**
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }
}
