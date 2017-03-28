<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Mvc\Service;

use Zend\View\Assets\Service\AssetsRouterFactory as BaseAssetsRouterFactory;
use Zend\View\Assets\Mvc\AssetsRouter;

class AssetsRouterFactory extends BaseAssetsRouterFactory
{
    protected $assetsClass = AssetsRouter::class;
}
