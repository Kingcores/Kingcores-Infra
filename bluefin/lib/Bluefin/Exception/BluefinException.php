<?php

namespace Bluefin\Exception;

class BluefinException extends \Exception
{
    public function __construct($message, $code = \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR, \Exception $previousException = null)
    {
        parent::__construct($message ? $message : \Bluefin\Common::getStatusCodeMessage($code), $code, $previousException);
    }

    public function sendHttpResponse()
    {
        ob_end_clean();
        header("HTTP/1.1 " . $this->getCode() . ' ' . \Bluefin\Common::getStatusCodeMessage($this->getCode()), true);
        header("Content-Type: application/json;charset=utf-8", true);
        header("Cache-Control: no-store", true);
        if (RENDER_EXCEPTION)
        {
            echo json_encode($this->getMessage() . $this->getTraceAsString());
        }
        else
        {
            echo json_encode($this->getMessage());
        }
        exit();
    }
}