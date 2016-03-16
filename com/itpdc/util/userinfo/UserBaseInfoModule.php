<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午8:12
 */

namespace com\itpdc\util\userinfo;
use com\itpdc\dao\AbstractModule;
use com\itpdc\service\DbException;

class UserBaseInfoModule extends AbstractModule
{
    public function add()
    {
        echo "addInfo";
        //写入失败抛出错误
        if (false)
        {
            throw new DbException("Insert DB Fail!");
        }
    }
    public function delete()
    {
        echo "delete";
        //删除失败抛出错误
        if (false)
        {
            throw new DbException("Delete DB Fail!");
        }
    }
    /**
     * 从数据库获取结果，没有结果则抛出异常.
     *
     * @throws DbException if not found in DB
     *
     * @return mixed:array or Exception
    **/
    public function search()
    {
        $res = array("a","b");
         if (is_array($res) && count($res) > 0)
        {
            return $res;
        }else
        {
            throw new DbException("Not Found!");
        }
    }
    /**
     *
    **/
    public function update()
    {
        $s = "update";

        //更新失败抛出错误
        if (false)
        {
            throw new DbException("Update DB Fail!");
        }
    }
} 