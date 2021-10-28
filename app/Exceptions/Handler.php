<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Utilities\Utility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


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
        if(strpos($exception->getMessage(), '<JSON/>') === 0 ) {
            return \prepare_json(Utility::$negative, [], str_replace('<JSON/>','',$exception->getMessage()), $exception->getStatusCode());
        }


        if ($exception instanceof ModelNotFoundException) {
            return response()->json(['message' => 'Not Found!'], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return \prepare_json(Utility::$negative, [], "invalid action attempted.", $exception->getStatusCode());
        }



        return parent::render($request, $exception);
    }
}
