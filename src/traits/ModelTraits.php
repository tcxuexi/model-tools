<?php

namespace traits;

use think\Model;

trait ModelTrait
{
    /**
     * 添加一条数据
     * @param $data
     * @return object $model 数据对象
     */
    public static function set($data)
    {
        return self::create($data);
    }

    /**
     * 添加多条数据
     * @param $group
     * @param bool $replace
     * @return mixed
     */
    public static function setAll($group, $replace = false)
    {
        return self::insertAll($group, $replace);
    }

    /**
     * 修改一条数据
     * @param $data [array类型：修改的字段名称+值]
     * @param $id [主键值]
     * @param $field [主键字段别名]
     * @return bool $type 返回成功失败
     */
    public static function edit($data, $id, $field = null)
    {
        $model = new self;
        if (!$field) {
            $field = $model->getPk();
        }

        return false !== $model->update($data, [$field => $id]);
    }

    /**
     * 查询一条数据是否存在
     * @param $map [值]
     * @param string $field [字段]
     * @return bool 是否存在
     * 在$map输入主键值，$field不输入，可以用于判断主键数据是否存在
     */
    public static function be($map, $field = '')
    {
        $model = (new self);
        if (!is_array($map) && empty($field)) {
            $field = $model->getPk();
        }
        $map = !is_array($map) ? [$field => $map] : $map;

        return 0 < $model->where($map)->count();
    }

    /**
     * 删除一条数据
     * @param $id
     * @return bool $type 返回成功失败
     */
    public static function del($id)
    {
        return false !== self::destroy($id);
    }


    /**
     * 分页
     * @param null $model 模型
     * @param null $eachFn 处理结果函数
     * @param array $params 分页参数
     * @param int $limit 分页数
     * @return array
     *
     */
    public static function page($model = null, $eachFn = null, $params = [], $limit = 20)
    {
        // dump(Advertising::where('is_show', 1)->order('sort desc,id desc')->page((int)$page, (int)$limit)->select());
        // exit();
        if (is_numeric($eachFn) && is_numeric($model)) {
            return parent::page($model, $eachFn);
        }

        if (is_numeric($eachFn)) {
            $limit  = $eachFn;
            $eachFn = null;
        } else {
            if (is_array($eachFn)) {
                $params = $eachFn;
                $eachFn = null;
            }
        }

        if (is_callable($model)) {
            $eachFn = $model;
            $model  = null;
        } elseif (is_numeric($model)) {
            $limit = $model;
            $model = null;
        } elseif (is_array($model)) {
            $params = $model;
            $model  = null;
        }

        if (is_numeric($params)) {
            $limit  = $params;
            $params = [];
        }

        $paginate = $model === null ? self::paginate($limit, false, ['query' => $params]) : $model->paginate($limit, false, ['query' => $params]);
        $list     = is_callable($eachFn) ? $paginate->each($eachFn) : $paginate;
        $page     = $list->render();
        $total    = $list->total();

        return compact('list', 'page', 'total');
    }

    /**
     * 获取分页 生成where 条件和 whereOr 支持多表查询生成条件
     * @param object $model 模型对象
     * @param array $where 需要检索的数组
     * @param array $field where字段名
     * @param array $fieldOr whereOr字段名
     * @param array $fun 闭包函数
     * @param string $like 模糊查找 关键字
     * @return array
     */
    public static function setWherePage($model = null, $where = [], $field = [], $fieldOr = [], $fun = null, $like = 'LIKE')
    {
        if (!is_array($where) || !is_array($field)) {
            return false;
        }
        if ($model === null) {
            $model = new self();
        }
        //处理等于行查询
        foreach ($field as $key => $item) {
            if (($count = strpos($item, '.')) === false) {
                if (isset($where[$item]) && $where[$item] != '') {
                    $model = $model->where($item, $where[$item]);
                }
            } else {
                $item_l = substr($item, $count + 1);
                if (isset($where[$item_l]) && $where[$item_l] != '') {
                    $model = $model->where($item, $where[$item_l]);
                }
            }
        }
        //回收变量
        unset($count, $key, $item, $item_l);
        //处理模糊查询
        if (!empty($fieldOr) && is_array($fieldOr) && isset($fieldOr[0])) {
            if (($count = strpos($fieldOr[0], '.')) === false) {
                if (isset($where[$fieldOr[0]]) && $where[$fieldOr[0]] != '') {
                    $model = $model->where(self::get_field($fieldOr), $like, "%".$where[$fieldOr[0]]."%");
                }
            } else {
                $item_l = substr($fieldOr[0], $count + 1);
                if (isset($where[$item_l]) && $where[$item_l] != '') {
                    $model = $model->where(self::get_field($fieldOr), $like, "%".$where[$item_l]."%");
                }
            }
        }
        unset($count, $key, $item, $item_l);

        return $model;
    }

