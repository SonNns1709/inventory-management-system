<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Cho phép dùng $this->authorize('update', $model) trong mọi controller (Laravel 11+ không còn gắn sẵn)
    use AuthorizesRequests;
}
