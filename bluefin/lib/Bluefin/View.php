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

    public function appendData(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
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

    public function appendOption($option, $value)
    {
        if (isset($this->_options[$option]))
        {
            if (is_array($value))
            {
                $this->_options[$option] = array_merge($this->_options[$option], $value);
            }
            else
            {
                array_push_unique($this->_options[$option], $value);
            }
        }
        else
        {
            is_array($value) || ($value = [$value]);
            $this->_options[$option] = $value;
        }
    }

    public function render()
    {        
        $renderer = array_try_get($this->_options, 'renderer', Convention::DEFAULT_VIEW_RENDERER);

        try
        {
            $rendererClass = '\\Bluefin\\Renderer\\' . strtoupper($renderer) . 'Renderer';
            $rendererObject = new $rendererClass;
        }
        catch (\Exception $e)
        {
            throw new \Bluefin\Exception\InvalidOperationException("Unsupported view renderer: {$renderer}");
        }

        /**
         * @var \Bluefin\Renderer\RendererInterface $rendererObject
         */

        return $rendererObject->render($this);
    }
}
