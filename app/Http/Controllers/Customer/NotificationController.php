<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * The customer's own notifications, newest first.
     *
     * Queried through the model's scopes rather than
     * NotificationService::getUserNotifications, which returns a limited
     * collection and so cannot paginate.
     */
    public function index()
    {
        $notifications = Notification::forUser(auth()->id())
            ->with('userCase')
            ->latest()
            ->paginate(20);

        return view('customer.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $this->notifications->getUnreadCount(auth()->user()),
        ]);
    }

    /**
     * Mark one notification as read.
     *
     * There is no policy for this model; ownership is a single column, so the
     * check is inline. 404 rather than 403 — a customer has no business
     * learning that another user's notification exists.
     */
    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $this->notifications->markAsRead($notification);

        if ($request->wantsJson()) {
            return response()->json(['read' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark every one of the customer's notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $cleared = $this->notifications->markAllAsRead(auth()->user());

        if ($request->wantsJson()) {
            return response()->json(['read' => $cleared]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
