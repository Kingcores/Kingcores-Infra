<?php

namespace Bluefin\Lance\Feature;

use Bluefin\Lance\Entity;
use Bluefin\Data\Type;
use Bluefin\Lance\Convention;
use Bluefin\Lance\Exception\GrammarException;

class AutoIncrementId extends AbstractFeature implements FeatureInterface
{
    const FIELD_NAME_SUFFIX = '_id';
    const DEFAULT_LENGTH = 10;
    const DEFAULT_BASE = 1;

    public function __construct(Entity $entity, array $modifiers)
    {
        parent::__construct(Convention::FEATURE_AUTO_INCREMENT_ID, $entity, $modifiers);
    }

    public function apply1Pass()
    {
        $fieldName = $this->_entity->getCodeName() . self::FIELD_NAME_SUFFIX;
        $length = self::DEFAULT_LENGTH;
        $base = self::DEFAULT_BASE;

        foreach ($this->_modifiers as $modifier)
        {
            switch ($modifier[0])
            {
                case Convention::MODIFIER_TYPE_GT:
                    if ($modifier[1] == '=')
                    {
                        //Convention::MODIFIER_TYPE_GTE
                        $base = substr($modifier, 2);
                    }
                    else
                    {
                        $base = substr($modifier, 1) + 1;
                    }
                    break;

                case Convention::MODIFIER_TYPE_DIGITS:
                    $length = substr($modifier, 1);
                    break;

                default:
                    throw new GrammarException("Unknown auto_increment_id modifier: {$modifier}");
            }
        }

        $fieldModifier = array();
        $fieldModifier[] = Type::TYPE_INT;
        $fieldModifier[] = 'c=ID';
        $fieldModifier[] = Convention::MODIFIER_TYPE_DIGITS . $length;
        $fieldModifier[] = Convention::MODIFIER_TYPE_GTE . $base;

        $this->_entity->setEntityOption(Convention::ENTITY_OPTION_AUTO_INCREMENT_BASE, $base);

        $field = $this->_entity->addField($fieldName, merge_modifiers($fieldModifier), true, true);
        $field->setRequired();
        $field->setDBAutoInsert();
        $field->setReadonlyOnCreation();
        $field->setReadonlyOnUpdating();
        $field->setOwnerFeature($this->getFeatureName());
        $this->_entity->setPrimaryKey($fieldName);
    }

    public function apply2Pass()
    {

    }
}
