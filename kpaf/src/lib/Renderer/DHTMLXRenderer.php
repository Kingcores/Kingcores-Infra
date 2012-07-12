<?php

namespace Bluefin\Renderer;

use Bluefin\App;
use Bluefin\View;
use Bluefin\Data\Database;

class DHTMLXRenderer extends JSONRenderer
{
    public function render(View $view)
    {
        $originalResult = $view->getData();
        $expectedResult = array(
            'total_count' => $originalResult[Database::KW_TOTAL_ROW_COUNT],
            'pos' => $originalResult[Database::KW_ROW_OFFSET],
            'rows' => $originalResult[Database::KW_DATA]
        );

        $view->set('result', $expectedResult);

        return parent::render($view);
    }
}
