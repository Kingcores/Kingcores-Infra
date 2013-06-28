<?php

namespace Bluefin\HTML;

use Bluefin\View;

abstract class Component extends SimpleComponent
{
    /**
     * @var View
     */
    protected $_thisView;
    public $template;

    public function __construct(array $attributes = null)
    {
        parent::__construct($attributes);

        $this->template = 'default';
    }

    public function getView()
    {
        return $this->_view;
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        $this->_thisView = $this->_createView('.html');
    }

    protected function _renderContent()
    {
        $this->_thisView->set('component', $this);

        return $this->_thisView->render();
    }

    protected function _createView($suffix = '')
    {
        $templateName = "view/{$this->_type}/{$this->template}{$suffix}.twig";
        $template = APP . '/' . $templateName;
        if (file_exists($template))
        {
            $root = APP;
        }
        else
        {
            $root = BLUEFIN . '/HTML';
        }

        return new View(
            [
                'root' => $root,
                'template' => $templateName
            ]
        );
    }
}
