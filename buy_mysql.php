<?php

for($i=0;$i<10000;$i++){
$url = 'http://redis.19year.cn/index.php?app=app&c=seckill&a=addQsec&gid=2&type=mysql';
$result = file_get_contents($url);

var_dump($result);
}
