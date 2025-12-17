<?php

use Illuminate\Support\Facades\Broadcast;

// örnek (opsiyonel)
Broadcast::channel('channel-name', function ($user) {
    return true;
});
