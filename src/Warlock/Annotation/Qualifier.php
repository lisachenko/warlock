<?php

namespace Warlock\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Qualifier annotation declares the concrete service for argument
 *
 * @Annotation
 * @Target("METHOD")
 */
class Qualifier extends Annotation
{

    /**
     * Service type
     */
    const SERVICE = 'service';

    /**
     * Parameter qualifier
     */
    const PARAMETER = 'parameter';

    /**
     * Name of the service|parameter to use for injecting as an argument
     *
     * @var string
     */
    public $name;

    /**
     * Type of current qualifier
     *
     * @var string
     */
    public $type = self::SERVICE;
}
