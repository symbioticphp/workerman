<?php

namespace Symbiotic\Workerman;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Autoload\Autoloader;
use Symbiotic\Core\Core;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\Events\CacheClear;
use Symbiotic\Core\HttpKernelInterface;
use Symbiotic\Develop\Services\Debug\Timer;
use Symbiotic\Workerman\Http\Psr7\PsrRequestFactory;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

use function _S\listen;

class Worker extends \Workerman\Worker
{

    /**
     * @var null | CoreInterface
     */
    protected ?ContainerInterface $container;
    /**
     * @var null | HttpKernelInterface|RequestHandlerInterface
     */
    protected RequestHandlerInterface|null $httpKernel = null;


    private ?PsrRequestFactory $request_factory;

    public function __construct($socket_name = '', array $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
        $this->onMessage = [$this, 'onMessage'];
    }

    public function setContainer(CoreInterface $container)
    {
        $this->container = $container;
    }

    /**
     * start
     */
    public function start()
    {
        // gc_disable();
        $this->container = $this->createNewCore();


        $this->request_factory = $this->container->make(PsrRequestFactory::class);

        \Workerman\Worker::runAll();
    }

    /**
     * @return CoreInterface
     */
    public function getCurrentContainer(): CoreInterface
    {
        return $this->container;
    }

    /**
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function reloadContainer(): void
    {
        $this->container = $this->createNewCore();
    }

    /**
     * @return CoreInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function createNewCore(): CoreInterface
    {
        $config = $this->container->get('bootstrap_config');
        if (!isset($config['providers_exclude'])) {
            $config['providers_exclude'] = [];
        }

        $container = new Core($config);
        $container['base_uri'] = '/';
        $container->get(HttpKernelInterface::class)->bootstrap();


        // Build all singletons
        foreach ($container->getBindings() as $abstract => $bind) {
            if ($bind['shared'] === true) {
                $container->resolve($abstract);
            }
        }
        // Rebuilding the container when clearing the cache
        listen($container, CacheClear::class, function (CacheClear $event) {
            $this->reloadContainer();
            if (class_exists(Autoloader::class)) {
                Autoloader::registerNamespaces();
            }
        });

        $container->clearLive();

        return $container;
    }

    /**
     * @param TcpConnection $connection
     * @param Request       $workermanRequest
     *
     * @return null
     */
    public function onMessage(TcpConnection $connection, Request $workermanRequest): ?bool
    {
        try {
            $container = clone $this->container;
            $container->clearLive();

            $request = $this->request_factory
                ->createByWorkermanRequest($workermanRequest, $this->transport === 'ssl');

            $response = $container->get(HttpKernelInterface::class)->handle($request);

            $connection->send(
                (new Response())
                    ->withStatus($response->getStatusCode(), $response->getReasonPhrase())
                    ->withHeaders($response->getHeaders())
                    ->withBody((string)$response->getBody())
            );
            unset($container);
            return true;
        } catch (\Throwable $e) {
            $connection->send(new Response(500, [], (string)$e));
        }
        return null;
    }
}