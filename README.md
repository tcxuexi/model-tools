# tp5-timeRecord
对Thinkphp的模型的一个封装

## 使用

1.项目的traits目录和basic目录放到tp5的extend目录下

2.使用，例如：

模型文件：

~~~php
<?php 

namespace app\admin\model\article;

use traits\ModelTrait;
use basic\ModelBasic;

class Aritcle extends ModelBasic
{
    use ModelTrait;
}
~~~

控制器文件：

~~~php
Aritcle::edit(['name'=>'123'],$id);
~~~



