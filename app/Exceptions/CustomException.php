<?php


namespace App\Exceptions;


class CustomException extends \Exception
{
    const ERROR_NO_PERMISSIONS = [1, '没有操作权限'];
    const ERROR_GOODS_SPEC_NOT_FOUND = [100, '没有找到对应的商品规格'];
    const ERROR_PROVINCE_INVALID = [101, '该省份不在配送列表，无法下单'];
    const ERROR_ORDER_GOODS_ERROR = [102, '非现货无法多品种下单'];
    const ERROR_ORDER_STOCK_NOT_SUPPORT = [103, '商品库存不足'];
    const ERROR_WECHAT = [200, '微信接口错误'];
    const ERROR_WECHAT_ORDER_CREATE = [201, '微信创建订单出错'];
    const ERROR_WECHAT_ORDER_NOT_FOUND = [201, '微信订单出错'];
    const ERROR_SITE_NOPERMISSIONS = [300, '还没有成为团长'];
    const ERROR_SITE_NOT_EXISTS = [301, '站点不存在'];
    const ERROR_SITE_ORDER_NOT_EXISTS = [302, '站点订单不存在'];
    const ERROR_SITE_MONEY_NOT_ENOUGH = [303, '余额不足'];

    private $data = [];


    static function createWithError(array $error)
    {
        return new CustomException($error[1], $error[0]);
    }

    /**
     * BusinessException constructor.
     *
     * @param string $message
     * @param string $code
     * @param array $data
     */
    public function __construct(string $message = "fail", $code = -1)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = (object)[];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 异常输出
     */
    public function render($request)
    {
        return response()->json([
            'data' => $this->getData(),
            'code' => $this->getCode(),
            'messgae' => $this->getMessage(),
        ], 400);
    }

}
