<?php

namespace WBT\Controller\UnitTest;

use Bluefin\Controller;
use Bluefin\Data\Database;
use WBT\Model\Weibotui\User;
use WBT\Model\Weibotui\UserStatus;

use Bluefin\HTML\Table;
use Bluefin\HTML\Link;

class HTMLController extends Controller
{
    public function indexAction()
    {

    }

    public function testPaginationAction()
    {
        $condition = $this->_request->getQueryParams();
        Database::extractQueryCondition($condition, $outputColumns, $paging, $ranking);

        //测试数据
        $data = [];
        for ($i = 0; $i < Database::DEFAULT_ROWS_PER_PAGE; $i++)
        {
            $data[] = [ User::USER_ID => $i, User::USERNAME => 'user' . $i, User::STATUS => UserStatus::ACTIVATED ];
        }

        $outputColumns = User::s_metadata()->getFilterOptions();

        //表单Header
        $shownOptions = [
            User::USERNAME,
            User::STATUS
        ];

        $paging[Database::KW_SQL_TOTAL_ROWS] = 600;
        $paging[Database::KW_SQL_TOTAL_PAGES] = 20;
        isset($paging[Database::KW_SQL_PAGE_INDEX]) || ($paging[Database::KW_SQL_PAGE_INDEX] = 1);
        isset($paging[Database::KW_SQL_ROWS_PER_PAGE]) || ($paging[Database::KW_SQL_ROWS_PER_PAGE] = Database::DEFAULT_ROWS_PER_PAGE);

        //构造表单
        $table = Table::fromDbData($data, $outputColumns, User::USER_ID, $paging, $shownOptions, ['class' => 'table-bordered table-striped table-hover']
        );

        //显示记录编号，此编号是动态排序编号，和数据库中的数据无关
        $table->showRecordNo = true;

        $this->_view->set('table', $table);
    }

    public function testTableFunctionAction()
    {
        $condition = $this->_request->getQueryParams();
        Database::extractQueryCondition($condition, $outputColumns, $paging, $ranking);

        //测试数据
        $data = [];
        for ($i = 0; $i < 10; $i++)
        {
            $data[] = [ User::USER_ID => $i, User::USERNAME => 'user' . $i, User::STATUS => UserStatus::ACTIVATED ];
        }

        $outputColumns = User::s_metadata()->getFilterOptions();

        //表单Header
        $shownOptions = [
            User::USERNAME => [
                Table::COLUMN_FUNCTION => function (array $row) {
                    return $row[User::USERNAME] . "#" . $row[User::USER_ID];
                }
            ],
            User::STATUS,
            'operations' => [
                Table::COLUMN_TITLE => '操作',
                Table::COLUMN_CELL_STYLE => 'width:10%',

                Table::COLUMN_OPERATIONS => [
                    new Link(
                    '测试操作',
                    "{{this.user_id}}",
                    null
                    ),
                ]
            ]
        ];

        //构造表单
        $table = Table::fromDbData($data, $outputColumns, User::USER_ID, $paging, $shownOptions, ['class' => 'table-bordered table-striped table-hover']
        );

        //显示记录编号，此编号是动态排序编号，和数据库中的数据无关
        $table->showRecordNo = true;

        $this->_view->set('table', $table);
    }
}
