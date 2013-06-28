<?php
//Don't edit this file which is generated by Bluefin Lance.
//You can put custom business logic into a Business class under WBT\Business namespace.
namespace WBT\Model\Weibotui;

use Bluefin\App;
use Bluefin\Convention;
use Bluefin\VarText;
use Bluefin\Data\Type;
use Bluefin\Data\Model;
use Bluefin\Data\Database;
use Bluefin\Data\ModelMetadata;
use Bluefin\Data\DbExpr;

class UserExpenseRecord extends Model
{
    const SERIAL_NO = 'serial_no';
    const BATCH_ID = 'batch_id';
    const AMOUNT = 'amount';
    const USER = 'user';
    const USAGE = 'usage';
    const STATUS = 'status';
    const PENDING_TIME = 'pending_time';
    const PAID_TIME = 'paid_time';
    const CANCELLED_TIME = 'cancelled_time';
    const STATUS_LOG = 'status_log';

    const WITH_USER = 'user_expense_record.user:user.user_id';

    const TO_PAY = '_pay';
    const TO_CANCEL = '_cancel';

    protected static $__metadata;

    /**
     * @static
     * @return \Bluefin\Data\ModelMetadata
     */
    public static function s_metadata()
    {
        if (!isset(self::$__metadata))
        {
            self::$__metadata = new ModelMetadata(
                'weibotui',
                'user_expense_record',
                'serial_no',
                [
                    'serial_no' => ['name' => _META_('weibotui.user_expense_record.serial_no'), 'type' => 'text', 'length' => 20, 'required' => true],
                    'batch_id' => ['name' => _META_('weibotui.user_expense_record.batch_id'), 'type' => 'text', 'length' => 20, 'required' => true],
                    'amount' => ['name' => _META_('weibotui.user_expense_record.amount'), 'type' => 'money', 'precision' => 2, 'required' => true],
                    'user' => ['name' => _META_('weibotui.user_expense_record.user'), 'type' => 'int', 'length' => 10, 'min' => 100000, 'required' => true],
                    'usage' => ['name' => _META_('weibotui.user_expense_record.usage'), 'type' => 'text', 'max' => 20, 'required' => true, 'enum' => new UserBusinessType(), 'db_insert' => true],
                    'status' => ['name' => _META_('weibotui.user_expense_record.status'), 'type' => 'idname', 'required' => true, 'state' => new UserExpenseStatus(), 'db_insert' => true],
                    'pending_time' => ['name' => _META_('weibotui.user_expense_record.pending_time'), 'type' => 'datetime'],
                    'paid_time' => ['name' => _META_('weibotui.user_expense_record.paid_time'), 'type' => 'datetime'],
                    'cancelled_time' => ['name' => _META_('weibotui.user_expense_record.cancelled_time'), 'type' => 'datetime'],
                    'status_log' => ['name' => _META_('weibotui.user_expense_record.status_log'), 'type' => 'text', 'max' => 1000, 'default' => 'pending'],
                ],
                [
                    'owner_field' => 'user',
                    'has_states' => 'status',
                    'triggers' => ['BEFORE-INSERT']
                ],
                [
                    'user' => self::WITH_USER,
                ],
                [
                ],
                [
                    Model::OP_CREATE => NULL,
                    Model::OP_GET => NULL,
                    Model::OP_UPDATE => NULL,
                    Model::OP_DELETE => NULL,
                    '_pay' => ['pending' => ['weibotui' => ['*system*']], ],
                    '_cancel' => ['pending' => ['weibotui' => ['*system*']], ],
                ]
            );
        }

        return self::$__metadata;
    }

    /**
     * @param string $serialNo
     * @param array $params
     * @return \WBT\Model\Weibotui\UserExpenseRecord
     * @throws \Bluefin\Exception\RequestException
     */
    public static function doPay($serialNo, array $params = null)
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::doPay', 'diag');

        if (is_array($serialNo))
        {
            $userExpenseRecord = new UserExpenseRecord();
            $userExpenseRecord->populate($serialNo);
            $serialNo = $userExpenseRecord->pk();
        }
        else
        {
            $userExpenseRecord = new UserExpenseRecord($serialNo);
        }
        _NON_EMPTY($userExpenseRecord);

        $aclStatus = self::checkActionPermission(self::TO_PAY, $userExpenseRecord->data());
        if ($aclStatus !== Model::ACL_ACCEPTED)
        {
            if (ENV == 'dev')
            {
                throw new \Bluefin\Exception\RequestException(\Bluefin\Common::getStatusCodeMessage($aclStatus) . ' @ ' . __METHOD__, $aclStatus);
            }
            throw new \Bluefin\Exception\RequestException(null, $aclStatus);
        }

