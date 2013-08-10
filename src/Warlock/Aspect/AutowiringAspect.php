<?php

namespace Warlock\Aspect;


use Doctrine\Common\Annotations\Reader;
use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Core\AspectContainer;
use Go\Lang\Annotation\Around;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Warlock\Annotation\Autowired;
use Warlock\WarlockContainer;

/**
 * Autowiring aspect provides automatic injection of services for classes
 *
 * @author Alexander Lisachenko
 */
class AutowiringAspect implements Aspect
{
    /**
     * Name of the annotation class to match
     */
    const ANNOTATION_NAME = 'Warlock\Annotation\Autowired';

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
     * @Around("@access(Warlock\Annotation\Autowired)")
     *
     * @param FieldAccess $joinpoint Autowiring joinpoint
     *
     * @return mixed
     */
    public function beforeAccessingAutowiredProperty(FieldAccess $joinpoint)
    {
        $obj   = $joinpoint->getThis();
        $field = $joinpoint->getField();

        if ($joinpoint->getAccessType() == FieldAccess::READ) {
            /** @var Autowired $autowired */
            $autowired = $this->reader->getPropertyAnnotation($field, self::ANNOTATION_NAME);
            $strategy  = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
            if (!$autowired->required) {
                $strategy = ContainerInterface::NULL_ON_INVALID_REFERENCE;
            }
            $value = $this->container->get($autowired, $strategy);
        } else {
            $value = $joinpoint->proceed();
        }
        $field->setValue($obj, $value);
        return $value;
    }
}