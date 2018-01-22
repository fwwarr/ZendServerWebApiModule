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

    /**
     * Create APIManager as a service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService (ServiceLocatorInterface $serviceLocator)
    {
        return new ApiManager(
            $serviceLocator->get('log'),
            $serviceLocator->get('defaultApiKey'),
            $serviceLocator->get('targetZendServer'),
            $serviceLocator->get('zendServerClient'),
            $serviceLocator->get('config')
        );
    }
}
