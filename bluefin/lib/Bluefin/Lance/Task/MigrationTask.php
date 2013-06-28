<?php

namespace Bluefin\Lance\Task;

use Bluefin\App;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Arsenal;

class MigrationTask extends TaskBase implements TaskInterface
{
    protected $_currentVer;
    protected $_targetVer;

    protected $_currentVerDir;
    protected $_targetVerDir;

    protected $_targetVerSourceDir;

    protected $_schemaConfig;

    public function execute($params)
    {
        $verFile = APP . '/ver.lock';

        if (!file_exists($verFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($verFile);
        }

        $this->_currentVer = trim(file_get_contents($verFile));
        $currentVerNum = $this->_currentVer;

        $this->_targetVer = trim($params);
        $targetVerNum = $this->_targetVer;

        if ($targetVerNum <= $currentVerNum)
        {
            throw new \Bluefin\Exception\InvalidOperationException("Cannot migrate the database from {$this->_currentVer} to {$this->_targetVer}.");
        }

        $patchFile = LANCE . "/patch/{$this->_currentVer}-{$this->_targetVer}.yml";

        if (!file_exists($patchFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($patchFile);
        }

        $patchConfig = \Symfony\Component\Yaml\Yaml::load($patchFile);

        $this->_targetVerSourceDir = LANCE . "/patch/{$this->_currentVer}-{$this->_targetVer}";
        $this->_targetVerDir = LANCE . "/versions/{$this->_targetVer}";
        $this->_currentVerDir = LANCE . "/versions/{$this->_currentVer}";

        if (!is_dir($this->_currentVerDir))
        {
            throw new \Bluefin\Exception\FileNotFoundException($this->_currentVerDir);
        }

        if (is_dir($this->_targetVerDir))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Target version already exists!");
        }

        $report = [];

        $this->_copyDir($this->_currentVerDir, $this->_targetVerDir, $report);

        $this->_logReport($report);

        foreach ($patchConfig as $patchName => $patchItems)
        {
            Arsenal::getInstance()->log()->info("Parsing '{$patchName}' patch config...", 'report');

            foreach ($patchItems as $patchItem)
            {
                foreach ($patchItem as $patchOp => $opInfo)
                {
                    foreach ($opInfo as $opTarget => $opData)
                    {
                        $method = '_' . usw_to_camel("{$patchOp}_{$opTarget}");

                        $this->$method($opData);
                    }
                }
            }
        }
    }

    protected function _addSchema($data)
    {
        is_array($data) || ($data = [ $data ]);

        foreach ($data as $schemaFile)
        {
            $this->_copyFileIfNotExist($this->_targetVerSourceDir . "/schema/{$schemaFile}", $this->_targetVerDir . "/schema/{$schemaFile}");

            \Bluefin\Lance\Arsenal::getInstance()->log()->info("Added schema: {$schemaFile}", 'report');
        }
    }

    protected function _addEntityUsage($data)
    {
        is_array($data) || ($data = [ $data ]);

        foreach ($data as $schema => $usageItems)
        {
            $entities = [];

            foreach ($usageItems as $codeName => $entityName)
            {
                $entities[$codeName] = $entityName;
            }

            $schemaFile = $this->_targetVerDir . "/{$schema}.*.yml";

            $files = glob($schemaFile, GLOB_ERR);

            foreach ($files as $file)
            {
                $schemaConfig = \Symfony\Component\Yaml\Yaml::load($file);
                $schemaConfig[$schema]['entities'] = array_merge($schemaConfig[$schema]['entities'], $entities);
                $newSchema = \Symfony\Component\Yaml\Yaml::dump($schemaConfig, 4);

                file_put_contents($file, $newSchema, LOCK_EX);
            }

            \Bluefin\Lance\Arsenal::getInstance()->log()->info("Added entity usage to '{$schema}'.", 'report');
        }
    }

    protected function _addEntityMember($data)
    {
        foreach ($data as $entity => $members)
        {
            list($entitySet, $entityName) = explode('.', $entity);

            if (empty($entitySet) || empty($entityName))
            {
                throw new \Bluefin\Exception\ConfigException("Invalid entity: {$entity}!");
            }

            $entitySetFile = $this->_targetVerDir . "/schema/{$entitySet}.yml";

            if (!file_exists($entitySetFile))
            {
                throw new \Bluefin\Exception\FileNotFoundException($entitySetFile);
            }

            $add = [ $entityName => [ 'has' => $members ] ];

            $entitySetConfig = \Symfony\Component\Yaml\Yaml::load($entitySetFile);
            $entitySetConfig = array_merge_recursive($entitySetConfig, $add);
            $newEntitySet = \Symfony\Component\Yaml\Yaml::dump($entitySetConfig, 4);

            file_put_contents($entitySetFile, $newEntitySet, LOCK_EX);

            \Bluefin\Lance\Arsenal::getInstance()->log()->info("Added entity members to '{$entity}'.", 'report');

            //Arsenal::getInstance()->loadSchemaSet()
        }
    }
}
