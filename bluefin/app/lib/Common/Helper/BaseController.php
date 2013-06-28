<?php

namespace Common\Helper;

use Bluefin\App;
use Bluefin\Controller;
use Bluefin\Convention;

use Common\Data\Event;

class BaseController extends Controller
{
    protected function _init()
    {
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->_view->set('title', _APP_(implode('.', $this->_gateway->getLocationStack())));

        $event = $this->_request->getQueryParam('_event', null, true);

        if (isset($event)) {
            $params = $this->_request->getQueryParam('_param', null, true);
            if (isset($params)) {
                $params = json_decode(base64_decode($params), true);
            }
            $eventMessage = Event::getMessage($event, $params);

            $this->_view->set('_eventMessage', $eventMessage);
            $this->_view->set('_eventAlertClass', Event::getLevelAlertClass($event));
        }
    }

    public function postDispatch()
    {
        parent::postDispatch();

        if (isset($this->_requestSource)) {
            $this->_view->set('from', $this->_requestSource);
        }
    }

    protected function _showEventMessage($code, $source = Event::SRC_COMMON, $level = Event::LEVEL_ERROR, array $params = null)
    {
        $eventCode = Event::make($level, $source, $code);

        $this->changeView('WBT/Error.message.html');

        isset($title) || ($title = _DICT_('error'));

        $this->_view->set('title', $title);
        $this->_view->set('message', Event::getMessage($eventCode, $params));

        throw new \Bluefin\Exception\SkipException();
    }

    protected function _redirectWithEvent($event, array $eventParams = null, $toUrl = null, array $otherParams = null)
    {
        isset($toUrl) || ($toUrl = $this->_requestSource);

        if (!isset($toUrl)) {
            throw new \Bluefin\Exception\InvalidOperationException('Nowhere to redirect!');
        }

        isset($otherParams) || ($otherParams = []);

        $otherParams[Convention::KEYWORD_REQUEST_EVENT] = $event;

        if (isset($eventParams)) {
            $otherParams[Convention::KEYWORD_REQUEST_PARAMS] = base64_encode(json_encode($eventParams));
        }

        if (is_abs_url($toUrl))
        {
            $toUrl = build_uri($toUrl, $otherParams, null);
        }
        else
        {
            $toUrl = $this->_gateway->url($toUrl, $otherParams, null);
        }

        $this->_gateway->redirect($toUrl);
    }

    protected function _checkRequiredInput($name, $value)
    {
        if (!isset($value) || $value == '')
        {
            $this->_setEventMessage(Event::E_MISSING_ARGUMENT, Event::SRC_COMMON, Event::LEVEL_ERROR, ['%name%' => $name]);
            return false;
        }

        return true;
    }

    protected function _setEventMessage($code, $source = Event::SRC_COMMON, $level = Event::LEVEL_ERROR, array $params = null)
    {
        $eventCode = Event::make($level, $source, $code);
        $this->_view->set('_eventMessage', Event::getMessage($eventCode, $params));
        $this->_view->set('_eventAlertClass', Event::getLevelAlertClass($eventCode));
    }
}
