<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Interface binder pass collect all services that provides interfaces and register them in one place
 */
class InterfaceBinderPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $resolver = $container->getDefinition('warlock.interface.resolver');
        $bindings = array();

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $reflector  = new \ReflectionClass($definition->getClass());
            $interfaces = $reflector->getInterfaces();
            foreach ($interfaces as $interface) {
                $bindings[$interface->name][] = $serviceId;
            }
        }
        $resolver->addMethodCall('addBindings', array($bindings));
    }
}