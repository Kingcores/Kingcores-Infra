<?php

namespace Bluefin\Data\Db;

use Bluefin\App;
use Bluefin\Data\Type;
use Bluefin\Data\Database;

class PDO
{
    const CONFIG_DSN = 'dsn';
    const CONFIG_USERNAME = 'username';
    const CONFIG_PASSWORD = 'password';

    const OPTION_PERSISTENT = 'persistent';

    /**
     * @var \PDO
     */
    protected $_pdo;
    protected $_dsn;
    protected $_username;
    protected $_password;
    protected $_options;

    protected $_adapter;

    private $_transactionCounter;

    public function __construct(DbInterface $adapter, array $config)
    {
        $this->_adapter = $adapter;

        $this->_dsn = array_try_get($config, self::CONFIG_DSN, null, true);

        if (is_null($this->_dsn))
        {
            throw new \Bluefin\Exception\InvalidOperationException(
                "DSN is required for creating PDO instance."
            );
        }

        $this->_username = array_try_get($config, self::CONFIG_USERNAME, null, true);
        $this->_password = array_try_get($config, self::CONFIG_PASSWORD, null, true);

        $persistent = array_try_get($config, self::OPTION_PERSISTENT, null, true);

        if (isset($persistent))
        {
            \Bluefin\Data\Type::convertBool(self::OPTION_PERSISTENT, $persistent);
        }
        else
        {
            $persistent = 1;
        }

        $this->_options = $config;

        $this->_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $this->_options[\PDO::ATTR_PERSISTENT] = $persistent === 1;

        $this->_transactionCounter = 0;
    }

    public function ensureConnected()
    {
        try
        {
            isset($this->_pdo) ||
                ($this->_pdo = new \PDO(
                    $this->_dsn, $this->_username, $this->_password, $this->_options
                ));
        }
        catch (\PDOException $e)
        {
            throw new \Bluefin\Exception\DatabaseException("Connecting to database failed! Detail: {$e->getMessage()}");
        }
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->_pdo;
    }

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return mixed
     */
    public function fetchAll($sql, array $params = null, $fetchMode = Database::FETCH_ALL_TO_ASSOC)
    {
        return $this->_query($sql, $params)->fetchAll($fetchMode);
    }

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return mixed
     */
    public function fetchRow($sql, array $params = null, $fetchMode = Database::FETCH_ROW_TO_ASSOC)
    {
        return $this->_query($sql, $params)->fetch($fetchMode);
    }

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $columnIndex
     * @return mixed
     */
    public function fetchValue($sql, array $params = null, $columnIndex = 0)
    {
        return $this->_query($sql, $params)->fetchColumn($columnIndex);
    }

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $columnIndex
     * @param bool $unique
     * @return mixed
     */
    public function fetchColumn($sql, array $params = null, $columnIndex = 0, $unique = false)
    {
        $fetchMode = Database::FETCH_COLUMN;
        if ($unique) $fetchMode |= Database::FETCH_COLUMN_UNIQUE;

        return $this->_query($sql, $params)->fetchAll($fetchMode, $columnIndex);
    }

    /**
     * @abstract
     * @param $sql
     * @param array $params
     * @param int $groupByColumnIndex
     * @return array
     */
    public function fetchGroup($sql, array $params = null, $groupByColumnIndex = 0)
    {
        $fetchMode = Database::FETCH_COLUMN | Database::FETCH_GROUP;
        return $this->_query($sql, $params)->fetchAll($fetchMode, $groupByColumnIndex);
    }

