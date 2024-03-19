<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class BaseController extends AbstractController
{
    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[$this->getErrorFieldName($error)] = $error->getMessage();
        }

        return $errors;
    }

    private function getErrorFieldName(FormError $error): string
    {
        $origin = $error->getOrigin();
        if ($origin instanceof FormInterface) {
            $name = $origin->getName();
            if (ctype_digit($name)) {
                $parent = $origin->getParent();
                $name = $parent ? $parent->getName() : '';
            }

            return $name;
        }

        return '';
    }
}