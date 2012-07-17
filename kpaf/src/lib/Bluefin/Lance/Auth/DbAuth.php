<?php

namespace Bluefin\Lance\Auth;

use Bluefin\Lance\SchemaSet;
use Bluefin\View;

class DbAuth implements AuthInterface
{
    public function generate($authName, array $config)
    {
        $result = array();
        $schemaSet = new SchemaSet($config['db']);
        $schemaSet->loadEntities();

        $dir = APP_LIB . '/' . $schemaSet->getNamespace() . '/Auth';
        ensure_dir_exist($dir);

        $view = new View(
            array(
                'renderer' => 'twig',
                'templateExt' => '.twig',
                'root' => ROOT,
                'template' => \Bluefin\Lance\Convention::getTemplatePath('auth/db_auth'),
                'filters' => array(
                    'array_export' => '\Bluefin\Lance\Convention::dumpArray',
                    'format_value' => '\Bluefin\Lance\Convention::dumpValue',
                    'pascal' => 'usw_to_pascal',
                    'functor' => '\Bluefin\Lance\PHPCodingLogic::parseFunctor',
                    'const' => 'usw_to_const'
                ),
                'tests' => array(
                    'array' => 'is_array'
                )
            )
        );

        foreach ($config as $key => $value)
        {
            $view->set($key, $value);
        }

        $joinColumns = array();
        $authData = $config['persistence']['data'];

        foreach ($authData as $column)
        {
            if (strpos($column, '.') !== false)
            {
                $parts = explode('.', $column, 2);
                $withField = $parts[0];

                if (array_key_exists($withField, $joinColumns))
                {
                    array_push_unique($joinColumns[$withField], $parts[1]);
                }
                else
                {
                    $joinColumns[$withField] = array($parts[1]);
                }
            }
        }

        $dbName = usw_to_pascal($schemaSet->getSchemaSetName());
        $modelName = usw_to_pascal($config['table']);
        $authClassName = usw_to_pascal($authName);
        $view->namespace = $schemaSet->getNamespace();
        $view->authName = $authName;
        $view->authClassName = $authClassName;
        $view->entity = $schemaSet->getUsedEntity($config['table']);
        $view->modelClass = "\\{$schemaSet->getNamespace()}\\Model\\$dbName\\$modelName";

        $content = $view->render();
        $filename = $dir . "/{$authClassName}.php";
        file_put_contents($filename, $content);
        $result[] = $filename;

        $view->setOption('template', \Bluefin\Lance\Convention::getTemplatePath('auth/db_auth_yml'));

        $dir = APP_ETC . '/auth';
        ensure_dir_exist($dir);

        $content = $view->render();
        $filename = $dir . "/{$authName}.dev.yml";
        file_put_contents($filename, $content);
        $result[] = $filename;

        return $result;
    }
}
