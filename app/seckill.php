<?php
/* * ********************************************
 * 抢购模块
 * ab -n 1000 -c 100 http://127.0.0.1/Seckill/buy.php
 *
 */

class seckill extends common
{

    private $_orderModel = null;
    private $_goodsModel = null;
    private $_redis = null;

    /*
     * 错误信息
     */
    protected $_error = '';

    /**
     * 构造器
     */
    public function __construct()
    {
        if ($this->_orderModel === null) {
            $this->_orderModel = new OrderModel();
        }

        if ($this->_goodsModel === null) {
            $this->_goodsModel = new GoodsModel();
        }

        if ($this->_redis === null) {
            $this->_redis = new QRedis();
        }
    }

    /*
     * 秒杀API
     */

    public function addQsec()
    {
        //获取商品id
        $gid = intval($_GET['gid']);
        //获取使用那种方式处理秒杀
        $type = isset($_GET['type']) ? $_GET['type'] : 'mysql';
        switch ($type) { //mysql
            case 'mysql':
                $this->order_check_mysql($gid);
                echo $this->getError();
                break;
            case 'redis': //redis
                $this->order_check_redis($gid);
                echo $this->getError();
                break;
            case 'transaction': //transaction
                $this->order_check_transaction($gid);
                echo $this->getError();
                break;
            default:
                echo '类型错误';
                break;
        }
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->_error;
    }

    /*
     * 基于mysql验证库存信息
     * @desc 高并发下会导致超卖
     */

    protected function order_check_mysql($gid)
    {

        //获取商品模型
        $model = $this->_goodsModel;
        //获取pdo对象
        $pdo = $model->getHandler();
        //处理商品id
        $gid = intval($gid);

        /**
         * 1：$sql_forlock如果不加事务，不加写锁：
         *      超卖非常严重，就不说了
         * 2：$sql_forlock如果不加事务，只加写锁：
         *      第一个会话读$sql_forlock时加写锁，第一个会话$sql_forlock查询结束会释放该行锁.
         *      第二个会话在第一个会话释放后读$sql_forlock的写锁时，会再次$sql_forlock查库存
         *      导致超卖现象产生
         */
        //根据商品id 查询商品 取一条 加锁
        $sql_forlock = 'select * from goods where id = ' . $gid . ' limit 1 for update';

        //根据商品id 查询商品 取一条 不加锁
        //$sql_forlock	= 'select * from goods where id = '.$gid .' limit 1';

        //执行sql  关联数组形式
        $result = $pdo->query($sql_forlock, PDO::FETCH_ASSOC);
        //拿到结果
        $goodsInfo = $result->fetch();

        //如果库存大于 0
        if ($goodsInfo['counts'] > 0) {
            //去库存
            $gid = $goodsInfo['id'];
            //去库存语句
            $sql_inventory = 'UPDATE goods SET counts = counts - 1 WHERE id = ' . $gid;
            //执行
            $result = $this->_goodsModel->exect($sql_inventory);
            //去库存成功
            if ($result) {
                //创订单
                $data = [];
                //获取订单号
                $data['order_id'] = $this->_orderModel->buildOrderNo();
                //获取商品id
                $data['goods_id'] = $goodsInfo['id'];
                //时间
                $data['addtime'] = time();
                //用户id
                $data['uid'] = 1001;
                //创建订单写入订单表
                $order_rs = $this->_orderModel->create_order($data);
                if ($order_rs) {
                    $this->_error = '购买成功';
                    return true;
                }
            }
        }

        $this->_error = '库存不足';
        return false;
    }

    /**
     * 基于redis队列验证库存信息
     * @desc Redis是底层是单线程的,命令执行是原子操作,包括lpush,lpop等.高并发下不会导致超卖
     */
    protected function order_check_redis($gid)
    {
        //获取指定商品
        $goodsInfo = $this->_goodsModel->getGoods($gid);
        //判断商品存不存在
        if (!$goodsInfo) {
            $this->_error = '商品不存在';
            return false;
        }

        //制定 商品key
        $key = 'goods_list_' . $goodsInfo['id'];

        //$count = $this->_redis->getHandel()->lpop($key);

        //移除列表头元素 并返回移除的元素值  当全部库存移除完之后 会返回flase
        $count = $this->_redis->lpoplist($key);

        //库存不足
        if (!$count) {
            $this->_error = '库存不足';
            return false;
        }

        //生成订单
        $data = [];
        $data['order_id'] = $this->_orderModel->buildOrderNo();
        $data['goods_id'] = $goodsInfo['id'];
        $data['addtime'] = time();
        $data['uid'] = 1002;
        $order_rs = $this->_orderModel->create_order($data);

        //库存减少
        $gid = $goodsInfo['id'];
        $sql = 'UPDATE goods SET counts = counts - 1 WHERE id = ' . $gid;
        $result = $this->_goodsModel->exect($sql);
        $this->_error = '购买成功';
        return true;
    }

