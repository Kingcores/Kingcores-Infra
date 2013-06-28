<?php

namespace Bluefin\HTML;

class FileUpload extends SimpleComponent
{
    protected $_supportUrl;
    protected $_imageUpload;
    protected $_uploadID;
    protected $_name;

    public function __construct(array $attributes = null)
    {
        parent::__construct($attributes);

        $this->_supportUrl = array_try_get($this->attributes, 'url', false, true);
        $this->_imageUpload = array_try_get($this->attributes, 'image', true, true);

        $action = array_try_get($this->attributes, 'action', null, true);
        $fileUpload = $this->_view->get('_fileUpload');
        isset($fileUpload) || [$fileUpload = []];
        $this->_uploadID = array_try_get($this->attributes, Form::FIELD_ID, null, true);
        $this->_name = array_try_get($this->attributes, Form::FIELD_NAME, null, true);

        if (!isset($this->_uploadID))
        {
            throw new \Bluefin\Exception\InvalidOperationException("Missing id attribute!");
        }

        $fileUpload[$this->_uploadID] = $action;
        $this->_view->set('_fileUpload', $fileUpload);
    }

    public function _renderContent()
    {
        $btns = new ButtonGroup(null, ButtonGroup::TYPE_REGULAR);
        if ($this->_supportUrl)
        {
            $btns->addComponent(new Button('<i class="icon-share"></i>', null));
        }
        if ($this->_imageUpload)
        {
            $btns->addComponent(new Button('<i class="icon-picture"></i>', "javascript:$('#{$this->_uploadID}Up').trigger('click');"));
        }

        return '<div id="'.$this->_uploadID.'UpC">' . $btns->__toString() . '<div class="progress progress-success progress-striped hide"><div id="'. $this->_uploadID .'UpP" class="bar" style="width: 0%;"></div></div><input id="' . $this->_uploadID . '" name="'. $this->_name .'" type="hidden"></div>';

        /*
        return '<div class="input-append customfile"><span class="customfile-feedback ' . $class . '" aria-hidden="true" style="margin-left:0;">'. _VIEW_('No file selected...') . '</span><span class="add-on customfile-button" aria-hidden="true" style="float: right; ">' . _VIEW_('Browse') . '</span></div><input type="file" class="' . $class . ' customfile-input" name="' . $name . '" id="' . $id . '">';
        */
    }
}
