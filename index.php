<?php

/* ------------------------------------------------------
 * 系统入口文件
 * 访问模式：
 * 网址/index.php?app=app&c=order&a=buypub
 * ------------------------------------------------------
 */

//设置时区
date_default_timezone_set('Asia/Shanghai');
//引入框架初始化文件
include 'Common/bootstrop.php';

//获取访问应用路径 默认路径 app
$app = isset($_GET['app']) ? $_GET['app'] : 'app';

//获取访问应用控制器 默认商品控制器
$controller = isset($_GET['c']) ? $_GET['c'] : 'goods';

//获取访问应用操作  默认商品列表方法
$action = isset($_GET['a']) ? $_GET['a'] : 'goodsLits';

//获取实际的控制器路径
$file = SEC_ROOT_PATH . '/' . $app . '/' . $controller . '.php';

//如果控制器存在
if (is_file($file)) {
    //定义常量APP 值为当前应用的 文件夹名称
    defined('APP') or define('APP', $app);
    //定义控制器为当前 控制器的名称
    defined('CONTROLLER') or define('CONTROLLER', $controller);
    //定义方法为当前 方法的名称
    defined('ACTION') or define('ACTION', $action);
    //引入要访问的控制器
    include $file;
    //实例化要访问的控制器
    $appClass = new $controller();
    //执行要访问的方法
    $appClass->$action();
} else {
    //抛出异常
    throw new Exception("应用错误", 1);
}