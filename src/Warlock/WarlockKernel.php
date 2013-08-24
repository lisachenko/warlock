<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Warlock\DependencyInjection\Compiler\AspectCollectorPass;
use Warlock\DependencyInjection\Compiler\ComponentScannerPass;
use Warlock\DependencyInjection\Compiler\InterfaceBinderPass;

/**
 * Class WarlockKernel is responsible to initialize AOP and DIC component
 */
class WarlockKernel extends AspectKernel
{

    /**
     * Default class name for container, can be redefined in children
     *
     * @var string
     */
    protected static $containerClass = 'AspectServiceContainer';

    /**
     * {@inheritdoc}
     */
    public function init(array $options = array())
    {
        $file = rtrim($options['cacheDir'], '/') . '/AspectServiceContainer.php';

        $containerConfigCache = new ConfigCache($file, !empty($options['debug']));

        if (!$containerConfigCache->isFresh()) {

            $container = new ContainerBuilder();
            $loader    = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
            $loader->load('components.xml');
            $loader->load('aspect.xml');
            $loader->load('demo_aspects.xml'); // TODO: Remove this hardcoded example

            $container->addCompilerPass(new ComponentScannerPass($options['appDir'] . '/src'));
            $container->addCompilerPass(new InterfaceBinderPass());
            $container->addCompilerPass(new AspectCollectorPass());
            $container->setParameter('kernel.interceptFunctions', !empty($options['interceptFunctions']));
            $container->compile();

            $dumper = new PhpDumper($container);
            $containerConfigCache->write(
                $dumper->dump(array(
                    'base_class' => 'Warlock\WarlockContainer',
                    'class'      => static::$containerClass
                )),
                $container->getResources()
            );
        }
        require_once $file;

        parent::init($options);
        $this->container->addResource($file);
    }

    /**
     * Configure an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer|Container $container
     *
     * @return void
     */
    final protected function configureAop(AspectContainer $container)
    {
        $aspectIds = $container->getParameter('aspect.list');
        foreach ($aspectIds as $aspectId) {
            $container->registerAspect($container->get($aspectId));
        }
    }
}