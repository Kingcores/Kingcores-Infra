<?php

namespace Bluefin\HTML;

use Bluefin\App;
use Bluefin\Data\Type;
use Bluefin\Data\ModelMetadata;

/**
 * 表单。
 *
 * items属性
 *   type - 组件类别
 *   label - 字段标签
 *   name - 字段Pascal名称
 *   value - 字段值
 *   class - 样式类
 *   data - select的options
 *   subType - input的type属性
 */
class Form extends Container
{
    const FIELD_TAG = 'tag';
    const FIELD_TYPE = 'type';
    const FIELD_CLASS = 'class';
    const FIELD_LABEL = 'label';
    const FIELD_LABEL_ICON = 'icon';
    const FIELD_NAME = 'name';
    const FIELD_ALT_NAME = 'alt';
    const FIELD_DATA = 'data';
    const FIELD_VALUE = 'value';
    const FIELD_ID = 'id';
    const FIELD_REQUIRED = 'required';
    const FIELD_MESSAGE = 'message';
    const FIELD_CONFIRM = 'confirm';
    const FIELD_PREPEND = 'prepend';
    const FIELD_INLINE = 'inline';
    const FIELD_EXCLUDED = 'excluded';

    const COM_INPUT = 'input';
    const COM_COMBO_BOX = 'comboBox';
    const COM_TEXT_AREA = 'textArea';
    const COM_DATE = 'date';
    const COM_TIME = 'time';
    const COM_DATETIME = 'datetime';
    const COM_SPINNER = 'spinner';
    const COM_CHECK_BOX = 'checkBox';
    const COM_RADIO_GROUP = 'radioGroup';
    const COM_READONLY_TEXT = 'readonlyText';
    const COM_HIDDEN = 'hidden';
    const COM_FILE_UPLOAD = 'fileUpload';
    const COM_MOBILE_AUTH = 'mobileAuth';
    const COM_CUSTOM = 'custom';

    const CONFIRM_FIELD_SUFFIX = '_confirm';

    public $editMode;

    public $labelColon;
    public $legend;
    public $titleClass;
    public $ajaxForm;

    public $buttons;
    public $metadata;
    public $message;

    public $submitAction;
    public $bodyScript;
    public $initScript;
    public $showRequired;

    public static function filterFormInputs(ModelMetadata $mainTableMeta, array &$fields = null, array $input)
    {
        isset($fields) || ($fields = $mainTableMeta->getFieldNames());

        $fields = array_to_assoc($fields);

        $fieldsMetadata = $mainTableMeta->expandMeta(array_keys($fields));

        $result = [];

        foreach ($fields as $fieldName => $componentOption)
        {
            $fieldName = str_replace('.', '_', $fieldName);

            if (isset($componentOption) && isset($componentOption[self::FIELD_REQUIRED]) && $componentOption[self::FIELD_REQUIRED])
            {
                $required = true;
                $fieldDisplayName = array_try_get($componentOption, self::FIELD_LABEL, $fieldName);
            }
            else
            {
                if (!array_key_exists($fieldName, $fieldsMetadata)) continue;

                $fieldOptions = $fieldsMetadata[$fieldName];
                $fieldDisplayName = $fieldOptions[Type::FIELD_NAME];

                if (array_try_get($fieldOptions, Type::ATTR_REQUIRED, false))
                {
                    $required = true;
                }
                else
                {
                    $required = false;
                }
            }

            $value = array_try_get($input, $fieldName);

            if ($required)
            {
                if (!isset($value) || $value == '')
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_('"%name%" is required.', ['%name%' => $fieldDisplayName])
                    );
                };
            }
            else if (isset($value) && $value == '')
            {
                continue;
            }

            if (isset($componentOption) && isset($componentOption[self::FIELD_CONFIRM]) && $componentOption[self::FIELD_CONFIRM])
            {
                $confirmValue = array_try_get($input, $fieldName . self::CONFIRM_FIELD_SUFFIX);
                if ($value != $confirmValue)
                {
                    throw new \Bluefin\Exception\InvalidRequestException(
                        _APP_('The value of "%name%" is different from the one of "%name% Confirmation".',
                            ['%name%' => $fieldDisplayName])
                    );
                }
            }

