<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Throwable;

abstract class Controller
{
    protected function backSuccess(string $message): RedirectResponse
    {
        return redirect()->back()->with([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    protected function backError(string $message): RedirectResponse
    {
        return redirect()->back()->with([
            'status' => 'error',
            'message' => $message,
        ]);
    }

    protected function handleAction(callable $callback, string $successMessage, string $errorMessage): RedirectResponse
    {
        try {
            $callback();

            return $this->backSuccess($successMessage);
        } catch (Throwable) {
            return $this->backError($errorMessage);
        }
    }
}
