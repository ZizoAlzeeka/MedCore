<?php
class NotificationController extends Controller
{
    public function __construct()
    {
        Auth::check() || redirect('/login');
    }

    public function index()
    {
        // ⚡ Cache notifications for 15s per user
        $cacheKey = 'notif_page_' . Auth::id();
        $notifs = null;
        if (function_exists('apcu_fetch')) {
            $notifs = apcu_fetch($cacheKey);
        }
        if ($notifs === false || $notifs === null) {
            $notifs = (new Notification())->forUser(Auth::id(), 100);
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $notifs, 15);
            }
        }
        $title = 'الإشعارات';
        viewWithLayout('notifications/index', compact('notifs', 'title'));
    }

    public function markRead($id)
    {
        Auth::csrfVerify();
        (new Notification())->markRead($id, Auth::id());
        $this->json(['success' => true]);
    }

    public function markAllRead()
    {
        Auth::csrfVerify();
        (new Notification())->markAllRead(Auth::id());
        flash('success', 'تم تعليم الكل كمقروء');
        redirect('/notifications');
    }

    public function unreadCount()
    {
        $userId = Auth::id();
        $notifModel = new Notification();
        $count = $notifModel->unreadCount($userId);
        // Fetch the most recent unread notification so the client can show a
        // SweetAlert2 toast when a brand-new notif arrives (compared by id).
        $latest = Database::fetch(
            "SELECT id, type, title, message FROM notifications
             WHERE user_id = ? AND is_read = 0
             ORDER BY created_at DESC, id DESC LIMIT 1",
            [$userId]
        );
        $this->json([
            'success' => true,
            'count'   => $count,
            'latest'  => $latest ?: null,
        ]);
    }
}
