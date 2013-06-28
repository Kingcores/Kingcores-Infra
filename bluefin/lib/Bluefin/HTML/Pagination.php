<?php

namespace Bluefin\HTML;

class Pagination extends Component
{
    private static $__scriptRegistered;

    public $rowsPerPage;
    public $totalRows;
    public $currentPage;
    public $totalPages;
    
    public $startPage;
    public $endPage;

    public function __construct(array $attributes = null)
    {
        parent::__construct($attributes);

        $this->addFirstClass('pagination');

        if (!isset(self::$__scriptRegistered))
        {
            self::$__scriptRegistered = true;

            self::_registerScript(<<<'SCRIPT'
    <script type="text/javascript">
        $(function (){
            if (!bluefinBH.isRegistered('_pagination')) {
                bluefinBH.register('_pagination', true);
                $('.pagination').each(function(){
                    var divp = $(this),
                        req = divp.data('request');
                    divp.on('click', 'a[data-page]', function (e) {
                        location.href = bluefinBH.buildUrl(req, {'*PAGING*': {'page': $(this).data('page')}});
                    });
                });
            }
        });
    </script>
SCRIPT
);
        }
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        $halfPages = $this->totalPages > 11 ? 5 : ($this->totalPages / 2);
        $startPage = $this->currentPage - $halfPages;
        $startPage > 0 || ($startPage = 1);

        $endPage = $startPage + $halfPages * 2 - 1;
        if ($endPage > $this->totalPages)
        {
            $startPage -= $endPage - $this->totalPages;
            $startPage > 0 || ($startPage = 1);
            $endPage = $this->totalPages;
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;
    }
}
