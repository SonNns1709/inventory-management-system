<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * UC01.01 — Exception Flow E3: đăng nhập sai quá 5 lần trong 15 phút (theo từng cặp email + IP) -> HTTP 429.
 * Header Retry-After cho client biết còn bao nhiêu giây nữa mới được thử lại.
 */
class TooManyLoginAttemptsException extends HttpException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        $minutes = max(1, (int) ceil($retryAfterSeconds / 60));

        parent::__construct(
            429,
            "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$minutes} phút.",
            null,
            ['Retry-After' => (string) $retryAfterSeconds],
        );
    }
}
