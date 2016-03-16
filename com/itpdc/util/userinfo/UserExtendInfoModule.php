<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午8:13
 */

namespace com\itpdc\util\userinfo;
use com\itpdc\dao\AbstractModule;

class UserExtendInfoModule extends AbstractModule
{
    public function add()
    {
        echo "add";
        //写入失败抛出错误
        if (false)
        {
            throw new DbException("Insert DB Fail!");
        }
    }
    public function delete()
    {
        echo "delete";
        //写入失败抛出错误
        if (false)
        {
            throw new DbException("Delete DB Fail!");
        }
    }
    public function search()
    {
        $res = "search";
        //无返回抛出错误
        if (false)
        {
            throw new DbException("Not Found!");
        }
        return $res;
    }
    public function update()
    {
        echo "update";
        //更新失败抛出错误
        if (false)
        {
            throw new DbException("Insert DB Fail!");
        }
    }
} 