        $currentState = $userExpenseRecord->getStatus();
        $methodName = "{$currentState}ToPay";
        return self::$methodName($serialNo, $params, $userExpenseRecord);
    }

    public static function pendingToPay($serialNo, array $params = null, Model $cachedModel = null)
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::pendingToPay', 'diag');

        $db = self::s_metadata()->getDatabase()->getAdapter();
        $db->beginTransaction();

        try
        {
            if (isset($cachedModel))
            {
                $userExpenseRecord = $cachedModel;
            }
            else
            {
                $userExpenseRecord = new UserExpenseRecord($serialNo);
                _NON_EMPTY($userExpenseRecord);

                $aclStatus = self::checkActionPermission(self::TO_PAY, $userExpenseRecord->data());
                if ($aclStatus !== Model::ACL_ACCEPTED)
                {
                    throw new \Bluefin\Exception\RequestException(null, $aclStatus);
                }

                $currentState = $userExpenseRecord->getStatus();
                if ($currentState != 'pending')
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
            }

            //Set target state
            $userExpenseRecord->setStatus(UserExpenseStatus::PAID);

            App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, true);
            $affected = $userExpenseRecord->update(['serial_no' => $serialNo, 'status' => 'pending']);
            if ($affected <= 0)
            {
                App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, false);
                throw new \Bluefin\Exception\DataException(_APP_("The record to operate is not in expected state."));
            }

            App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, false);

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }

        return $userExpenseRecord;
    }

    /**
     * @param string $serialNo
     * @param array $params
     * @return \WBT\Model\Weibotui\UserExpenseRecord
     * @throws \Bluefin\Exception\RequestException
     */
    public static function doCancel($serialNo, array $params = null)
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::doCancel', 'diag');

        if (is_array($serialNo))
        {
            $userExpenseRecord = new UserExpenseRecord();
            $userExpenseRecord->populate($serialNo);
            $serialNo = $userExpenseRecord->pk();
        }
        else
        {
            $userExpenseRecord = new UserExpenseRecord($serialNo);
        }
        _NON_EMPTY($userExpenseRecord);

        $aclStatus = self::checkActionPermission(self::TO_CANCEL, $userExpenseRecord->data());
        if ($aclStatus !== Model::ACL_ACCEPTED)
        {
            if (ENV == 'dev')
            {
                throw new \Bluefin\Exception\RequestException(\Bluefin\Common::getStatusCodeMessage($aclStatus) . ' @ ' . __METHOD__, $aclStatus);
            }
            throw new \Bluefin\Exception\RequestException(null, $aclStatus);
        }

        $currentState = $userExpenseRecord->getStatus();
        $methodName = "{$currentState}ToCancel";
        return self::$methodName($serialNo, $params, $userExpenseRecord);
    }

    public static function pendingToCancel($serialNo, array $params = null, Model $cachedModel = null)
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::pendingToCancel', 'diag');

        $db = self::s_metadata()->getDatabase()->getAdapter();
        $db->beginTransaction();

        try
        {
            if (isset($cachedModel))
            {
                $userExpenseRecord = $cachedModel;
            }
            else
            {
                $userExpenseRecord = new UserExpenseRecord($serialNo);
                _NON_EMPTY($userExpenseRecord);

                $aclStatus = self::checkActionPermission(self::TO_CANCEL, $userExpenseRecord->data());
                if ($aclStatus !== Model::ACL_ACCEPTED)
                {
                    throw new \Bluefin\Exception\RequestException(null, $aclStatus);
                }

                $currentState = $userExpenseRecord->getStatus();
                if ($currentState != 'pending')
                {
                    throw new \Bluefin\Exception\InvalidRequestException();
                }
            }

            //Set target state
            $userExpenseRecord->setStatus(UserExpenseStatus::CANCELLED);

            App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, true);
            $affected = $userExpenseRecord->update(['serial_no' => $serialNo, 'status' => 'pending']);
            if ($affected <= 0)
            {
                App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, false);
                throw new \Bluefin\Exception\DataException(_APP_("The record to operate is not in expected state."));
            }

            $userExpenseRecord->_afterCancelled();
            App::getInstance()->setRegistry(Convention::KEYWORD_SYSTEM_ROLE, false);

            $db->commit();
        }
        catch (\Exception $e)
        {
            $db->rollback();

            throw $e;
        }

        return $userExpenseRecord;
    }

    public function __construct($condition = null)
    {
        parent::__construct(self::s_metadata());

        if (isset($condition))
        {
            $this->load($condition);
        }
        else
        {
            $this->reset();
        }
    }

    public function owner()
    {
        return $this->__get('user');
    }

    /**
     * Gets 流水号
     * @return string
     */
    public function getSerialNo()
    {
        return $this->__get(self::SERIAL_NO);
    }

    /**
     * Sets 流水号
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setSerialNo($value)
    {
        $this->__set(self::SERIAL_NO, $value);

        return $this;
    }

    /**
     * Gets 批次
     * @return string
     */
    public function getBatchID()
    {
        return $this->__get(self::BATCH_ID);
    }

    /**
     * Sets 批次
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setBatchID($value)
    {
        $this->__set(self::BATCH_ID, $value);

        return $this;
    }

    /**
     * Gets 支出金额
     * @return float
     */
    public function getAmount()
    {
        return $this->__get(self::AMOUNT);
    }

    /**
     * Sets 支出金额
     * @param float $value
     * @return UserExpenseRecord
     */
    public function setAmount($value)
    {
        $this->__set(self::AMOUNT, $value);

        return $this;
    }

    /**
     * Gets 用户
     * @return int
     */
    public function getUser()
    {
        return $this->__get(self::USER);
    }

    /**
     * Sets 用户
     * @param int $value
     * @return UserExpenseRecord
     */
    public function setUser($value)
    {
        $this->__set(self::USER, $value);

        return $this;
    }

    /**
     * Gets 支出用途
     * @return string
     */
    public function getUsage()
    {
        return $this->__get(self::USAGE);
    }

    /**
     * Gets 支出用途 display name
     * @return string
     */
    public function getUsage_EnumValue()
    {
        $option = $this->metadata()->getFilterOption('usage');
        return $option['enum']::getDisplayName($this->__get(self::USAGE));
    }

    /**
     * Sets 支出用途
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setUsage($value)
    {
        $this->__set(self::USAGE, $value);

        return $this;
    }

    /**
     * Gets 状态
     * @return string
     */
    public function getStatus()
    {
        return $this->__get(self::STATUS);
    }

    /**
     * Gets 状态 display name
     * @return string
     */
    public function getStatus_StateValue()
    {
        $option = $this->metadata()->getFilterOption('status');
        return $option['state']::getDisplayName($this->__get(self::STATUS));
    }

    /**
     * Sets 状态
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setStatus($value)
    {
        $this->__set(self::STATUS, $value);

        return $this;
    }

    /**
     * Gets 冻结时间
     * @return string
     */
    public function getPendingTime()
    {
        return $this->__get(self::PENDING_TIME);
    }

    /**
     * Sets 冻结时间
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setPendingTime($value)
    {
        $this->__set(self::PENDING_TIME, $value);

        return $this;
    }

    /**
     * Gets 已付时间
     * @return string
     */
    public function getPaidTime()
    {
        return $this->__get(self::PAID_TIME);
    }

    /**
     * Sets 已付时间
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setPaidTime($value)
    {
        $this->__set(self::PAID_TIME, $value);

        return $this;
    }

    /**
     * Gets 取消时间
     * @return string
     */
    public function getCancelledTime()
    {
        return $this->__get(self::CANCELLED_TIME);
    }

    /**
     * Sets 取消时间
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setCancelledTime($value)
    {
        $this->__set(self::CANCELLED_TIME, $value);

        return $this;
    }

    /**
     * Gets 状态历史
     * @return string
     */
    public function getStatusLog()
    {
        return $this->__get(self::STATUS_LOG);
    }

    /**
     * Sets 状态历史
     * @param string $value
     * @return UserExpenseRecord
     */
    public function setStatusLog($value)
    {
        $this->__set(self::STATUS_LOG, $value);

        return $this;
    }

    /**
     * @param bool $new
     * @return \WBT\Model\Weibotui\User
     */
    public function getUser_($new = false)
    {
        if ($new)
        {
            return new \WBT\Model\Weibotui\User();
        }

        if (isset($this->_links['user']))
        {
            return $this->_links['user'];
        }

        return ($this->_links['user'] = new \WBT\Model\Weibotui\User($this->getUser()));
    }

    protected function _beforeInsert()
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::_beforeInsert', 'diag');
        //支出前冻结储值账户余额
        $userAsset = new UserAsset($this->user);
        _NON_EMPTY($userAsset);
        
        $depositBalance = $userAsset->deposit_balance - $this->amount;
        if ($depositBalance < 0)
        {
            throw new \Bluefin\Exception\InvalidRequestException(_APP_('Insufficient deposit balance.'));
        }
        
        $userAsset->deposit_balance = $depositBalance;
        $userAsset->update();
        
    }

    protected function _afterCancelled(array $INPUT = null)
    {
        App::getInstance()->log()->verbose('UserExpenseRecord::_afterCancelled', 'diag');    
        //取消后返回储值账户余额
        $userAsset = new UserAsset($this->user);
        _NON_EMPTY($userAsset);
        
        $userAsset->deposit_balance += $this->amount;
        $userAsset->update();
        
        self::delete($this->pk());
        
    }
}
?>