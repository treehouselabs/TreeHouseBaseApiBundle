<?php

namespace TreeHouse\BaseApiBundle\Exception;

/**
 * Exception for when a resource is not found
 */
class NotFoundException extends ApiException
{
    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param integer    $code     The internal exception code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        $this->statusCode = 404;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
