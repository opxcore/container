<?php

namespace OpxCore\Container\Exceptions;

use Exception;
use OpxCore\Container\Interfaces\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{

}
