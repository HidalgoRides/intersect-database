<?php

namespace Intersect\Database\Model\Validation;

use Intersect\Database\Exception\ValidationException;
use Intersect\Database\Model\AbstractModel;
use Intersect\Database\Model\Model;
use Intersect\Database\Model\Validation\Validator\PropertyValidator;
use Intersect\Database\Model\Validation\Validator\RequiredPropertyValidator;

class ModelValidator {

    /** @var Model */
    private $model;
    private $needsValidation = false;
    private $propertyValidators = [];
    private $validationPassed = true;
    private $validationReasons = [];

    /**
     * @param AbstractModel $model
     * @param array $validatorMap
     * @throws ValidationException
     */
    public function validate(AbstractModel $model, array $validatorMap)
    {
        if (is_null($validatorMap) || count($validatorMap) == 0)
        {
            return;
        }

        $this->model = $model;
        $this->initValidatorMap($validatorMap);

        if ($this->needsValidation)
        {
            $this->performValidation();

            if (!$this->validationPassed)
            {
                throw new ValidationException($this->model, $this->validationReasons);
            }
        }
    }

    /**
     * @param $validatorMap
     */
    private function initValidatorMap($validatorMap)
    {
        foreach ($validatorMap as $property => $propertyValidators)
        {
            if (!is_array($propertyValidators))
            {
                $propertyValidators = [$propertyValidators];
            }

            foreach ($propertyValidators as $propertyValidator)
            {
                if ($propertyValidator instanceof PropertyValidator)
                {
                    $this->propertyValidators[$property][] = $propertyValidator;
                    continue;
                }
                else if (is_string($propertyValidator))
                {
                    $this->initValidatorFromString($property, $propertyValidator);
                }
            }
        }

        $this->needsValidation = (count($this->propertyValidators));
    }

    /**
     * @param $property
     * @param $validator
     */
    private function initValidatorFromString($property, $validator)
    {
        $validatorNames = explode('|', $validator);

        foreach ($validatorNames as $validatorName)
        {
            if (is_null($validatorName) || trim($validatorName) == '')
            {
                continue;
            }

            if (class_exists($validatorName))
            {
                $validatorClass = new $validatorName();
                if ($validatorClass instanceof PropertyValidator)
                {
                    $validatorClassName = get_class($validatorClass);
                    $this->propertyValidators[$property][$validatorClassName] = $validatorClass;
                    continue;
                }
            }
            else
            {
                $validatorName = strtolower($validatorName);

                if ($validatorName == 'required')
                {
                    $requiredPropertyValidatorClass = new RequiredPropertyValidator();
                    $requiredPropertyValidatorClassName = get_class($requiredPropertyValidatorClass);
                    $this->propertyValidators[$property][$requiredPropertyValidatorClassName] = $requiredPropertyValidatorClass;
                }
            }
        }
    }

    private function performValidation()
    {
        $attributes = $this->model->getAttributes();

        /** @var PropertyValidator $propertyValidator */
        foreach ($this->propertyValidators as $name => $propertyValidators)
        {
            foreach ($propertyValidators as $propertyValidator)
            {
                if ($propertyValidator instanceof RequiredPropertyValidator && !array_key_exists($name, $attributes))
                {
                    $this->validationPassed = false;
                    $this->validationReasons[] = $propertyValidator->getMessage($name);
                }
                else if (array_key_exists($name, $attributes))
                {
                    if (!$propertyValidator->validate($attributes[$name]))
                    {
                        $this->validationPassed = false;
                        $this->validationReasons[] = $propertyValidator->getMessage($name);
                    }
                }
            }
        }
    }

}