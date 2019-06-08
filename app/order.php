<?php
/*
 * 订单控制器
 */

class order extends common
{

    //订单模型对象
    private $_orderModel = null;
    //商品模型对象
    private $_goodsModel = null;


    public function __construct()
    {

        if ($this->_orderModel === null) {
            $this->_orderModel = new OrderModel();
        }
        //如果当前商品模型对象不存在
        if ($this->_goodsModel === null) {
            //创建商品模型对象
            $this->_goodsModel = new GoodsModel();
        }
    }

    /**
     * 查看订单列表
     */
    public function orderList()
    {
        //获取商品id
        $gid = $_GET['gid'];
        //获取商品信息
        $goodsInfo = $this->_goodsModel->getGoods($gid);
        if (!$goodsInfo) {
            exit('商品不存在');
        }
        //根据商品id 获取商品订单
        $list = $this->_orderModel->getOrdersByGid($gid);
        //分配数据 渲染模板
        $this->render('', ['list' => $list]);
    }

}
