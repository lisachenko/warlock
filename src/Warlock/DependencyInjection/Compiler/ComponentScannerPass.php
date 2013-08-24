<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock\DependencyInjection\Compiler;

use Go\Instrument\RawAnnotationReader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TokenReflection\Broker;
use TokenReflection\ReflectionMethod;
use TokenReflection\ReflectionParameter;
use TokenReflection\ReflectionClass;
use TokenReflection\ReflectionFileNamespace;
use Warlock\Annotation\Qualifier;
use Warlock\Exception\SourceRuntimeException;

class ComponentScannerPass implements CompilerPassInterface
{

    /**
     * Annotation class name for component definition
     */
    const ANNOTATION_CLASS = 'Warlock\Annotation\Component';

    /**
     * Instance of token reflection broker
     *
     * @var null|Broker
     */
    protected $broker = null;

    /**
     * Annotation reader
     *
     * @var null|RawAnnotationReader
     */
    protected $reader = null;

    protected $directory = '';

    public function __construct($directory)
    {
        $this->broker    = new Broker(new Broker\Backend\Memory());
        $this->reader    = new RawAnnotationReader();
        $this->directory = $directory;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        /** @var \SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->directory,
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($files as $file) {

            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (strpos($content, '@Component') === false) {
                continue;
            }

            $reflectionFile = $this->broker->processString($content, $file->getPathname(), true);
            /** @var ReflectionFileNamespace[] $namespaces */
            $namespaces = $reflectionFile->getNamespaces();

            foreach ($namespaces as $namespace) {

                $classes = $namespace->getClasses();
                foreach ($classes as $class) {
                    $this->checkAndRegisterComponent($class, $container);
                }
            }
        }
        $container->addResource(new DirectoryResource($this->directory));
    }

    /**
     * Checks the class for possible component and register it in the container if needed
     *
     * @param ReflectionClass $class Instance of class reflection
     * @param ContainerBuilder $container
     *
     * @return bool True if component is registered
     */
    protected function checkAndRegisterComponent(ReflectionClass $class, ContainerBuilder $container)
    {
        $this->reader->setImports($class->getNamespaceAliases());
        $serviceName = str_replace('\\', '.', $class->getName());
        $annotation  = $this->reader->getClassAnnotation($class, self::ANNOTATION_CLASS);
        if (!$annotation) {
            return false;
        }

        $definition  = $container->register($serviceName, $class->getName());
        $constructor = $class->getConstructor();
        if ($constructor) {
            $this->bindConstructorArgs($constructor, $definition, $container);
        }
        $interfaces  = $class->getOwnInterfaceNames();
        foreach ($interfaces as $interface) {
            $definition->addTag('warlock.interface', array('provide' => $interface));
        }
        if ((string) $annotation) {
            // Make an alias for annotation
            $container->setAlias($annotation, $serviceName);
        }

        return true;
    }

    /**
     * Bind a constructor arguments to the services
     *
     * @param ReflectionMethod $ctor Reflection of constructor
     * @param Definition $definition Service definition for component
     * @param ContainerBuilder $container
     *
     * @throws \RuntimeException
     */
    protected function bindConstructorArgs(ReflectionMethod $ctor, Definition $definition, ContainerBuilder $container)
    {
        $parameterQualifiers = $this->getQualifiers($ctor);
        foreach ($ctor->getParameters() as $parameter) {

            /** @var $parameter ReflectionParameter */
            $typehintClass = $parameter->getClass();
            $hasQualifier  = isset($parameterQualifiers[$parameter->name]);
            if (!$typehintClass && !$hasQualifier) {
                $error = "Can not automatically bind parameter {$parameter->name}. ";
                $error .= "Please, use @Qualifier annotation to specify the concrete service or parameter.";
                throw new SourceRuntimeException($error, $parameter);
            }

            $qualifier = $hasQualifier ? $parameterQualifiers[$parameter->name] : null;
            if ($hasQualifier && $qualifier->type === Qualifier::PARAMETER) {
                $definition->addArgument("%{$qualifier->name}%");
                continue;
            }

            $identifier  = $typehintClass ? $typehintClass->getName() : $qualifier->name;
            $serviceName = str_replace('\\', '.', $identifier);

            if (!$container->hasDefinition($serviceName)) {
                $injector = $container->register($serviceName, $serviceName);
                $injector
                    ->setFactoryService('warlock.interface.resolver')
                    ->setFactoryMethod('resolve')
                    ->addArgument($identifier)
                    ->setPublic('false');
            }
            $definition->addArgument(new Reference($serviceName));
        }
    }

    /**
     * Return the list of additional qualifiers for parameters
     *
     * @param ReflectionMethod $ctor
     * @return Qualifier[]
     */
    private function getQualifiers(ReflectionMethod $ctor)
    {
        $qualifiers  = array();
        $annotations = $this->reader->getMethodAnnotations($ctor);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Qualifier) {
                $qualifiers[$annotation->value] = $annotation;
            }
        }
        return $qualifiers;
    }

}