<?php
namespace ZendServerWebApi\Service;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZendServerWebApi\Model\ApiManager;

/**
 * API Manager Factory
 */
class ApiManagerFactory implements FactoryInterface
{

    public function __invoke($container, $requestedName)
    {
        return new ApiManager(
            $container->get('log'),
            $container->get('defaultApiKey'),
            $container->get('targetZendServer'),
            $container->get('zendServerClient'),
            $container->get('config')
        );
    }

    /**
     * Create APIManager as a service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService (ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator);
    }
}
