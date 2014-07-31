<?php

namespace TreeHouse\BaseApiBundle\Tests\Mock;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TreeHouse\BaseApiBundle\Controller\BaseApiController;

class ApiControllerMock extends BaseApiController
{
    public function doGetApiUser()
    {
        return $this->getApiUser();
    }

    public function doGetRequestData(Request $request, $serializeType = null)
    {
        return $this->getRequestData($request, $serializeType);
    }

    public function doValidate(Request $request)
    {
        $this->validate($request);
    }

    public function doCreateResponse($statusCode = Response::HTTP_OK)
    {
        return $this->createResponse($statusCode);
    }

    public function doRenderOk(Request $request, $result, $code = 200, array $groups = [], array $metadata = [])
    {
        return $this->renderOk($request, $result, $code, $groups, $metadata);
    }

    public function doRenderError(Request $request, $code = 400, $error, array $groups = [])
    {
        return $this->renderError($request, $code, $error, $groups);
    }

    public function doRenderResponse(Request $request, array $result = [], $ok, $statusCode, array $groups = [])
    {
        return $this->renderResponse($request, $result, $ok, $statusCode, $groups);
    }
}
