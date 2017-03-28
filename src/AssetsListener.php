<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Interop\Container\ContainerInterface;
use Zend\Http\Response\Stream as StreamResponse;
use Zend\Mvc\MvcEvent;
use Zend\View\Assets\Exception as AssetsException;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Exception;

class AssetsListener extends AbstractListenerAggregate
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $routeName = 'assets';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 20);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'injectRouter'], 10000);
    }

    public function injectRouter(MvcEvent $event)
    {
        $routeName = explode('/', $this->getRouteName());
        $event->getRouter()->addRoute(
            end($routeName),
            $this->getAssetsManager()->getAssetsRouter(),
            10000
        );
    }

    /**
     * @param MvcEvent $event
     * @return string|\Zend\Http\Response
     * @throws Exception\InvalidArgumentException
     */
    public function onDispatch(MvcEvent $event)
    {
        $match = $event->getRouteMatch();
        if (!$match || !$match->getParam('source')) {
            return;
        }

        try {
            $asset = $this->getAssetsManager()->getPreparedAsset(
                $match->getParam('collection'),
                $match->getParam('ns'),
                $match->getParam('source')
            );
            $content = $asset->getTargetContent();
            if (is_string($content)) {
                $response = $event->getResponse();
                $response->setContent($content);
                $contentLength = function_exists('mb_strlen')
                        ? mb_strlen($content, '8bit')
                        : strlen($content);
            } else {
                if (is_resource($content)) {
                    $handle = $asset->getTargetContent();
                } elseif (file_exists($asset->getTargetUri())) {
                    $handle = fopen($asset->getTargetUri(), 'r');
                } else {
                    $handle = fopen($asset->getSourceUri(), 'r');
                }

                $response = new StreamResponse();
                $response->setStream($handle);
                $contentLength = fstat($handle)['size'];
            }

            $response->getHeaders()->clearHeaders()->addHeaders([
                'Content-Transfer-Encoding' => 'binary',
                'Content-Type' => $asset->getMimeType(),
                'Content-Length' => $contentLength,
            ]);
            return $response;
        } catch (AssetsException\NotFoundException $exc) {
            return $event->getResponse()
                            ->setStatusCode(404)
                            ->setContent($exc->getMessage());
        } catch (\Exception $exc) {
            return $event->getResponse()
                    ->setStatusCode(500)
                    ->setContent($exc->getMessage());
        }
    }

    /*protected function getRequestedAssetUri(MvcEvent $event)
    {
        return substr($event->getRequest()->getRequestUri(), strlen($event->getRequest()->getBasePath()));
    }*/

    /**
     * @return AssetsManager
     */
    public function getAssetsManager()
    {
        if (!$this->assetsManager) {
            $this->assetsManager = $this->container->get('AssetsManager');
        }
        return $this->assetsManager;
    }

    /**
     * @param AssetsManager $assetsManager
     * @return self
     */
    public function setAssetsManager(AssetsManager $assetsManager)
    {
        $this->assetsManager = $assetsManager;
        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     * @return self
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     * @return self
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
        return $this;
    }
}
