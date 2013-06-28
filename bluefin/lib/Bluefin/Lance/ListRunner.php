<?php

namespace Bluefin\Lance;

use Bluefin\App;
use Bluefin\Lance\Schema;

class ListRunner
{
    private $_config;

    /**
     * @var \Bluefin\Lance\Schema
     */
    private $_schema;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    public function run($filename)
    {
        $lanceName = $this->_config['lance'];
        $dbName = $this->_config['dbname'];

        $dbTask = new \Bluefin\Lance\Task\DatabaseTask();
        $dbTask->prepareDataScript($this->_getSchema($lanceName));

        if (!file_exists($filename))
        {
            throw new \Bluefin\Exception\FileNotFoundException($filename);
        }

        $path = dirname($filename);
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $ymlRunner = null;
        $schema = null;

        $mysqlCmd = "mysql -h{$this->_config['host']} -h{$this->_config['host']} -P{$this->_config['port']} --default-character-set={$this->_config['charset']} -u{$this->_config['username']} -p{$this->_config['password']} {$dbName}";

        foreach ($lines as $line)
        {
            $line = trim($line);
            if ($line == '' || $line[0] == '#') continue;

            $file = build_path($path, $line);

            if (!file_exists($file))
            {
                throw new \Bluefin\Exception\FileNotFoundException($file);
            }

            $file = realpath($file);

            $ext = mb_strtolower(pathinfo($file, PATHINFO_EXTENSION));

            Arsenal::getInstance()->log()->info("Processing {$file} ...", Convention::LOG_CAT_LANCE_CORE);

            switch ($ext)
            {
                //SQL script file
                case 'sql':
                    if (!exec_shell_command(
                        "{$mysqlCmd} < {$file}"
                    ))
                    {
                        throw new \ErrorException("Failed to execute mysql script!");
                    }
                    break;

                //PHP code file
                case 'php':
                    include($file);
                    break;

                //YAML config file
                case 'yml':
                    if (!isset($ymlRunner))
                    {
                        $ymlRunner = new YAMLRunner($this->_getSchema($lanceName));
                    }

                    $ymlRunner->run($file);
                    break;

                //Bluefin data script
                case 'bsd':
                    $this->_runBluefinDataScript($this->_getSchema($lanceName), $file);
                    break;

                default:
                    throw new \Bluefin\Exception\BluefinException("Unknown file type: {$ext}! File: {$file}");
            }

            Arsenal::getInstance()->log()->info("EXECUTED: {$file}", Convention::LOG_CAT_LANCE_CORE);
        }
    }

    private function _getSchema($schemaName)
    {
        if (isset($this->_schema)) return $this->_schema;

        $this->_schema = Arsenal::getInstance()->loadSchema($schemaName);
        $this->_schema->loadEntities();

        return $this->_schema;
    }

    private function _runBluefinDataScript(Schema $schema, $sourceFile)
    {
        $content = '';

        $lineNum = 0;
        $lines = file($sourceFile, FILE_IGNORE_NEW_LINES);
        $commandLineNum = 0;
        $command = '';
        $target = '';
        $columns = array();
        $dataBuf = array();

        foreach ($lines as $line)
        {
            $lineNum++;
            $line = trim($line);
            if ($line == '' || $line[0] == '#') continue;

            if ($line[0] == '-')
            {
                // execute last command
                if ($command != '')
                {
                    $content .= $this->_translateCommand($schema, $command, $target, $columns,
                        $dataBuf, $lineNum);

                    $target = '';
                    $columns = array();
                    $dataBuf = array();
                }

                // parse new command
                $body = mb_substr($line, 1);
                Arsenal::getInstance()->log()->info("Executing {$body} ...", Convention::LOG_CAT_LANCE_CORE);

                $ps = explode(':', $body, 2);
                $p1 = trim($ps[0]);
                $cs = explode(' ', $p1, 2);
                $commandLineNum = $lineNum;
                $command = $cs[0];
                count($cs) > 1 && ($target = $cs[1]);
                count($ps) > 1 && ($columns = explode(',', $ps[1]));
            }
            else
            {
                $dataBuf[] = $line;
            }
        }

        if ($command != '')
        {
            $content .= $this->_translateCommand($schema, $command, $target, $columns, $dataBuf, $commandLineNum);
        }

        return App::getInstance()->db($schema->getSchemaName())->getAdapter()->query($content);
    }

    private function _translateCommand(Schema $schema, $command, $target,
                                           array $columns, array $dataBuf, $lineNum)
    {
        switch ($command)
        {
            case 'set':
                if (count($columns) > 1)
                {
                    throw new \Bluefin\Lance\Exception\GrammarException("Invalid value for set statement! Line: {$lineNum}");
                }
                return "SET @@{$target} = " . \Bluefin\Lance\Convention::dumpValue(trim($columns[0])) . ";\n";

            default:
                return $schema->getDbLancer()->translateSQL($command, $target, $columns, $dataBuf);
        }
    }
}
