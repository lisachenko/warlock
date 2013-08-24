<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

class InterfaceResolver
{

    /**
     * Instance of DI-container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Bindings of interfaces to services
     *
     * @var array
     */
    private $bindlings = array();

    /**
     * Default constructor
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Binds an interface name to the concrete service that implements it
     *
     * @param string $interface Interface name to bind
     * @param string $serviceId Identifier os service that implements this interface
     */
    public function bind($interface, $serviceId)
    {
        $this->bindlings[$interface][] = $serviceId;
    }

    /**
     * Add new bindings to the container
     *
     * @param array $bindings
     */
    public function addBindings(array $bindings)
    {
        $this->bindlings = array_merge_recursive($this->bindlings, $bindings);
    }

    /**
     * Resolves an interface to the concrete service that implements it
     *
     * @param string $interface Interface name to resolve
     * @param string|null $serviceId Optional service qualifier
     *
     * @return object Specific service from the container that implements this interface
     * @throws \InvalidArgumentException
     */
    public function resolve($interface, $serviceId = null)
    {
        $hasBinding  = isset($this->bindlings[$interface]);
        $hasConcrete = $hasBinding && $serviceId && in_array($serviceId, $this->bindlings[$interface]);

        if ($serviceId && !$hasConcrete) {
            $errorMessage = "There are no public services for the {$interface} interface with identifier {$serviceId}";
            throw new \InvalidArgumentException($errorMessage);
        }

        if ($hasConcrete) {
            return $this->container->get($serviceId);
        }

        if ($hasBinding) {
            if (count($this->bindlings[$interface]) !== 1) {
                $possibleQualifiers = $this->bindlings[$interface];
                $errorMessage = "There are multiple services that provide {$interface} interface. ";
                $errorMessage .= "Can not automatically inject an interface provider. ";
                $errorMessage .= "Please, choose one of " . join(', ', $possibleQualifiers) . ".";
                throw new \InvalidArgumentException($errorMessage);
            }
            $serviceId = $this->bindlings[$interface][0];
        } else {
            $errorMessage = "There are no public services that can provide {$interface} interface.";
            throw new \InvalidArgumentException($errorMessage);
        }
        return $this->container->get($serviceId);
    }
}