    /**
     * 字符串拼接
     * @param int|array $id
     * @param string $str
     * @return string
     */
    private static function get_field($id, $str = '|')
    {
        if (is_array($id)) {
            $sql = "";
            $i   = 0;
            foreach ($id as $val) {
                $i++;
                if ($i < count($id)) {
                    $sql .= $val.$str;
                } else {
                    $sql .= $val;
                }
            }

            return $sql;
        } else {
            return $id;
        }
    }

    /**
     * 条件切割
     * @param string $order
     * @param string $file
     * @return string
     */
    public static function setOrder($order, $file = '-')
    {
        if (empty($order)) {
            return '';
        }

        return str_replace($file, ' ', $order);
    }

    /**
     * 获取时间段之间的model
     * @param $where [变量数组]
     * @param string $prefix [时间字段]
     * @param string $data [变量数组字段名]
     * @param string $field [时间分隔符]
     * @param null $model [模型]
     * @return ModelTrait
     */
    public static function getModelTime($where, $prefix = 'add_time', $data = 'data', $field = ' - ', $model = null)
    {
        if ($model == null) {
            $model = new self;
        }
        if (!isset($where[$data])) {
            return $model;
        }
        $arr = ['today', 'week', 'month', 'year', 'yesterday', 'last week', 'last month', 'last year'];
        if (in_array($where[$data], $arr)) {
            $model = $model->whereTime($prefix, $where[$data]);
        } else {
            switch ($where[$data]) {
                case 'quarter':
                    list($startTime, $endTime) = self::getMonth();
                    $model = $model->where($prefix, '>', strtotime($startTime));
                    $model = $model->where($prefix, '<', strtotime($endTime));
                    break;
                default:
                    if (strstr($where[$data], $field) !== false) {
                        list($startTime, $endTime) = explode($field, $where[$data]);
                        if ($startTime && $endTime) {
                            $model = $model->where($prefix, '>', strtotime($startTime));
                            $model = $model->where($prefix, '<', strtotime($endTime));
                        }
                    }
                    break;
            }
        }

        return $model;
    }

    /**
     * 友好时间显示
     * @param $time
     * @return string
     */
    public static function timeTran($time)
    {
        $t = time() - $time;
        $f = array(
            '31536000' => '年',
            '2592000'  => '个月',
            '604800'   => '星期',
            '86400'    => '天',
            '3600'     => '小时',
            '60'       => '分钟',
            '1'        => '秒',
        );
        foreach ($f as $k => $v) {
            if (0 != $c = floor($t / (int)$k)) {
                return $c.$v.'前';
            }
        }
    }


    /**
     * 分级排序
     * @param $data
     * @param int $pid
     * @param string $field
     * @param string $pk
     * @param string $html
     * @param int $level
     * @param bool $clear
     * @return array
     */
    public static function sortListTier($data, $pid = 0, $field = 'pid', $pk = 'id', $html = '|-----', $level = 1, $clear = true)
    {
        static $list = [];
        if ($clear) {
            $list = [];
        }
        foreach ($data as $k => $res) {
            if ($res[$field] == $pid) {
                $res['html'] = str_repeat($html, $level);
                $list[]      = $res;
                unset($data[$k]);
                self::sortListTier($data, $res[$pk], $field, $pk, $html, $level + 1, false);
            }
        }

        return $list;
    }

