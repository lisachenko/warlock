<?php

namespace Warlock\Aspect;


use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Core\AspectContainer;
use Go\Lang\Annotation\Before;
use Warlock\WarlockContainer;

/**
 * Autowiring aspect provides automatic injection of services for classes
 *
 * @author Alexander Lisachenko
 */
class AutowiringAspect implements Aspect
{

    /**
     * Instance of aspect container for injecting dependencies
     *
     * @var AspectContainer|null
     */
    protected $container = null;

    /**
     * Annotation reader
     *
     * @var Reader|null
     */
    protected $reader = null;

    /**
     * Aspect constructor
     *
     * @param WarlockContainer $container
     * @param Reader $reader Annotation reader (TODO: remove this dependency)
     */
    public function __construct(AspectContainer $container, Reader $reader)
    {
        $this->container = $container;
        $this->reader    = $reader;
    }

    /**
     * Intercepts access to autowired properties and injects specified dependency
     *
     * @Before("access(protected|public **->*)")
     *
     * @todo Use annotation joinpoint (need to rewrite parser for better handling of \@annotation)
     * @param FieldAccess $joinpoint Autowiring joinpoint
     */
    public function beforeAccessingAutowiredProperty(FieldAccess $joinpoint)
    {
        $obj   = $joinpoint->getThis();
        $field = $joinpoint->getField();

        if ($joinpoint->getAccessType() == FieldAccess::READ) {
            $autowire = $this->reader->getPropertyAnnotation($field, 'Warlock\Annotation\Autowired');
            if ($autowire) {
                $field->setValue($obj, $this->container->get($autowire->value));
            }
        }
    }
}