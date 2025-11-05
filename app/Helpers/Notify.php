<?php

namespace App\Helpers;

use App\Models\Notification;

class Notify
{
    public static function send($userId, $title, $body = null, $type = null)
    {
        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
        ]);
    }
}
