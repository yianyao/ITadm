<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午8:19
 *
 * 自动加载类
 **/
use com\itpdc\service\LoadException;
//require ("itpdc/service/LoadException.php");

function loadClass($class)
{

    try
    {
        $path = "d:\\wamp\\www\\lib\\";
        $file = $path . $class . ".php";
        if (!file_exists($file))
        {
            throw new LoadException("Not File exists!");
        }
        include $file;
    }
    catch (LoadException $e )
    {
        echo $e->getMessage() . "In" . $e->getFile() . $e->getLine();
    }
}
spl_autoload_register("loadClass");
/**
class load
{
    public function loadClass($class)
    {
        try
        {
            $path = "d:\\wamp\\www\\lib\\";
            $file = $path . $class . ".php";
            if (!file_exists($file))
            {
                throw new LoadException("Not File exists!");
            }
            include $file;
            throw new LoadException("Not File exists!");
        }
        catch (LoadException $e )
        {
            echo $e->getMessage() . "In" . $e->getFile() . $e->getLine();
        }
    }
    public static function load($class)
    {
        spl_autoload_register($this->loadClass($class));
    }
}
 **/