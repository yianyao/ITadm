<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午5:42
 */

namespace com\itpdc\util\userinfo;
use com\itpdc\dao\AbstractModule;

abstract class AbstractUser
{
    //数据库对象引用
    protected $_module;
    public function __construct(AbstractModule $module)
    {
        $this->_module = $module;
    }
    abstract function addInfo();
    abstract function deleteInfo();
    abstract function searchInfo();
    abstract function updateInfo();
} 