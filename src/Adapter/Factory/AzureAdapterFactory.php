<?php

declare(strict_types=1);

namespace BsbFlysystem\Adapter\Factory;

use BsbFlysystem\Exception\RequirementsException;
use BsbFlysystem\Exception\UnexpectedValueException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Azure\AzureAdapter as Adapter;
use WindowsAzure\Common\ServicesBuilder;
use Zend\ServiceManager\ServiceLocatorInterface;

class AzureAdapterFactory extends AbstractAdapterFactory
{
    public function doCreateService(ServiceLocatorInterface $serviceLocator): AdapterInterface
    {
        if (! class_exists(\League\Flysystem\Azure\AzureAdapter::class)) {
            throw new RequirementsException(
                ['league/flysystem-azure'],
                'Azure'
            );
        }
        $endpoint      = sprintf(
            'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
            $this->options['account-name'],
            $this->options['account-key']
        );

        $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($endpoint);
        $adapter       = new Adapter($blobRestProxy, $this->options['container']);

        return $adapter;
    }

    protected function validateConfig()
    {
        if (! isset($this->options['account-name'])) {
            throw new UnexpectedValueException("Missing 'account-name' as option");
        }

        if (! isset($this->options['account-key'])) {
            throw new UnexpectedValueException("Missing 'account-key' as option");
        }

        if (! isset($this->options['container'])) {
            throw new UnexpectedValueException("Missing 'container' as option");
        }
    }
}
