<?php
/**
 * Example using Ding with Doctrine.
 * 
 * PHP Version 5
 *
 * @category Ding
 * @package  
 * @author   Agustín Gutiérrez <agu.gutierrez@gmail.com>
 * @license  http://www.noneyet.ar/ Apache License 2.0
 * @version  SVN: $Id$
 * @link     http://www.noneyet.ar/
 */

// no php notices please...
date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);


/**
 * Note: make sure Doctrine library is defined in your include path.
 */
define('DOCTRINE_LIB_PATH', '/usr/php-5.3/lib/php');
ini_set(
    'include_path',
    implode(
        PATH_SEPARATOR,
        array(
            DOCTRINE_LIB_PATH,
            __DIR__ .'/entities',
            ini_get('include_path'),
            __DIR__ .DIRECTORY_SEPARATOR
            .implode(DIRECTORY_SEPARATOR, array('..', '..', '..', 'src', 'mg')),
                )
            )
        );

require_once 'entities/Person.php';

// register Doctrine class loader
require 'Doctrine/Common/ClassLoader.php';
$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', DOCTRINE_LIB_PATH);
$classLoader->register();

// register Ding autoloader
require_once 'Ding/Autoloader/Autoloader.php'; // Include ding autoloader.
Autoloader::register(); // Call autoloader register for ding autoloader.

use Ding\Container\Impl\ContainerImpl;
use Doctrine\ORM\EntityManager;
try
{
    $myProperties = array(
        'doctrine.proxy.dir' => './proxies',
        'doctrine.proxy.autogenerate' => true,
        'doctrine.proxy.namespace' => "\\Test\\Proxies",
        'doctrine.entity.path' => __DIR__ ."/entities",
        'doctrine.db.driver' => "pdo_sqlite",
        'doctrine.db.path' => __DIR__ ."/db.sqlite3",
        'user.name' => 'nobody',
        'log.dir' => '/tmp/alogdir',
        'log.file' => 'alog.log'
	 );
    $dingProperties = array(
        'ding' => array(
            'factory' => array(
                'bdef' => array(
                	'xml' => array('filename' => __DIR__ . '/beans.xml'),
                	'annotation' => array()
                ),
                'properties' => $myProperties
            ),
    		  'cache' => array(
    		    'proxy' => array('directory' => '/tmp/Ding/proxy'),
          	 'bdef' => array('impl' => 'apc'),
        	    'beans' => array('impl' => 'dummy')
           )
        )
    );
    $a = ContainerImpl::getInstance($dingProperties);
    $em = $a->getBean('repository-locator');
    createSchema($myProperties);

    $person = new Person('foobar', 'Foo', 'Bar');
    echo "Persisting $person\n";
    $em->persist($person);
    $em->flush();
    $person = $em->find('Person', 1);

    echo "Retrieved from db:$person\n";

    @unlink($myProperties['doctrine.db.path']);
} catch(Exception $exception) {
    echo $exception . "\n";
}

function createSchema($props) {
    $schema = file_get_contents(__DIR__ .'/schema.sql');
    $config = new \Doctrine\DBAL\Configuration();
    //..
    $connectionParams = array(
        'driver' => $props['doctrine.db.driver'],
        'path' => $props['doctrine.db.path'],
    );
    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    $conn->executeQuery($schema);
}