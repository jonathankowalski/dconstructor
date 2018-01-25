<?php
/**
 * Created by PhpStorm.
 * User: jonathankowalski
 * Date: 17/07/2017
 * Time: 19:31
 */

namespace JonathanKowalski\Dconstructor\Exception;

use Psr\Container\ContainerExceptionInterface;

class NotFoundException extends \InvalidArgumentException implements ContainerExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(sprintf('Identifier "%s" is not defined.', $id));
    }
}
