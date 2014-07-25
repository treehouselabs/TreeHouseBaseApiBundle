<?php

namespace TreeHouse\BaseApiBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends ApiException
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $violations;

    /**
     * @param ConstraintViolationListInterface $violations
     */
    public function setViolations(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param ConstraintViolationListInterface $violations
     *
     * @return ValidationException
     */
    public static function create(ConstraintViolationListInterface $violations)
    {
        $exception = new ValidationException((string) $violations);
        $exception->setViolations($violations);

        return $exception;
    }
}
