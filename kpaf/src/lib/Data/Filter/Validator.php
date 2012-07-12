<?php

namespace Bluefin\Data\Filter;

use Bluefin\Data\Model;
use Bluefin\Data\DataFilter;

/**
 * 输入值有效性验证过滤器。
 * 该过滤器只对用户输入的数据进行合法性验证，不进行其他必要性或字段是否可写的检查。
 */
class Validator implements FilterInterface
{
    private $_isNewRecord;

    public function __construct($isNewRecord = false)
    {
        $this->_isNewRecord = $isNewRecord;
    }

    public function apply(FilterContext $context)
    {
        $fieldOptions = $context->getModel()->schema()->getFilterOptions();
        $modifiedFields = $context->getData();

        $data = Type::validate($modifiedFields, $this->_isNewRecord ? $fieldOptions : array_intersect_key($fieldOptions, $modifiedFields));

        $context->setData($data);
    }
}
