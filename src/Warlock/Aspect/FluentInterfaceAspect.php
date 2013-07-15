<?php

namespace Warlock\Aspect;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\Around;

/**
 * Aspect allows for easy creation of fluent interface by implementing FleuntInterface marker
 *
 * @author Alexander Lisachenko
 */
class FluentInterfaceAspect implements Aspect
{
    /**
     * Fluent interface advice
     *
     * @Around("within(Warlock\MarkerInterface\FluentInterface+) && execution(public **->set*(*))")
     *
     * @param MethodInvocation $invocation
     * @return mixed|null|object
     */
    protected function aroundMethodExecution(MethodInvocation $invocation)
    {
        $result = $invocation->proceed();
        return $result!==null ? $result : $invocation->getThis();
    }
}