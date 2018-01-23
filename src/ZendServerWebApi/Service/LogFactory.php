<?php
namespace ZendServerWebApi\Service;
use Interop\Container\ContainerInterface;
use Zend\Log\Filter\Priority;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API Manager Factory
 */
class LogFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $log = new Logger();
        $logWriter = new Stream($config['zsapilog']['file']);
        $log->addWriter($logWriter);
        
        if(!empty($config['zsapilog']['priority'])) {
            $filter = new Priority($config['zsapilog']['priority']);
            $logWriter->addFilter($filter);
        }
        return $log;
    }
    /**
     * Create APIManager as a service
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService (ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator);
    }
}
