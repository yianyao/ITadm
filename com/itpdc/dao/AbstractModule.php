<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午8:04
 */

namespace com\itpdc\dao;


abstract class AbstractModule
{
    abstract function add();
    abstract function delete();
    abstract function search();
    abstract function update();
} 