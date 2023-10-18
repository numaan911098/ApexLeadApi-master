<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use Log;

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
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {

        $this->logException($e, $request);

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {
            if (empty($e->getPrevious())) {
                return Util::apiResponse(
                    403,
                    [],
                    ErrorTypes::UNAUTHORIZED,
                    'You are not Authorized for this action'
                );
            }

            Log::info("PREVIOUS EXCEPTION: " . get_class($e->getPrevious()));

            switch (get_class($e->getPrevious())) {
                case \Tymon\JWTAuth\Exceptions\TokenExpiredException::class:
                    return Util::apiResponse(
                        401,
                        [],
                        ErrorTypes::TOKEN_EXPIRED,
                        'Please provide the correct token'
                    );
                case \Tymon\JWTAuth\Exceptions\TokenInvalidException::class:
                case \Tymon\JWTAuth\Exceptions\TokenBlacklistedException::class:
                    return Util::apiResponse(
                        401,
                        [],
                        ErrorTypes::TOKEN_INVALID,
                        'Please provide the correct token'
                    );
                default:
                    return Util::apiResponse(
                        403,
                        [],
                        ErrorTypes::UNAUTHORIZED,
                        'You are not Authorized for this action'
                    );
                    break;
            }
        } elseif (
            $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ||
            $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
        ) {
            return Util::apiResponse(
                404,
                [],
                ErrorTypes::RESOURCE_NOT_FOUND,
                'No record found'
            );
        } elseif ($e instanceof AuthorizationException) {
            return Util::apiResponse(
                403,
                [],
                ErrorTypes::UNAUTHORIZED,
                'You are not Authorized for this action'
            );
        } elseif ($e instanceof AuthenticationException) {
            return Util::apiResponse(
                401,
                [],
                ErrorTypes::UNAUTHENTICATED,
                'You are not Authenticated'
            );
        } else {
            return Util::apiResponse(
                500,
                [],
                ErrorTypes::INTERNAL_SERVER_ERROR,
                'Unknown error occurred on server.'
            );
        }

        return parent::render($request, $e);
    }

    public function logException($e, $request)
    {
        Log::info("REQUEST URL: " . $request->url());
        Log::info("EXCEPTION CLASS: " . get_class($e));
        Log::info("EXCEPTION MESSAGE: " . $e->getMessage());
        Log::info("EXCEPTION TRACE: " . $e->getTraceAsString());
    }
}
