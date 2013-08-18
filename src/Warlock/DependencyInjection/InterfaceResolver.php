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
     * @param string|null $qualifierId Optional qualifier of binding
     */
    public function bind($interface, $serviceId, $qualifierId = null)
    {
        $id = $qualifierId ?: $serviceId;
        $this->bindlings[$interface][$id] = $serviceId;
    }

    /**
     * Resolves an interface to the concrete service that implements it
     *
     * @param string $interface Interface name to resolve
     * @param string|null $qualifierId Optional qualifier name
     *
     * @return object Specific service from the container that implements this interface
     * @throws \InvalidArgumentException
     */
    public function resolve($interface, $qualifierId = null)
    {
        $serviceId   = null;
        $hasBinding  = isset($this->bindlings[$interface]);
        $hasConcrete = $qualifierId && isset($this->bindlings[$interface][$qualifierId]);

        if ($qualifierId && !$hasConcrete) {
            $errorMessage = "There is not binding for {$interface} interface with qualifier {$qualifierId}";
            throw new \InvalidArgumentException($errorMessage);
        }

        if ($hasConcrete) {
            $serviceId = $this->bindlings[$interface][$qualifierId];
        } elseif ($hasBinding) {
            if (count($this->bindlings[$interface]) !== 1) {
                $possibleQualifiers = array_keys($this->bindlings[$interface]);
                $errorMessage = "There are multiple bindings for the interface {$interface}. ";
                $errorMessage .= "Please, choose one of " . join(', ', $possibleQualifiers) . " qualifier";
                throw new \InvalidArgumentException($errorMessage);
            }
            $serviceId = reset($this->bindlings[$interface]);
        } else {
            $serviceId = $qualifierId;
        }
        return $this->container->get($serviceId);
    }
}