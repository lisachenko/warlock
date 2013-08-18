<?php

namespace Warlock\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Indicates that an annotated class is a "component"
 *
 * Such classes are considered as candidates for auto-detection when using annotation-based configuration and classpath
 * scanning.
 *
 * @Annotation
 * @Target("CLASS")
 */
class Component extends Annotation
{
    public $value = '';

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
