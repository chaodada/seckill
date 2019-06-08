<?php

class common
{
    /**
     * 受到保护的方法
     * 渲染页面
     * @param string $view 模板路径
     * @param array $data 数据
     * @throws Exception
     */
    protected function render($view = '', $data = [])
    {
        //定义模板文件路径
        $viewpath = SEC_ROOT_PATH . '/view/';
        //拼接模板文件路径  如果模板名不为空直接用模板名称  否则使用对应控制器名 下边的 对应方法名称的模板文件
        $viewfile = $viewpath . ($view ? $view : CONTROLLER . '/' . ACTION) . '.php';
        //如果模板文件存在
        if (is_file($viewfile)) {
            /**
             * 此处代码作用 ：
             * 估计是为了渲染模板的时候不让 渲染一点模板 加载一点模板 而是服务端渲染完毕之后 直接统一一下发送到前前端
             */
            //ob_start()-打开输出缓冲区，所有的输出信息不在直接发送到浏览器，而是保存在输出缓冲区里面,可选得回调函数用于处理输出结果信息。
            // 页面缓存
            ob_start();
            //ob_implicit_flush()-打开或关闭绝对刷新，默认为关闭，打开后ob_implicit_flush(true)，所谓绝对刷新，即当有输出语句(e.g: echo)被执行时，便把输出直接发送到浏览器，而不再需要调用flush()或等到脚本结束时才输出。
            ob_implicit_flush(0);
            //extract()-函数从数组中将变量导入到当前的符号表。
            //该函数使用数组键名作为变量名，使用数组键值作为变量值。针对数组中的每个元素，将在当前符号表中创建对应的一个变量。
            //第二个参数 type 用于指定当某个变量已经存在，而数组中又有同名元素时，extract() 函数如何对待这样的冲突。
            //该函数返回成功导入到符号表中的变量数目。

            // 模板阵列变量分解成为独立变量   EXTR_OVERWRITE - 默认。如果有冲突，则覆盖已有的变量。
            extract($data, EXTR_OVERWRITE);
            //引入模板文件
            include $viewfile;
            // 拿到缓冲区内容 并关闭缓冲区
            $content = ob_get_clean();
            //输出缓冲区内容 （相当于 服务器将 模板中 的变量啥的都解析完成 变成html 内容）
            echo $content;
        } else {
            //抛出模板不存在的异常
            throw new Exception("模板文件不存在");
        }
    }


    /**
     * ajax返回
     * @param $data
     */
    protected function ajaxreturn($data)
    {
        $return = json_encode($data);
        exit($return);
    }

}