    /**
     * 基于mysql事务验证库存信息
     * @desc 事务 和 行锁 模式,高并发下不会导致超卖，但效率会慢点
     * 说明：
     * 如果$sql_forlock不加写锁，并发时，$sql_forlock查询的记录存都大于0，可以减库存操作.
     * 如果$sql_forlock加了写锁，并发时，$sql_forlock查询是等待第一次链接释放后查询.所以库存最多就是5
     */

    protected function order_check_transaction($gid)
    {

        //获取商品模型
        $model = $this->_goodsModel;
        //获取pdo对象
        $pdo = $model->getHandler();
        //获取商品id
        $gid = intval($gid);

        try {
            //开启事务处理
            $pdo->beginTransaction();


            /**
             * 1：$sql_forlock如果只加事务，不加写锁：
             * 开启事务
             * 因为没有加锁，读$sql_forlock后，并发时$sql_inventory (去库存) 之前还可以再读。
             * $sql_inventory (去库存) 之后和commit之前才会锁定
             * 出现超卖跟事务的一致性不冲突
             *
             *
             * 2：$sql_forlock如果加了事务，又加读锁：
             * 开启事务
             * 第一个会话读$sql_forlock时加读锁，并发时，第二个会话也允许获得$sql_forlock的读锁，
             * 但是在第一个会话执行去库存操作时（写锁），写锁便会等待第二个会话的读锁，第二个会话执行写操作时，写锁便会等待第一个会话的读锁，
             * 出现死锁
             * 3：$sql_forlock如果加了事务，又加写锁：
             * 开启事务
             * 第一个会话读$sql_forlock时加写锁，直到commit才会释放写锁，并发查询不会出现超卖现象。
             *
             */

            $sql_forlock = 'select * from goods where id = ' . $gid . ' limit 1 for update';

            //加了事务，又加读锁：
            //$sql_forlock		= 'select * from goods where id = '.$gid .' limit 1 LOCK IN SHARE MODE';

            //只加事务，不加写锁：
            //$sql_forlock		= 'select * from goods where id = '.$gid .' limit 1';

            //执行sql  关联数组形式
            $result = $pdo->query($sql_forlock, PDO::FETCH_ASSOC);

            //拿到商品信息
            $goodsInfo = $result->fetch();

            //如果库存大于0
            if ($goodsInfo['counts'] > 0) {
                //去库存
                $gid = $goodsInfo['id']; //获取商品id
                $sql_inventory = 'UPDATE goods SET counts = counts - 1 WHERE id = ' . $gid; // 减少库存语句
                //执行减少库存
                $result = $this->_goodsModel->exect($sql_inventory);
                //减少库存失败
                if (!$result) {
                    //事务回滚
                    $pdo->rollBack();
                    $this->_error = '库存减少失败';
                    return false;
                }
                //创订单
                $data = [];
                //获取订单号
                $data['order_id'] = $this->_orderModel->buildOrderNo();
                //获取当前商品id
                $data['goods_id'] = $goodsInfo['id'];
                //获取当前用户id
                $data['uid'] = '1003';
                //获取当前时间
                $data['addtime'] = time();

                //模型写入订单记录
                $order_rs = $this->_orderModel->create_order($data);

                //$sql = 'insert into orders (order_id,goods_id,uid,addtime) values ("' . $data['order_id'] . '","' . $data['goods_id'] . '","' . $data['uid'] . '","' . $data['addtime'] . '")';
                //$result = $pdo->exec($sql);

                //订单记录写入失败
                if (!$order_rs) {
                    //事务回滚
                    $pdo->rollBack();
                    $this->_error = '订单创建失败';
                    return false;
                }
                //提交事务
                $pdo->commit(); //提交
                $this->_error = '购买成功';
                return true;
            } else {
                $this->_error = '库存不足';
                return false;
            }
        } catch (PDOException $e) {
            //pdo异常
            echo $e->getMessage();
            //事务回滚
            $pdo->rollBack();
        }
    }

    /*
     * 创建订单
     * mysql 事物处理，也可以用存储过程
     *
     */
    private function create_order($goodsInfo)
    {
        //生成订单
        $data = [];
        $data['order_id'] = $this->_orderModel->buildOrderNo();
        $data['goods_id'] = $goodsInfo['id'];
        $data['addtime'] = time();
        $data['uid'] = 1;
        $order_rs = $this->_orderModel->create_order($data);

        //库存减少
        $gid = $goodsInfo['id'];
        $sql = 'UPDATE goods SET counts = counts - 1 WHERE id = ' . $gid;
        $result = $this->_goodsModel->exect($sql);
        return true;
    }

}
