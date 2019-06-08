<?php

$url = 'http://redis.19year.cn/index.php?app=app&c=seckill&a=addQsec&gid=2&type=redis';
$result = file_get_contents($url);

var_dump($result);