            if (isset($value))
            {
                $result[$fieldName] = $value;
            }
        }

        return $result;
    }

    public static function fromModelMetadata(ModelMetadata $mainTableMeta, array $fields = null, $data /* or pkValue */ = null, array $attributes = null)
    {
        if (!isset($fields))
        {//没给字段，默认用全表字段
            $fields = $mainTableMeta->getFieldNames();
            $columns = $fields;
        }
        else
        {//预处理字段数组
            $columns = array_keys(array_to_assoc($fields));
        }

        if (!empty($data))
        {
            if (is_array($data))
            {//用户提交的数据
                if (isset($data[$mainTableMeta->getPrimaryKey()]))
                {
                    $pkValue = $data[$mainTableMeta->getPrimaryKey()];
                }
            }
            else
            {//从数据库根据主键取出编辑
                $pkValue = $data;
                $data = null;
            }
        }

        $form = new Form(null, $attributes);

        if (isset($pkValue))
        {//编辑模式
            $form->editMode = true;
            $form->addComponent(self::_buildFormComponent([self::FIELD_TAG => self::COM_HIDDEN, self::FIELD_VALUE => $pkValue]));

            if (!isset($data))
            {
                $condition = [ $mainTableMeta->getPrimaryKey() => $pkValue ];

                $sql = $mainTableMeta->getDatabase()->buildSelectSQL(
                    $columns,
                    $mainTableMeta->getModelName(),
                    $condition
                );

                $data = $mainTableMeta->getDatabase()->getAdapter()->fetchRow($sql, $condition);

                $fieldsMetadata = $columns;
            }
        }

        if (!isset($fieldsMetadata))
        {
            $fieldsMetadata = $mainTableMeta->expandMeta($columns);
        }

        unset($fields[$mainTableMeta->getPrimaryKey()]);

        foreach ($fields as $fieldName => $componentOption)
        {
            if (is_int($fieldName))
            {
                $fieldName = $componentOption;
                $componentOption = [];
            }

            $fieldName = str_replace('.', '_', $fieldName);

            //设置ID和字段名称
            isset($componentOption[self::FIELD_ID]) || ($componentOption[self::FIELD_ID] = $form->attributes['id'] . usw_to_pascal($fieldName));
            isset($componentOption[self::FIELD_NAME]) || ($componentOption[self::FIELD_NAME] = $fieldName);

            //取出字段元数据
            $fieldOptions = array_try_get($fieldsMetadata, $fieldName);

            if (isset($data) && array_key_exists($fieldName, $data))
            {
                $componentOption[self::FIELD_VALUE] = $data[$fieldName];
            }

            if (isset($fieldOptions))
            {
                if ($form->editMode)
                {//编辑模式
                    if (array_try_get($fieldOptions, Type::ATTR_NO_INPUT, false) ||
                        array_try_get($fieldOptions, Type::ATTR_STATE, false) ||
                        array_try_get($fieldOptions, Type::ATTR_READONLY_ON_UPDATING, false))
                    {//不允许编辑的字段
                        $componentOption[self::FIELD_TAG] = self::COM_READONLY_TEXT;
                    }
                }
                else if (array_try_get($fieldOptions, Type::ATTR_NO_INPUT, false) ||
                    array_try_get($fieldOptions, Type::ATTR_STATE, false) ||
                    array_try_get($fieldOptions, Type::ATTR_READONLY_ON_INSERTING, false))
                {//非编辑模式，不允许编辑的字段略过
                    continue;
                }

                //设置标签
                if (!isset($componentOption[self::FIELD_LABEL]))
                {
                    $componentOption[self::FIELD_LABEL] = $fieldOptions[Type::FIELD_NAME];
                }
                if (!isset($componentOption[self::FIELD_ALT_NAME]))
                {
                    $componentOption[self::FIELD_ALT_NAME] = $fieldOptions[Type::FIELD_NAME];
                }
                //设置是否必填
                isset($componentOption[self::FIELD_REQUIRED]) || ($componentOption[self::FIELD_REQUIRED] = array_try_get($fieldOptions, Type::ATTR_REQUIRED, false));

                if (array_try_get($fieldOptions, Type::ATTR_ENUM, false))
                {//枚举字段
                    $enumerable = $fieldOptions[Type::ATTR_ENUM];

                    isset($componentOption[self::FIELD_TAG]) || ($componentOption[self::FIELD_TAG] = self::COM_COMBO_BOX);
                    isset($componentOption[self::FIELD_DATA]) || ($componentOption[self::FIELD_DATA] = $enumerable::getDictionary());
                }
                else if (!array_key_exists(self::FIELD_TAG, $componentOption))
                {
                    self::_integrateComponentOption($componentOption, $fieldOptions);
                }
            }
            else if (empty($componentOption) || !isset($componentOption[self::FIELD_TAG]))
            {
                throw new \Bluefin\Exception\InvalidOperationException(
                    "Incomplete options for field '{$fieldName}'!"
                );
            }
            else
            {
                if (!isset($componentOption[self::FIELD_ALT_NAME]) && isset($componentOption[self::FIELD_LABEL]))
                {
                    $componentOption[self::FIELD_ALT_NAME] = $componentOption[self::FIELD_LABEL];
                }
            }

            $hasConfirmField = array_try_get($componentOption, Form::FIELD_CONFIRM, false, true);
            
            $form->addComponent($form->_buildFormComponent($componentOption));

            if ($hasConfirmField)
            {
                $componentOption[self::FIELD_CONFIRM] = $componentOption[self::FIELD_ID];
                $componentOption[self::FIELD_ID] .= 'Confirm';
                $componentOption[self::FIELD_NAME] .= self::CONFIRM_FIELD_SUFFIX;
                $componentOption[self::FIELD_VALUE] = array_try_get($data, $componentOption[self::FIELD_NAME]);

                $confirmText = _DICT_('confirm');
                $componentOption[self::FIELD_LABEL] .= $confirmText;
                isset($componentOption[self::FIELD_ALT_NAME]) && ($componentOption[self::FIELD_ALT_NAME] .= $confirmText);
                unset($componentOption[self::FIELD_INLINE]);

                $form->addComponent($form->_buildFormComponent($componentOption));
            }
        }
        
        return $form;
    }

    protected static function _integrateComponentOption(array &$componentOption, array $fieldOptions)
    {
        switch ($fieldOptions[Type::ATTR_TYPE])
        {
            case Type::TYPE_INT:
            case Type::TYPE_FLOAT:
            case Type::TYPE_DIGITS:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-small');
                break;

            case Type::TYPE_MONEY:
                $componentOption[self::FIELD_TAG] = self::COM_SPINNER;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = InputSpinner::TYPE_CURRENCY);
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-small');
                break;

            case Type::TYPE_BOOL:
                $componentOption[self::FIELD_TAG] = self::COM_CHECK_BOX;
                break;

            case Type::TYPE_BINARY:
                App::assert(false);
                break;

            case Type::TYPE_DATE:
                $componentOption[self::FIELD_TAG] = self::COM_DATE;
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-small');
                break;

            case Type::TYPE_TIME:
                $componentOption[self::FIELD_TAG] = self::COM_TIME;
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-small');
                break;

            case Type::TYPE_DATE_TIME:
            case Type::TYPE_TIMESTAMP:
                $componentOption[self::FIELD_TAG] = self::COM_DATETIME;
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-small');
                break;

            case Type::TYPE_TEXT:
            case Type::TYPE_IDNAME:
            case Type::TYPE_JSON:
            case Type::TYPE_XML:
                $len = array_try_get($fieldOptions, Type::ATTR_MAX, array_try_get($fieldOptions, Type::ATTR_LENGTH));
                if (isset($len) && $len <= 100)
                {
                    $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                    isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                    isset($componentOption['maxlength']) || ($componentOption['maxlength'] = $len);
                }
                else
                {
                    $componentOption[self::FIELD_TAG] = self::COM_TEXT_AREA;
                    isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-xlarge');
                }
                break;

            case Type::TYPE_PASSWORD:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'password');
                break;

            case Type::TYPE_EMAIL:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'email');
                break;

            case Type::TYPE_PHONE:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                break;

            case Type::TYPE_URL:
            case Type::TYPE_PATH:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-xlarge');
                break;

            case Type::TYPE_UUID:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                isset($componentOption[self::FIELD_CLASS]) || ($componentOption[self::FIELD_CLASS] = 'input-xlarge');
                break;

            case Type::TYPE_IPV4:
                $componentOption[self::FIELD_TAG] = self::COM_INPUT;
                isset($componentOption[self::FIELD_TYPE]) || ($componentOption[self::FIELD_TYPE] = 'text');
                break;

            default:
                App::assert(false);
                break;
        }
    }

    public function __construct(array $components = null, array $attributes = null)
    {
        parent::__construct($components, $attributes);

        if (!isset($this->attributes['id']))
        {
            $this->generateID();
        }
        $this->ajaxForm = false;
        $this->showRequired = array_try_get($this->attributes, 'showRequired', true, true);
        $this->metadata = [];
        $this->labelColon = _DICT_(':');
    }

    public function addButtons(array $buttons)
    {
        isset($this->buttons) || ($this->buttons = []);

        $this->buttons = array_merge($this->buttons, $buttons);

        return $this;
    }

    protected function _commitProperties()
    {
        parent::_commitProperties();

        isset($this->attributes['method']) || ($this->attributes['method'] = \Bluefin\Common::HTTP_METHOD_POST);
        if (!$this->ajaxForm)
        {
            isset($this->attributes['action']) || ($this->attributes['action'] = '');
        }
    }

    protected function _renderContent()
    {
        $result = parent::_renderContent();

        $scriptView = $this->_createView('.script');
        $scriptView->set('component', $this);

        $this->_registerScript($scriptView->render());

        return $result;
    }

    protected function _buildFormComponent(array $componentOption)
    {
        $tag = array_try_get($componentOption, self::FIELD_TAG, null, true);
        $alt = array_try_get($componentOption, self::FIELD_ALT_NAME);
        $icon = array_try_get($componentOption, self::FIELD_LABEL_ICON, null, true);

        if (isset($icon))
        {
            $componentOption[self::FIELD_LABEL] = '<i class="' . $icon . '"></i> ' . $componentOption[self::FIELD_LABEL];
        }

        switch ($tag)
        {
            case self::COM_INPUT:
                if (isset($componentOption[self::FIELD_LABEL]))
                {
                    $placeHolder = (isset($componentOption[self::FIELD_PREPEND]) && $componentOption[self::FIELD_PREPEND]) ? '' : $alt;
                    if ($this->showRequired && array_try_get($componentOption, self::FIELD_REQUIRED, false))
                    {
                        $placeHolder .= _VIEW_('(required)');
                    }
                    $componentOption['placeholder'] = $placeHolder;
                }
                return new SimpleComponent($componentOption, $tag);

            case self::COM_COMBO_BOX:
                $collection = array_try_get($componentOption, self::FIELD_DATA, [], true);
                return new ComboBox($collection, $componentOption);

            case self::COM_TEXT_AREA:
                if (isset($componentOption[self::FIELD_LABEL]))
                {
                    $placeHolder = (isset($componentOption[self::FIELD_PREPEND]) && $componentOption[self::FIELD_PREPEND]) ? '' : $alt;
                    if ($this->showRequired && array_try_get($componentOption, self::FIELD_REQUIRED, false))
                    {
                        $placeHolder .= _VIEW_('(required)');
                    }
                    $componentOption['placeholder'] = $placeHolder;
                }
                return new DoubleTag('textarea', $componentOption);

            case self::COM_DATE:
                return new DatetimePicker(DatetimePicker::TYPE_DATE_ONLY, $componentOption);

            case self::COM_TIME:
                return new DatetimePicker(DatetimePicker::TYPE_TIME_ONLY, $componentOption);

            case self::COM_DATETIME:
                return new DatetimePicker(DatetimePicker::TYPE_DATETIME, $componentOption);

            case self::COM_SPINNER:
                return new InputSpinner($componentOption);

            case self::COM_CHECK_BOX:
                return CheckableGroup::buildSingleCheckBox($componentOption);

            case self::COM_RADIO_GROUP:
                $collection = array_try_get($componentOption, self::FIELD_DATA, [], true);
                return new CheckableGroup(CheckableGroup::TYPE_RADIO, $collection, $componentOption);

            case self::COM_READONLY_TEXT:
                return new DoubleTag('span', $componentOption);

            case self::COM_FILE_UPLOAD:
                return new FileUpload($componentOption);

            case self::COM_HIDDEN:
                $componentOption[self::FIELD_TYPE] = 'hidden';
                return new SimpleComponent($componentOption, 'input');

            case self::COM_CUSTOM:
                return new CustomComponent($componentOption[self::FIELD_VALUE]);

            case self::COM_MOBILE_AUTH:
                $componentOption['placeholder'] = $alt;
                return new MobileAuthCode($componentOption);

            default:
                _DEBUG($componentOption);
                App::assert(false);
                return null;
        }
    }
}