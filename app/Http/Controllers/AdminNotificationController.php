<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('type', 'admin_broadcast')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        Notification::create([
            'user_id' => Auth::id(),
            'type' => 'admin_broadcast',
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'sender_name' => Auth::user()->name,
                'recipients' => 'all'
            ],
            'read_at' => null
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم إرسال الإشعار بنجاح لجميع المستخدمين');
    }

    public function edit(Notification $notification)
    {
        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $notification->update([
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'sender_name' => Auth::user()->name,
                'recipients' => 'all',
                'updated_at' => now()
            ]
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم تحديث الإشعار بنجاح');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم حذف الإشعار بنجاح');
    }
}
