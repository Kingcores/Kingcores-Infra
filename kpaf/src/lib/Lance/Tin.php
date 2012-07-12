<?php

namespace Bluefin\Lance;

use Bluefin\App;

class TinManifest
{
    private $_tinFolder;
    private $_manifest;
    private $_manifestFile;

    public function __construct($tinFolder)
    {
        $this->_tinFolder = $tinFolder;

        $this->_manifestFile = build_path($this->_tinFolder, 'manifest.yml');

        if (!file_exists($this->_manifestFile))
        {
            throw new \Bluefin\Exception\FileNotFoundException($this->_manifestFile);
        }

        $this->_manifest = App::loadYmlFileEx($this->_manifestFile);

        $this->_validateManifest();
    }

    public function getName()
    {
        return $this->_manifest['tin']['name'];
    }

    public function getComment()
    {
        return $this->_manifest['tin']['comment'];
    }

    public function getVersion()
    {
        return $this->_manifest['tin']['version'];
    }

    public function setup(System $system)
    {
        $this->_checkRequirements($system);

        if (!empty($this->_manifest['schema']))
        {
            //foreach ()
        }
    }

    protected function _checkRequirements(System $system)
    {

    }

    protected function _validateManifest()
    {
        if (!isset($this->_manifest['tin']))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Missing tin block in {$this->_manifestFile}");
        }

        $tin = $this->_manifest['tin'];

        if (!all_keys_exists($tin, array('name','comment','version')))
        {
            throw new \Bluefin\Lance\Exception\GrammarException("Missing tin.name or tin.comment or tin.version in {$this->_manifestFile}");
        }
    }
}
