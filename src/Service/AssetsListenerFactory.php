<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Assets\Mvc\AssetsListener;

class AssetsListenerFactory implements FactoryInterface
{
    /**
     * Create the assets listener.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return AssetsListener
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $listener = new AssetsListener($container);

        $config = $container->get('config');
        if (isset($config['assets_manager']['router_name'])) {
            $listener->setRouteName($config['assets_manager']['router_name']);
        }
        return $listener;
    }

    /**
     * @param ServiceLocatorInterface $container
     * @return AssetsListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AssetsListener::class);
    }
}
