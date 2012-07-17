<?php

namespace Bluefin;

class View
{
    private $_options;
    private $_data;

    public function __construct(array $viewOptions = array())
    {
        $this->_data = array();
        $this->resetOptions($viewOptions);
    }

    public function __set($key, $val)
    {
        $this->_data[$key] = $val;
    }

    public function __get($key)
    {
        return array_try_get($this->_data, $key);
    }

    public function set($key, $val)
    {
        $this->__set($key, $val);
    }

    public function get($key)
    {
        return $this->__get($key);
    }

    public function getData()
    {
        $dataSource = $this->getOption('dataSource');
        if (isset($dataSource))
        {
            return array_try_get($this->_data, $dataSource, array());
        }
        
        return $this->_data;
    }

     public function resetData($data = array())
    {
        return $this->_data = $data;
    }

    public function resetOptions(array $options)
    {
        $this->_options = $options;
    }

    public function setOption($option, $value)
    {            
        $this->_options[$option] = $value;
    }

    public function getOption($option, $default = null)
    {
        return array_try_get($this->_options, $option, $default);
    }

    public function render()
    {        
        $renderer = array_try_get($this->_options, 'renderer', Convention::DEFAULT_VIEW_RENDERER);

        if ($renderer == Convention::DEFAULT_VIEW_RENDERER)
        {
            $functions = array_try_get($this->_options, 'functions', array());
            $functions['config'] = '_C';
            $functions['text'] = '_T';
            $functions['url'] = '_U';
            $functions['route'] = '_R';
            $functions['yml'] = '_Y';

            $this->setOption('functions', $functions);
            $this->set('_locale_', App::getInstance()->currentLocale());
            $this->set('_elapsed_', App::getInstance()->end() * 1000);
            $this->set('_www_', App::getInstance()->basePath());
        }

        try
        {
            $rendererClass = '\\Bluefin\\Renderer\\' . strtoupper($renderer) . 'Renderer';
            $rendererObject = new $rendererClass;
        }
        catch (\Exception $e)
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unsupported view renderer: {$renderer}");
        }

        return $rendererObject->render($this);
    }
}
