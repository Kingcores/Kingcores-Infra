<?php

namespace Bluefin\Util;

class TwigVarHelper
{
    private $_path;

    public function __construct($path = null)
    {
        $this->_path = $path;
    }

    public function __call($name, $arguments)
    {
        if (isset($this->_path))
        {
            $this->_path .= '.' . $name;

            return $this;
        }

        return new TwigVarHelper($name);
    }

    public function _()
    {
        \Bluefin\App::assert(isset($this->_path));

        return _C($this->_path);
    }

    public function __toString()
    {
        return isset($this->_path) ? _C($this->_path, '') : '';
    }
}
