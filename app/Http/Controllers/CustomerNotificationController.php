<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('store.notifications.index', [
            'notifications' => $request->user('customer')->notifications()->latest('id')->paginate(15),
        ]);
    }

    public function markRead(Request $request, int $notification): RedirectResponse
    {
        $record = $request->user('customer')->notifications()->findOrFail($notification);
        if ($record->read_at === null) {
            $record->update(['read_at' => now()]);
        }

        return redirect()->route('customer.notifications.index');
    }
}
