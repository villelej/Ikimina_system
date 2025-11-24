<?php
class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getUserNotifications($userId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT 
                n.id,
                n.title,
                n.message,
                n.type,
                n.is_read,
                n.created_at,
                n.icon,
                n.action_url
            FROM notifications n
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getNotificationCategories($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                type,
                COUNT(*) as count,
                CASE 
                    WHEN type = 'payment' THEN 'Payments'
                    WHEN type = 'group' THEN 'Groups'
                    WHEN type = 'loan' THEN 'Loans'
                    WHEN type = 'voting' THEN 'Voting'
                    WHEN type = 'system' THEN 'System'
                    ELSE 'Other'
                END as name,
                'all' as active
            FROM notifications 
            WHERE user_id = ?
            GROUP BY type
            UNION ALL
            SELECT 'all' as type, COUNT(*) as count, 'All Notifications' as name, 'active' as active
            FROM notifications 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    }
    
    public function createNotification($data) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, icon, action_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type'],
            $data['icon'] ?? 'notifications',
            $data['action_url'] ?? null
        ]);
    }
}
?>