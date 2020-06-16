<?php


namespace App\Exceptions;


class CustomException extends \Exception
{
    const ERROR_NO_PERMISSIONS = [1, '没有操作权限'];
    const ERROR_PERIOD = [2, '周期参数必须为数组'];

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
