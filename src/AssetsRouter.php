<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Mvc;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Router\RouteInterface;
use Zend\Router\Http\RouteMatch;
use Zend\View\Assets\AssetsRouter as BaseAssetsRouter;
use Zend\View\Assets\Exception;

class AssetsRouter extends BaseAssetsRouter implements RouteInterface
{
    public function match(Request $request, $pathOffset = null)
    {
        if (!method_exists($request, 'getUri')) {
            return;
        }

        $path = $request->getUri()->getPath();
        $match = parent::match($path, $pathOffset);
        if (!$match) {
            return;
        }
        return new RouteMatch($match, strlen($path));
    }

    public function getAssembledParams()
    {
        return [];
    }

    public static function factory($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable set of options',
                __METHOD__
            ));
        }
        $router = new static();

        if (isset($options['router_prefix'])) {
            $router->setPrefix($options['router_prefix']);
        }
        if (isset($options['base_path'])) {
            $router->setBasePath($options['base_path']);
        }
        if (isset($options['assets_manager'])) {
            $router->setAssetsManager($options['assets_manager']);
        }
        return $router;
    }
}
