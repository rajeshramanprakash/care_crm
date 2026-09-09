<?php

use App\Models\User;
use App\Models\WhatsappMsgGroup;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('whatsapp.number.{number}', function ($user, $number) {
    if (! $user) {
        return false;
    }

    if ((int) $user->role_id === 1) {
        return true;
    }

    $allowedIds = [];
    if (in_array((int) $user->role_id, [3, 5], true)) {
        $allowedIds = User::where('parent_id', $user->id)->pluck('id')->prepend($user->id)->all();
    } else {
        $allowedIds = [$user->id];
    }

    $group = WhatsappMsgGroup::where('whatsapp_number', $number)->first();
    if (! $group) {
        return false;
    }

    $executiveIds = array_filter(array_map('intval', explode(',', (string) $group->executive_ids)));

    return count(array_intersect($allowedIds, $executiveIds)) > 0;
});
