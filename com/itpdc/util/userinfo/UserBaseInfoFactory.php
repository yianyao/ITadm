<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午5:58
 */

namespace com\itpdc\util\userinfo;

class UserBaseInfoFactory implements IFactory
{
    public static function createModule()
    {
        return new UserBaseInfoModule();
    }
} 