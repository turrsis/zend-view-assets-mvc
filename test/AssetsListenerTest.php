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
use Zend\Http\PhpEnvironment\Response as ContentResponse;
use Zend\Http\Response\Stream as StreamResponse;
use Zend\Router\RouteMatch;
use Zend\Mvc\MvcEvent;
use Zend\View\Assets\Mvc\AssetsListener;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Asset\Asset;
use Zend\View\Assets\Exception;
use Zend\View\Assets\Mvc\AssetsRouter;

class AssetsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsListener
     */
    protected $assetsListener;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var MvcEvent
     */
    protected $mvcEvent;

    public function setUp()
    {
        $serviceManager = new ServiceManager([
            'services' => [
                'AssetsRouter'  => new AssetsRouter,
                'Request'       => new Request(),
            ],
            'factories' => [
                'Router'             => 'Zend\Router\RouterFactory',
                'RoutePluginManager' => 'Zend\Router\RoutePluginManagerFactory',
            ],
            'allow_override' => true,
        ]);

        $assetsManager = $this->getMock(AssetsManager::class, ['getPreparedAsset'], [$serviceManager], '', true);
        $assetsManager->method('getPreparedAsset')->will($this->returnCallback(function($alias, $ns, $name, $targetUri = null) {
            return $name;
        }));

        $serviceManager->setService('AssetsManager', $assetsManager);
        $this->mvcEvent = new MvcEvent;
        $this->mvcEvent->setRequest(new Request());
        $this->mvcEvent->setResponse(new ContentResponse);
        
        $this->assetsListener = new AssetsListener($serviceManager);
    }

    public function testInjectRouter()
    {
        $event = new MvcEvent;
        $event->setRouter($this->assetsListener->getContainer()->get('Router'));

        $routeName = $this->assetsListener->getRouteName();

        $this->assertFalse($event->getRouter()->hasRoute($routeName));        
        $this->assetsListener->injectRouter($event);
        $this->assertTrue($event->getRouter()->hasRoute($routeName));
    }

    protected function dispatchAsset($asset)
    {
        $this->mvcEvent->setRouteMatch((new RouteMatch([
            'source'  => $asset,
        ]))->setMatchedRouteName('assets'));
        return $this->assetsListener->onDispatch($this->mvcEvent);
    }

    public function testDispatchNothing()
    {
        $this->mvcEvent->setRouteMatch((new RouteMatch([])));
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertNull($response);
    }

    public function testDispatchStringContent()
    {
        $response = $this->dispatchAsset(new Asset([
            'source' => 'foo.css',
            'mime_type' => 'text/css',
            'target_content' => 'content-foo.css-content',
        ]));

        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('content-foo.css-content', $response->getContent());
        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 23,
        ], $response->getHeaders()->toArray());
    }

    public function testDispatchStreamContent()
    {
        $handle = fopen(__FILE__, 'r');

        $response = $this->dispatchAsset(new Asset([
            'source' => 'foo.css',
            'mime_type' => 'text/css',
            'target_content' => $handle,
        ]));

        $this->assertInstanceOf(StreamResponse::class, $response);
        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => fstat($handle)['size'],
        ], $response->getHeaders()->toArray());        
        $this->assertSame($handle, $response->getStream());

        fclose($handle);
    }

    public function testDispatchStreamContent_ByTargetUri()
    {
        $response = $this->dispatchAsset(new Asset([
            'source' => 'foo.css',
            'mime_type' => 'text/css',
            'target_uri' => __FILE__,
        ]));

        $this->assertInstanceOf(StreamResponse::class, $response);
        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => filesize(__FILE__),
        ], $response->getHeaders()->toArray());        

        $this->assertEquals(
            __FILE__,
            stream_get_meta_data($response->getStream())["uri"]
        );
    }

    public function testDispatchStreamContent_BySourceUri()
    {
        $response = $this->dispatchAsset(new Asset([
            'source' => 'foo.css',
            'mime_type' => 'text/css',
            'source_uri' => __FILE__,
        ]));

        $this->assertInstanceOf(StreamResponse::class, $response);
        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => filesize(__FILE__),
        ], $response->getHeaders()->toArray());        

        $this->assertEquals(
            __FILE__,
            stream_get_meta_data($response->getStream())["uri"]
        );
    }

    public function testDispatch404()
    {
        $this->assetsListener->getAssetsManager()->method('getPreparedAsset')->will($this->returnCallback(function() {
            throw new Exception\NotFoundException('something not found');
        }));

        $response = $this->dispatchAsset(new Asset('foo.css'));

        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('something not found', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }

    public function testDispatch500()
    {
        $this->assetsListener->getAssetsManager()->method('getPreparedAsset')->will($this->returnCallback(function() {
            throw new \Exception('something fatal wrong');
        }));

        $response = $this->dispatchAsset(new Asset('foo.css'));

        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('something fatal wrong', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }

    public function testDispatch500WrongStreamContent()
    {
        $response = $this->dispatchAsset(new Asset('foo.css'));

        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('fopen(): Filename cannot be empty', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }
}
