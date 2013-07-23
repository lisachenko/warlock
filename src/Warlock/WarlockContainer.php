<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock;

use ReflectionClass;
use Go\Aop;
use Go\Core\AspectContainer;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class WarlockContainer provides basic implementation of AspectContainer interface
 */
class WarlockContainer extends Container implements AspectContainer
{
    /**
     * Store identifiers of services, grouped by tag
     *
     * @var array
     */
    protected $tags = array();

    /**
     * List of resources for application
     *
     * @var array
     */
    protected $resources = array();

    /**
     * Cached timestamp for resources
     *
     * @var integer
     */
    protected $maxTimestamp = 0;

    /**
     * Returns a pointcut by identifier
     *
     * @param string $id Pointcut identifier
     *
     * @return Aop\Pointcut
     */
    public function getPointcut($id)
    {
        return $this->get("pointcut.{$id}");
    }

    /**
     * Store the pointcut in the container
     *
     * @param Aop\Pointcut $pointcut Instance
     * @param string $id Key for pointcut
     */
    public function registerPointcut(Aop\Pointcut $pointcut, $id)
    {
        $identifier = "pointcut.{$id}";
        $this->set($identifier, $pointcut);
        $this->tags['pointcut'][] = $identifier;
    }

    /**
     * Store the advisor in the container
     *
     * @param Aop\Advisor $advisor Instance
     * @param string $id Key for advisor
     */
    public function registerAdvisor(Aop\Advisor $advisor, $id)
    {
        $identifier = "advisor.{$id}";
        $this->set($identifier, $advisor);
        $this->tags['advisor'][] = $identifier;
    }

    /**
     * Returns an aspect by id or class name
     *
     * @param string $aspectName Aspect name
     *
     * @return Aop\Aspect
     */
    public function getAspect($aspectName)
    {
        return $this->get("aspect.{$aspectName}");
    }

    /**
     * Register an aspect in the container
     *
     * @param Aop\Aspect $aspect Instance of concrete aspect
     */
    public function registerAspect(Aop\Aspect $aspect)
    {
        $refAspect  = new ReflectionClass($aspect);
        $identifier = "aspect.{$refAspect->name}";

        $this->set($identifier, $aspect);
        $this->tags['aspect'][] = $identifier;

        $this->addResource($refAspect->getFileName());
    }

    /**
     * Return list of service tagged with marker
     *
     * @param string $tag Tag to select
     * @return array
     */
    public function getByTag($tag)
    {
        $result = array();
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $id) {
                $result[$id] = $this->get($id);
            }
        }
        return $result;
    }

    /**
     * Add an resource for container
     *
     * TODO: use symfony/config component for creating the cache
     *
     * Resources is used to check the freshness of cache
     */
    public function addResource($resource)
    {
        $this->resources[]  = $resource;
        $this->maxTimestamp = 0;
    }

    /**
     * Checks the freshness of container
     *
     * @param integer $timestamp
     *
     * @return bool Whether or not container is fresh
     */
    public function isFresh($timestamp)
    {
        if (!$this->maxTimestamp && $this->resources) {
            $this->maxTimestamp = max(array_map('filemtime', $this->resources));
        }
        return $this->maxTimestamp < $timestamp;
    }

    /**
     * Returns the list of AOP resources
     *
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }
}