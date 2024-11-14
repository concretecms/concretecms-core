<?php

namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Entity\Package;
use SimpleXMLElement;

class ImportConfigValuesRoutine extends AbstractRoutine
{
    private $repositoryInstances;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Backup\ContentImporter\Importer\Routine\RoutineInterface::getHandle()
     */
    public function getHandle()
    {
        return 'config_values';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Backup\ContentImporter\Importer\Routine\RoutineInterface::import()
     */
    public function import(SimpleXMLElement $sx)
    {
        if (!isset($sx->config)) {
            return;
        }
        $this->repositoryInstances = [];
        foreach ($sx->config->children() as $key) {
            $package = isset($key['package']) ? static::getPackageObject($key['package']) : null;
            $node = (string) $key->getName();
            if ($node !== 'option') {
                // legacy
                $key = $node;
            } else {
                $key = (string) $key['name'];
            }
            $value = (string) $key;
            if ($value === 'false') {
                $value = false;
            }
            $rawOverwrite = isset($key['overwrite']) ? (string) $key['overwrite'] : '';
            $overwrite = $rawOverwrite === '' ? true : filter_var($rawOverwrite, FILTER_VALIDATE_BOOLEAN);
            $repository = $this->getRepository(isset($key['storage']) ? (string) $key['storage'] : '', $package);
            if ($overwrite || !$repository->has($key)) {
                $repository->set($key, $value);
                $repository->save($key, $value);
            }
        }
    }

    /**
     * @return \Concrete\Core\Config\Repository\Repository|\Concrete\Core\Config\Repository\Liaison
     */
    private function getRepository(string $storage, ?Package $package)
    {
        switch ($storage) {
            case 'database':
                break;
            default:
                $storage = 'file';
        }
        $key = $storage . ($package ? "@{$package->getPackageHandle()}" : '');
        if (isset($this->repositoryInstances[$key])) {
            return $this->repositoryInstances[$key];
        }
        switch ($storage) {
            case 'database':
                $repository = $package ? $package->getController()->getDatabaseConfig() : app('config/database');
                break;
            case 'file':
                $repository = $package ? $package->getController()->getFileConfig() : app('config');
        }
        $this->repositoryInstances[$key] = $repository;

        return $repository;
    }
}
