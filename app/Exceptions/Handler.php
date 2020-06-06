<?php

namespace App\Exceptions;

use App\Repository\Facade\RDS\RDS;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof CustomException) {
            return $exception->render($request);
        } elseif ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        return JsonResponse::create(RDS::fail(-1, $exception->getMessage()),
            $this->isHttpException($exception) ? $exception->getStatusCode() : 500,
            $this->isHttpException($exception) ? $exception->getHeaders() : []
        );

//        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return JsonResponse::create(RDS::fail(-1, $exception->getMessage()),
            401
        );
    }

}
