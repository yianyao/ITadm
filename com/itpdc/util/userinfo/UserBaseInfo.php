<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午5:47
 */

namespace com\itpdc\util\userinfo;
use com\itpdc\service\DbException;


class UserBaseInfo extends AbstractUser
{
    public function addInfo()
    {
        try
        {
           $this->_module->add();
        }
        catch (DbException $e)
        {
            return $e->getMessage();
        }
        return true;
    }
    public function deleteInfo()
    {
        try
        {
            $this->_module->delete();
        }
        catch (DbException $e)
        {
            return $e->getMessage();
        }
        return true;
    }
    public function searchInfo()
    {
        try
        {
           return ($this->_module->search());
        }
        catch (DbException $e)
        {
            return $e->getMessage();
        }
    }
    public function updateInfo()
    {
        try
        {
            return $this->_module->update();
        }
        catch (DbException $e)
        {
            return $e->getMessage();
        }
    }
} 