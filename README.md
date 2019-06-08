# 秒杀系统


### 实现步骤
1. 首先创建redis商品库存队列
2. 客户端用户访问秒杀API接口
3. 然后从redis的商品库存队列中查询剩余库存量
4. redis队列中有剩余量，则在mysql中创建订单去库存，抢购成功
5. redis队列中没有剩余，则提示库存不足，抢购失败

### 环境
* php5.6 + phpredis扩展
* redis服务
* apache2
* mysql innodb 支持事务和行锁



