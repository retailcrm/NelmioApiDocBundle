<?php


namespace Nelmio\ApiDocBundle\Parser;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormTypeInterface;

interface FormInfoParser
{
    public function parseFormType(FormTypeInterface $type, FormConfigInterface $config): ?array;
}
