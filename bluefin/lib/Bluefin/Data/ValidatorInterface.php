<?php

namespace Bluefin\Data;

/**
 * 验证器接口。
 * 用于字段过滤选项。
 * 枚举类型默认实现此接口。
 */
interface ValidatorInterface
{
    function validate($value);
}
