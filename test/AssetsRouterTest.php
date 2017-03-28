<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Mvc;

use Zend\ServiceManager\ServiceManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Asset;

class AssetsRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function setUp()
    {
        $this->serviceManager = new ServiceManager([
            'services' => [
                'config'  => [
                    'assets_manager' => []
                ],
                'Request' => new Request(),
            ],
            'factories' => [
                'AssetsRouter'       => 'Zend\View\Assets\Mvc\Service\AssetsRouterFactory',
                'Router'             => 'Zend\Router\RouterFactory',
                'RoutePluginManager' => 'Zend\Router\RoutePluginManagerFactory',
            ],
        ]);
        $this->serviceManager->setService('AssetsManager', new AssetsManager($this->serviceManager));
        $this->serviceManager->setAllowOverride(true);
    }

    public function testMatch()
    {
        $router = $this->serviceManager->get('AssetsRouter');
        $request = new Request();
        $request->setUri('/assets/collection-foo/ns-bar/css/baz.css');
        $this->assertEquals([
            'collection' => 'foo',
            'ns'         => 'bar',
            'source'     => 'css/baz.css',
        ], $router->match($request)->getParams());

        $request->setUri('/assets/ns-bar/css/baz.css');
        $this->assertEquals([
            'collection' => null,
            'ns'         => 'bar',
            'source'     => 'css/baz.css',
        ], $router->match($request)->getParams());

        $request->setUri('/assets/collection-foo/css/baz.css');
        $this->assertEquals([
            'collection' => 'foo',
            'ns'         => null,
            'source'     => 'css/baz.css',
        ], $router->match($request)->getParams());

        $request->setUri('/assets/css/baz.css');
        $this->assertEquals([
            'collection' => null,
            'ns'         => null,
            'source'     => 'css/baz.css',
        ], $router->match($request)->getParams());

        $request->setUri('/other_path');
        $this->assertNull($router->match($request));
    }

    public function testAssemble()
    {
        $router = $this->serviceManager->get('AssetsRouter');        

        $this->assertEquals(
            'http://com.com/css/bar.css',
            $router->assemble(['source' => new Asset\Asset('http://com.com/css/bar.css')])
        );

        $this->assertEquals(
            '/css/bar.css',
            $router->assemble(['source' => new Asset\Asset('css/bar.css')])
        );

        $this->assertEquals(
            '/assets/ns-foo/css/bar.css',
            $router->assemble(['source' => new Asset\Asset('foo::css/bar.css')])
        );
    }
}
