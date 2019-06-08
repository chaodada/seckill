<?php

/* -------------------------------------------------
 * redis驱动类，需要安装phpredis扩展
 *
 * @athor	Sandy
 * @link	http://pecl.php.net/package/redis
 * @date	2018-03-19
 * -------------------------------------------------
 */

class QRedis
{

    private $_redis = null;

    /*
     * 构造器
     *
     */

    public function __construct()
    {
        if ($this->_redis === null) {
            try {
                $redis = new Redis();
                $redis->connect('redis主机', 端口);
                $hanedel = $redis->auth('密码');
                if ($hanedel) {
                    $this->_redis = $redis;
                } else {
                    echo 'redis服务器无法链接';
                    $this->_redis = false;
                    exit;
                }
            } catch (RedisException $e) {
                echo 'phpRedis扩展没有安装：' . $e->getMessage();
                exit;
            }
        }
    }

    /*
     * 队列尾追加
     *
     *
     */

    public function addRlist($key, $value)
    {
        //rpush
        //描述：由列表尾部添加字符串值。如果不存在该键则创建该列表。如果该键存在，而且不是一个列表，返回FALSE。
        //参数：key,value
        //返回值：成功返回数组长度，失败false
        $result = $this->_redis->rPush($key, $value);
        return $result;
    }

    /*
     * 队列头追加
     *
     *
     */

    public function addLlist($key, $value)
    {
        $result = $this->_redis->lPush($key, $value);
        return $result;
    }

    /**
     * 头出队列
     */
    public function lpoplist($key)
    {

        //移除并返回列表 key 的头元素。
        //返回值
        //列表的头元素。 当 key 不存在时，返回 nil 。
        $result = $this->_redis->lPop($key);
        return $result;
    }

    /*
     * 尾出队列
     *
     */

    public function rpoplist($key)
    {
        $result = $this->_redis->rPop($key);
        return $result;
    }

    /*
     * 查看队列
     *
     */

    public function showlist($key)
    {
        $result = $this->_redis->lRange($key, 0, -1);
        return $result;
    }

    /**
     * 队列数量
     */
    public function listcount($key)
    {
        //返回的列表的大小。如果列表不存在或为空，该命令返回0。如果该键不是列表，该命令返回FALSE。
        $result = $this->_redis->lSize($key);
        return $result;
    }

    /**
     * 清空队列
     */

    public function clearlist($key)
    {
        //delete
        //描述：删除指定的键
        //参数：一个键，或不确定数目的参数，每一个关键的数组：key1 key2 key3 … keyN
        //返回值：删除的项数
        $result = $this->_redis->delete($key);
        return $result;
    }

    /*
     * 获取redis资源对象
     *
     */

    public function getHandel()
    {
        return $this->_redis;
    }

}

?>