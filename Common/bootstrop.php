<?php



//echo __FILE__ ; // 取得当前文件的绝对地址，结果：D:\www\test.php 
//echo dirname(__FILE__); // 取得当前文件所在的绝对目录，结果：D:\www\ 
//echo dirname(dirname(__FILE__)); //取得当前文件的上一层目录名，结果：D:\ 

// 检查常量是否存在 不存在就定义常量 站点跟目录路径
defined('SEC_ROOT_PATH') or define('SEC_ROOT_PATH', dirname(dirname(__FILE__)));


// 引入框架函数库 *
include 'function.php';
//引入框架公共文件
include 'app/common.php';
//引入框架模型驱动类
include 'Model/Model.php';
//引入订单模型
include 'Model/OrderModel.php';
//引入商品模型
include 'Model/GoodsModel.php';
//引入redis驱动
include 'Redis/QRedis.php';
