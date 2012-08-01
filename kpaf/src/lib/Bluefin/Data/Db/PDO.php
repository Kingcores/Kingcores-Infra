<?php

namespace Bluefin\Data\Db;

class PDO
{
    const CONFIG_DSN = 'dsn';
    const CONFIG_USERNAME = 'username';
    const CONFIG_PASSWORD = 'password';

    const OPTION_PERSISTENT = 'persistent';

    const FETCH_ONE_ASSOC = 0;
    const FETCH_ALL_ASSOC = 1;
    const FETCH_ONE_BY_NUM = 2;
    const FETCH_ALL_BY_NUM = 3;


    /**
     * @var \PDO
     */
    protected $_pdo;
    protected $_dsn;
    protected $_username;
    protected $_password;
    protected $_options;

    protected $_adapter;

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
            throw new \Bluefin\Exception\DatabaseException("Connecting to database failed! Detail: {$e->getMessage()}", $e);
        }
    }

    public function query($sql, array $params = null)
    {
        try
        {
            // prepare and execute the statement with profiling
            $pdoStmt = $this->_pdo->prepare($sql);

            if (!empty($params))
            {
                $pos = 1;
                foreach ($params as $dbParam)
                {
                    /**
                     * @var \Bluefin\Data\DbParam $dbParam
                     */
                    $pdoStmt->bindValue($pos++, $dbParam->value, $dbParam->dbType);
                }
            }

            $pdoStmt->execute();

            return $pdoStmt;
        }
        catch (\PDOException $e)
        {
            throw new \Bluefin\Exception\DatabaseException("Connecting to database failed! Detail: {$e->getMessage()}", $e);
        }

        // return the results embedded in the prepared statement object
        $stmt->setFetchMode($this->_dao->getFetchMode());
        return $stmt;
    }
}