    /**
     * 身份证验证
     * @param $card
     * @return bool
     */
    public static function setCard($card)
    {
        $city  = [11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古", 21 => "辽宁", 22 => "吉林", 23 => "黑龙江 ", 31 => "上海", 32 => "江苏", 33 => "浙江", 34 => "安徽", 35 => "福建", 36 => "江西", 37 => "山东", 41 => "河南", 42 => "湖北 ", 43 => "湖南", 44 => "广东", 45 => "广西", 46 => "海南", 50 => "重庆", 51 => "四川", 52 => "贵州", 53 => "云南", 54 => "西藏 ", 61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => "宁夏", 65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外 "];
        $tip   = "";
        $match = "/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/";
        $pass  = true;
        if (!$card || !preg_match($match, $card)) {
            //身份证格式错误
            $pass = false;
        } else {
            if (!$city[substr($card, 0, 2)]) {
                //地址错误
                $pass = false;
            } else {
                //18位身份证需要验证最后一位校验位
                if (strlen($card) == 18) {
                    $card = str_split($card);
                    //∑(ai×Wi)(mod 11)
                    //加权因子
                    $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                    //校验位
                    $parity = [1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2];
                    $sum    = 0;
                    $ai     = 0;
                    $wi     = 0;
                    for ($i = 0; $i < 17; $i++) {
                        $ai  = $card[$i];
                        $wi  = $factor[$i];
                        $sum += $ai * $wi;
                    }
                    $last = $parity[$sum % 11];
                    if ($parity[$sum % 11] != $card[17]) {
//                        $tip = "校验位错误";
                        $pass = false;
                    }
                } else {
                    $pass = false;
                }
            }
        }
        if (!$pass) {
            return false;
        }/* 身份证格式错误*/

        return true;/* 身份证格式正确*/
    }

    /**
     * 获取去除html去除空格去除软回车,软换行,转换过后的字符串
     * @param string $str
     * @return string
     */
    public static function HtmlToMbStr($str)
    {
        return trim(strip_tags(str_replace(["\n", "\t", "\r", " ", "&nbsp;"], '', htmlspecialchars_decode($str))));
    }


    /**
     * 截取中文指定字节
     * @param string $str
     * @param int $utf8len
     * @param string $chaet
     * @param string $file
     * @return string
     */
    public static function getSubstrUTf8($str, $utf8len = 100, $chaet = 'UTF-8', $file = '....')
    {
        if (mb_strlen($str, $chaet) > $utf8len) {
            $str = mb_substr($str, 0, $utf8len, $chaet).$file;
        }

        return $str;
    }


    /**
     * 获取本季度 time
     * @param int|string $time
     * @param string $ceil
     * @return array
     */
    public static function getMonth($time = '', $ceil = 0)
    {
        if ($ceil != 0) {
            $season = ceil(date('n') / 3) - $ceil;
        } else {
            $season = ceil(date('n') / 3);
        }
        $firstday = date('Y-m-01', mktime(0, 0, 0, ($season - 1) * 3 + 1, 1, date('Y')));
        $lastday  = date('Y-m-t', mktime(0, 0, 0, $season * 3, 1, date('Y')));

        return array($firstday, $lastday);
    }

    /**
     * 高精度 加法
     * @param $key [int|string $uid id]
     * @param $incField [相加的字段]
     * @param $inc [加的值]
     * @param string $keyField id的字段
     * @param int $acc 精度
     * @return bool
     */
    public static function bcInc($key, $incField, $inc, $keyField = null, $acc = 2)
    {
        if (!is_numeric($inc)) {
            return false;
        }
        $model = new self();
        if ($keyField === null) {
            $keyField = $model->getPk();
        }
        $result = self::where($keyField, $key)->find();
        if (!$result) {
            return false;
        }
        $new = bcadd($result[$incField], $inc, $acc);

        return false !== $model->where($keyField, $key)->update([$incField => $new]);
    }


    /**
     * 高精度 减法
     * @param $key [int|string $uid id]
     * @param string $decField 相减的字段
     * @param float|int $dec 减的值
     * @param string $keyField id的字段
     * @param bool $minus 是否可以为负数
     * @param int $acc 精度
     * @return bool
     */
    public static function bcDec($key, $decField, $dec, $keyField = null, $minus = false, $acc = 2)
    {
        if (!is_numeric($dec)) {
            return false;
        }
        $model = new self();
        if ($keyField === null) {
            $keyField = $model->getPk();
        }
        $result = self::where($keyField, $key)->find();
        if (!$result) {
            return false;
        }
        if (!$minus && $result[$decField] < $dec) {
            return false;
        }
        $new = bcsub($result[$decField], $dec, $acc);

        return false !== $model->where($keyField, $key)->update([$decField => $new]);
    }

    /**
     * @param null $model
     * @return Model
     */
    protected static function getSelfModel($model = null)
    {
        return $model == null ? (new self()) : $model;
    }

}