<?php
class Notification extends Model
{
    protected $table = 'notifications';

    public function forUser($userId, $limit = 50)
    {
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function unreadCount($userId)
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function send($userId, $type, $title, $message, $relatedId = null)
    {
        return Database::insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId,
            'is_read' => 0,
            'created_at' => now(),
        ]);
    }

    public function markRead($id, $userId)
    {
        return Database::query(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        )->rowCount();
    }

    public function markAllRead($userId)
    {
        return Database::query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
            [$userId]
        )->rowCount();
    }
}
