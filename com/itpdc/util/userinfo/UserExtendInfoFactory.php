<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午6:02
 */

namespace com\itpdc\util\userinfo;

class UserExtendInfoFactory
{
    public static function createModule()
    {
        return new UserExtendInfoModule();
    }
} 