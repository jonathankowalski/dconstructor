<?php

namespace JonathanKowalski\Dconstructor\Exception;

use Psr\Container\ContainerExceptionInterface;

class NotFoundException extends \InvalidArgumentException implements ContainerExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(sprintf('Identifier "%s" is not defined.', $id));
    }
}
