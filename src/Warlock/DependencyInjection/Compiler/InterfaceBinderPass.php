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
        $interfaceProviders = $container->findTaggedServiceIds('warlock.interface');
        $definition = $container->getDefinition('warlock.interface.resolver');

        foreach ($interfaceProviders as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $interfaceName = $tag['provide'];
                $definition->addMethodCall('bind', array($interfaceName, $serviceId));
            }
        }
    }
}