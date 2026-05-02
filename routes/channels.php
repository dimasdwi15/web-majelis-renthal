<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel: user hanya diizinkan subscribe ke channel
| miliknya sendiri (notifikasi.{user_id}).
|
| Return true  → authorized
| Return false → 403 Forbidden otomatis oleh Laravel
|
*/

Broadcast::channel('notifikasi.{userId}', function ($user, int $userId): bool {
    return (int) $user->id === $userId;
});
