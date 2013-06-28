<?php

namespace Bluefin\HTML;

use Bluefin\Data\Type;
use Bluefin\Data\Database;

/**
 * 表格。
 *
 * headers属性
 *   Type中的字段属性
 *   varText
 *   dependsOn
 */
class Table extends Component
{
    const COLUMN_TITLE = 'name';
    const COLUMN_VAR_TEXT = 'varText';
    const COLUMN_OPERATIONS = 'operations';
    const COLUMN_DELIMITER = 'delimiter';
    const COLUMN_DEPENDS_ON = 'dependsOn';
    const COLUMN_ALT_VALUE = 'altValue';
    const COLUMN_HEADER_STYLE = 'headerStyle';
    const COLUMN_CELL_STYLE = 'cellStyle';
    const COLUMN_FUNCTION = 'function';

    public static function fromDbData(array $data, array $columns, $idColumn, array $paging = null,
                                      array $shownOptions = null, array $attributes = null)
    {
        if (isset($shownOptions))
        {
            $headers = [];

            foreach ($shownOptions as $code => $extendOptions)
            {
                if (is_int($code))
                {
                    $code = $extendOptions;
                    $extendOptions = null;
                }

                $hasExtend = false;

                if (isset($extendOptions))
                {
                    $hasExtend = true;

                    if (array_key_exists(self::COLUMN_DEPENDS_ON, $extendOptions))
                    {
                        if (!array_key_exists(self::COLUMN_ALT_VALUE, $extendOptions))
                        {
                            $extendOptions[self::COLUMN_ALT_VALUE] = _VIEW_('N/A');
                        }
                    }

                    if (array_key_exists(self::COLUMN_OPERATIONS, $extendOptions))
                    {
                        if (!array_key_exists(self::COLUMN_TITLE, $extendOptions))
                        {
                            $extendOptions[self::COLUMN_TITLE] = _VIEW_('Operations');
                        }
                    }
                }

                $column = array_try_get($columns, $code);

                if (isset($column))
                {
                    if ($hasExtend)
                    {
                        $column = array_merge($column, $extendOptions);
                    }

                    $headers[$code] = $column;
                }
                else
                {
                    if (!$hasExtend)
                    {
                        throw new \Bluefin\Exception\InvalidOperationException(
                            "Missing field options for field '{$code}'!"
                        );
                    }

                    $headers[$code] = $extendOptions;
                }
            }
        }
        else
        {
            $headers = $columns;
        }

        $table = new Table($headers, $idColumn, $attributes);

        foreach ($data as $dataRow)
        {
            $table->addAssocRow($dataRow);
        }

        if (isset($paging))
        {
            $table->paging = new Pagination(['data-request' => $table->request]);
            $table->paging->rowsPerPage = $paging[Database::KW_SQL_ROWS_PER_PAGE];
            $table->paging->totalRows = $paging[Database::KW_SQL_TOTAL_ROWS];
            $table->paging->currentPage = $paging[Database::KW_SQL_PAGE_INDEX];
            $table->paging->totalPages = $paging[Database::KW_SQL_TOTAL_PAGES];
        }

        return $table;
    }

    public $headers;
    public $numColumns;
    /**
     * @var Pagination
     */
    public $paging;

    public $idColumn;
    public $showRecordNo;
    public $baseRecordNo;

    public $data;
    public $request;

    public function __construct(array $headers, $idColumn, array $attributes = null)
    {
        parent::__construct($attributes);

        $this->addFirstClass('table');

        $this->headers = $headers;
        $this->idColumn = $idColumn;
        $this->numColumns = count($headers);
        $this->showRecordNo = false;
        $this->request = array_try_get($this->attributes, 'request', null, true);
        isset($this->request) || ($this->request = \Bluefin\App::getInstance()->request()->getRequestUri());

        $this->data = [];
    }

    public function addAssocRow(array $array)
    {
        $row = [];

        foreach ($this->headers as $code => $options)
        {
            if (array_key_exists(self::COLUMN_OPERATIONS, $options))
            {
                $content = [];
                $delimiter = array_try_get($options, self::COLUMN_DELIMITER, '&nbsp;');
                foreach ($options[self::COLUMN_OPERATIONS] as $component)
                {
                    /**
                     * @var SimpleComponent $component
                     */
                    $component->dataContext = $array;
                    $unit = $component->__toString();
                    if ($unit != '') $content[] = $unit;
                    $component->dataContext = null;
                }
                $row[] = implode($delimiter, $content);
            }
            else if (array_key_exists(self::COLUMN_VAR_TEXT, $options))
            {
                if (array_key_exists(self::COLUMN_DEPENDS_ON, $options))
                {
                    $dependsOn = $options[self::COLUMN_DEPENDS_ON];

                    if (is_array($dependsOn))
                    {
                        $fallback = false;

                        foreach ($dependsOn as $depend)
                        {
                            if (is_null($array[$depend]))
                            {
                                $row[] = $options[self::COLUMN_ALT_VALUE];
                                $fallback = true;
                                break;
                            }
                        }

                        if ($fallback) continue;
                    }
                    else
                    {
                        if (is_null($array[$dependsOn]))
                        {
                            $row[] = $options[self::COLUMN_ALT_VALUE];
                            continue;
                        }
                    }
                }

                $vartext = $options[self::COLUMN_VAR_TEXT];
                $row[] = \Bluefin\VarText::parseVarText($vartext, $array);
            }
            else if (array_key_exists(self::COLUMN_FUNCTION, $options))
            {
                $row[] = call_user_func($options[self::COLUMN_FUNCTION], $array);
            }
            else if (array_key_exists(Type::ATTR_ENUM, $options))
            {
                $row[] = $options[Type::ATTR_ENUM]::getDisplayName($array[$code]);
            }
            else if (array_key_exists(Type::ATTR_STATE, $options))
            {
                $row[] = $options[Type::ATTR_STATE]::getDisplayName($array[$code]);
            }
            else
            {
                $row[] = $array[$code];
            }
        }

        $this->data[] = $row;
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        if ($this->showRecordNo)
        {
            if (isset($this->paging))
            {
                $this->baseRecordNo = ($this->paging->currentPage - 1) * $this->paging->rowsPerPage;
            }
            else
            {
                $this->baseRecordNo = 0;
            }

            $this->numColumns++;
        }

        $this->headers = array_values($this->headers);
    }
}