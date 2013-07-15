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

}
