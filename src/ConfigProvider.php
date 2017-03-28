<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Mvc;

class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories'  => [
                'AssetsListener' => Service\AssetsListenerFactory::class,
                'AssetsRouter'   => Service\AssetsRouterFactory::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getListenersConfig()
    {
        return [
            'AssetsListener',
        ];
    }
}