    /**
     * @param $sql
     * @param array $params
     * @return int
     */
    public function query($sql, array $params = null)
    {
        $stmt = $this->_query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * @param null $name
     * @return mixed
     */
    public function lastInsertId($name = null)
    {
        $this->ensureConnected();
        return $this->_pdo->lastInsertId($name);
    }

    /**
     * @throws \Bluefin\Exception\DatabaseException
     */
    public function beginTransaction()
    {
        ++$this->_transactionCounter;

        if ($this->_transactionCounter > 1)
        {
            return;
        }

        //[+]DEBUG
        if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
        {
            App::getInstance()->log()->debug('TRANSACTION BEGUN.', \Bluefin\Log::CHANNEL_DIAG);
        }
        //[-]DEBUG

        $this->ensureConnected();
        if (false === $this->_pdo->beginTransaction())
        {
            throw new \Bluefin\Exception\DatabaseException('Failed to begin a database transaction!');
        }
    }

    public function commit()
    {
        --$this->_transactionCounter;

        if ($this->_transactionCounter == 0)
        {
            //[+]DEBUG
            if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
            {
                App::getInstance()->log()->debug('TRANSACTION COMMITTED.', \Bluefin\Log::CHANNEL_DIAG);
            }
            //[-]DEBUG
            $this->ensureConnected();
            if (false === $this->_pdo->commit())
            {
                throw new \Bluefin\Exception\DatabaseException('Failed to commit a database transaction!');
            }
        }
    }

    public function rollBack()
    {
        if ($this->_transactionCounter > 0)
        {
            $this->_transactionCounter = 0;

            //[+]DEBUG
            if (App::getInstance()->log()->isLogOn(\Bluefin\Log::DEBUG, \Bluefin\Log::CHANNEL_DIAG))
            {
                App::getInstance()->log()->debug('TRANSACTION ROLLBACKED.', \Bluefin\Log::CHANNEL_DIAG);
            }
            //[-]DEBUG

            $this->ensureConnected();
            if (false === $this->_pdo->rollBack())
            {
                throw new \Bluefin\Exception\DatabaseException('Failed to rollback a database transaction!');
            }
        }
    }

    /**
     * @param $sql
     * @param array $params
     * @return \PDOStatement
     * @throws \Bluefin\Exception\DatabaseException
     */
    protected function _query($sql, array $params = null)
    {
        $this->ensureConnected();

        try
        {
            // prepare and execute the statement with profiling
            $pdoStmt = $this->_pdo->prepare($sql);

            if (!empty($params))
            {
                $pos = 1;
                foreach ($params as $dbParam)
                {
                    $pdoStmt->bindValue($pos++, $dbParam[0], $this->_translateType($dbParam[1]));
                }
            }

            $flag = $pdoStmt->execute();

            if (false === $flag)
            {
                $eInfo = $pdoStmt->errorInfo();

                throw new \Bluefin\Exception\DatabaseException("SQL exception: {$eInfo[2]}");
            }

            return $pdoStmt;
        }
        catch (\PDOException $e)
        {
            throw new \Bluefin\Exception\DatabaseException($e->getMessage(), \Bluefin\Common::HTTP_INTERNAL_SERVER_ERROR, $e);
        }
    }

    protected function _translateType($type)
    {
        switch ($type)
        {
            case Type::TYPE_INT:
            case Type::TYPE_BOOL:
                return \PDO::PARAM_INT;

            case Type::TYPE_FLOAT:
            case Type::TYPE_MONEY:
            case Type::TYPE_DATE:
            case Type::TYPE_TIME:
            case Type::TYPE_DATE_TIME:
            case Type::TYPE_TIMESTAMP:
            case Type::TYPE_TEXT:
            case Type::TYPE_JSON:
            case Type::TYPE_XML:
            case Type::TYPE_PASSWORD:
            case Type::TYPE_IDNAME:
            case Type::TYPE_DIGITS:
            case Type::TYPE_EMAIL:
            case Type::TYPE_PHONE:
            case Type::TYPE_URL:
            case Type::TYPE_PATH:
            case Type::TYPE_IPV4:
                return \PDO::PARAM_STR;

            case Type::TYPE_BINARY:
            case Type::TYPE_UUID:
                return \PDO::PARAM_LOB;

            default:
                App::assert(false, "Unknown type [{$type}].");
                return \PDO::PARAM_STR;
        }
    }
}
