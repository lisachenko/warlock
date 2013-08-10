<?php

namespace Warlock\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Autowire annotation specifies the service to inject for property
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Autowired extends Annotation
{

    /**
     * It this flag is set to false then no exception will be thrown if there isn't service in container
     *
     * @var bool
     */
    public $required = true;

    /**
     * Returns an identifier of service as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
