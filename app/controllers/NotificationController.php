<?php
class NotificationController extends Controller
{
    public function __construct()
    {
        Auth::check() || redirect('/login');
    }

    public function index()
    {
        $notifs = (new Notification())->forUser(Auth::id(), 100);
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
        $count = (new Notification())->unreadCount(Auth::id());
        $this->json(['success' => true, 'count' => $count]);
    }
}
