<?php
/*
 * 商品控制器
 */

class goods extends common
{

    //商品模型
    private $_goodsModel = null;
    //redis模型
    private $_redis = null;

    /**
     * 商品控制器构造方法
     * goods constructor.
     */
    public function __construct()
    {
        //如果当前商品模型对象不存在
        if ($this->_goodsModel === null) {
            //创建商品模型对象
            $this->_goodsModel = new GoodsModel();
        }
        //如果当前rides模型对象不存在
        if ($this->_redis === null) {
            //创建redis模型对象
            $this->_redis = new QRedis();
        }
    }

    /**
     * 查看商品列表
     * @throws Exception
     */
    public function goodsLits()
    {
        //查询所有的商品信息 返回数组
        $list = $this->_goodsModel->getGoodses();
        //var_dump($list);
        //拿到redis对象
        $redis = $this->_redis;
        //遍历所有商品信息
        foreach ($list as $key => &$value) {
            //拿到单个商品id
            $id = $value['id'];
            //制定单个商品的 redis的key
            $key = 'goods_list_' . $id;
            //查询该商品的队列长度
            $count = $redis->listcount($key);
            // 将队列长度 存入数组中
            $value['rediscount'] = $count;
        }
        //将数据分配到模板中 并且渲染模板
        $this->render('', ['list' => $list]);
    }

    /**
     * 设置商品库存
     */
    public function setGoodsCount()
    {
        //接受商品id
        $gid = $_POST['gid'];
        //接受商品数量
        $count = $_POST['counts'];
        //按照商品id 查询出商品信息
        $goodsInfo = $this->_goodsModel->getGoods($gid);
        //如果商品存在
        if ($goodsInfo) {
            //获取商品id
            $id = $goodsInfo['id'];
            //更新商品的数量
            $result = $this->_goodsModel->setGoodsCount($id, $count);
            //如果数量更新成功
            if ($result) {
                //更新redis list

                $redis = $this->_redis; //拿到redis对象
                // 构建商品key
                $key = 'goods_list_' . $id;
                // 以防key已经存在 所以redis中删除这个key
                $redis->clearlist($key);
                //遍历商品个数 追加到当前商品的队列中
                for ($i = 1; $i <= $count; $i++) {
                    //每次对当前商品的队列 追加一个元素 1
                    $redis->addRlist($key, 1);
                }
                //返回成功信息
                $this->ajaxreturn(['status' => 1, 'info' => '编辑成功']);
            }
        }
        //返回失败信息
        $this->ajaxreturn(['status' => 0, 'info' => '编辑失败']);
    }

}
