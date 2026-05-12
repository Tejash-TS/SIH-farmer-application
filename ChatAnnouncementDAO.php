<?php
/**
 * Chat and Announcement DAO
 * Handles database operations for chat messages and announcements
 */

class ChatAnnouncementDAO {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get chat history between two users
     */
    public function getChatHistory($user_id1, $user_id2, $limit = 50) {
        try {
            $query = "
                SELECT m.message_id, m.sender_id, m.receiver_id, m.message_text, m.is_read, m.created_on
                FROM chat_messages m
                WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_on ASC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iiiii', $user_id1, $user_id2, $user_id2, $user_id1, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            $stmt->close();
            return $messages;
        } catch (Exception $e) {
            error_log("Error fetching chat history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadMessageCount($user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = ? AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error fetching unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead($sender_id, $receiver_id) {
        try {
            $query = "UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('ii', $sender_id, $receiver_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error marking messages as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unique chat conversations for user
     */
    public function getChatConversations($user_id) {
        try {
            $query = "
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id 
                        ELSE sender_id 
                    END as other_user_id,
                    u.user_name,
                    u.image,
                    u.role,
                    MAX(m.created_on) as last_message_time,
                    (SELECT message_text FROM chat_messages WHERE 
                        (sender_id = ? AND receiver_id = u.user_id) OR 
                        (sender_id = u.user_id AND receiver_id = ?)
                        ORDER BY created_on DESC LIMIT 1) as last_message,
                    COUNT(CASE WHEN receiver_id = ? AND is_read = 0 THEN 1 END) as unread_count
                FROM chat_messages m
                INNER JOIN users u ON (
                    (m.sender_id = ? AND u.user_id = m.receiver_id) OR 
                    (m.receiver_id = ? AND u.user_id = m.sender_id)
                )
                WHERE m.sender_id = ? OR m.receiver_id = ?
                GROUP BY other_user_id
                ORDER BY last_message_time DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iiiiiiii', 
                $user_id, $user_id, $user_id, $user_id, 
                $user_id, $user_id, $user_id, $user_id
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            $conversations = [];
            while ($row = $result->fetch_assoc()) {
                $conversations[] = $row;
            }
            
            $stmt->close();
            return $conversations;
        } catch (Exception $e) {
            error_log("Error fetching conversations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save chat message (called from WebSocket server)
     */
    public function saveChatMessage($sender_id, $receiver_id, $message_text) {
        try {
            $query = "
                INSERT INTO chat_messages (sender_id, receiver_id, message_text, is_read, created_by, created_on)
                VALUES (?, ?, ?, 0, ?, NOW())
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iisi', $sender_id, $receiver_id, $message_text, $sender_id);
            $result = $stmt->execute();
            $message_id = $this->conn->insert_id;
            $stmt->close();
            
            return $result ? ['status' => true, 'message_id' => $message_id] : ['status' => false];
        } catch (Exception $e) {
            error_log("Error saving chat message: " . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // ==================== ANNOUNCEMENTS ====================

    /**
     * Get announcements for user
     */
    public function getAnnouncements($user_id, $limit = 50) {
        try {
            // First get user's role
            $role_query = "SELECT role FROM users WHERE user_id = ?";
            $role_stmt = $this->conn->prepare($role_query);
            $role_stmt->bind_param('i', $user_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $user_data = $role_result->fetch_assoc();
            $user_role = $user_data['role'] ?? '';
            $role_stmt->close();

            $query = "
                SELECT a.announcement_id, a.title, a.description, a.target_role, a.sender_id,
                       a.created_on, u.user_name as sender_name,
                       COALESCE(ar.read_on, NULL) as read_on
                FROM announcements a
                LEFT JOIN announcement_reads ar ON a.announcement_id = ar.announcement_id AND ar.user_id = ?
                LEFT JOIN users u ON a.sender_id = u.user_id
                WHERE a.is_active = 1 
                AND (
                    a.target_role = 'all' 
                    OR a.target_role = ?
                    OR FIND_IN_SET(?, a.target_role)
                )
                ORDER BY a.created_on DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('issi', $user_id, $user_role, $user_role, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $announcements = [];
            while ($row = $result->fetch_assoc()) {
                $announcements[] = $row;
            }
            
            $stmt->close();
            return $announcements;
        } catch (Exception $e) {
            error_log("Error fetching announcements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread announcement count for user
     */
    public function getUnreadAnnouncementCount($user_id) {
        try {
            $role_query = "SELECT role FROM users WHERE user_id = ?";
            $role_stmt = $this->conn->prepare($role_query);
            $role_stmt->bind_param('i', $user_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $user_data = $role_result->fetch_assoc();
            $user_role = $user_data['role'] ?? '';
            $role_stmt->close();

            $query = "
                SELECT COUNT(DISTINCT a.announcement_id) as count
                FROM announcements a
                LEFT JOIN announcement_reads ar ON a.announcement_id = ar.announcement_id AND ar.user_id = ?
                WHERE a.is_active = 1 
                AND ar.read_id IS NULL
                AND (
                    a.target_role = 'all' 
                    OR a.target_role = ?
                    OR FIND_IN_SET(?, a.target_role)
                )
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iss', $user_id, $user_role, $user_role);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error fetching unread announcement count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark announcement as read
     */
    public function markAnnouncementAsRead($announcement_id, $user_id) {
        try {
            $query = "
                INSERT IGNORE INTO announcement_reads (announcement_id, user_id, read_on)
                VALUES (?, ?, NOW())
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('ii', $announcement_id, $user_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error marking announcement as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create announcement (admin only)
     */
    public function createAnnouncement($title, $description, $target_role, $creator_id) {
        try {
            $query = "
                INSERT INTO announcements (title, description, target_role, sender_id, created_by, created_on)
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('sssii', $title, $description, $target_role, $creator_id, $creator_id);
            $result = $stmt->execute();
            $announcement_id = $this->conn->insert_id;
            $stmt->close();
            
            return $result ? ['status' => true, 'announcement_id' => $announcement_id] : ['status' => false];
        } catch (Exception $e) {
            error_log("Error creating announcement: " . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all announcements (admin)
     */
    public function getAllAnnouncements($limit = 50) {
        try {
            $query = "
                SELECT a.announcement_id, a.title, a.description, a.target_role, a.sender_id,
                       a.is_active, a.created_on, u.user_name as sender_name,
                       COUNT(DISTINCT ar.user_id) as read_count
                FROM announcements a
                LEFT JOIN users u ON a.sender_id = u.user_id
                LEFT JOIN announcement_reads ar ON a.announcement_id = ar.announcement_id
                GROUP BY a.announcement_id
                ORDER BY a.created_on DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $announcements = [];
            while ($row = $result->fetch_assoc()) {
                $announcements[] = $row;
            }
            
            $stmt->close();
            return $announcements;
        } catch (Exception $e) {
            error_log("Error fetching all announcements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update announcement
     */
    public function updateAnnouncement($announcement_id, $title, $description, $target_role, $updater_id) {
        try {
            $query = "
                UPDATE announcements 
                SET title = ?, description = ?, target_role = ?, modified_on = NOW(), modified_by = ?
                WHERE announcement_id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('sssii', $title, $description, $target_role, $updater_id, $announcement_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error updating announcement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete announcement
     */
    public function deleteAnnouncement($announcement_id) {
        try {
            $query = "UPDATE announcements SET is_active = 0 WHERE announcement_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $announcement_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting announcement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active users that can be used to start a chat
     */
    public function getChatUsers($user_id) {
        try {
            $query = "
                SELECT
                    u.user_id,
                    u.user_name,
                    u.role,
                    u.image,
                    (
                        SELECT m.message_text
                        FROM chat_messages m
                        WHERE (m.sender_id = ? AND m.receiver_id = u.user_id)
                           OR (m.sender_id = u.user_id AND m.receiver_id = ?)
                        ORDER BY m.created_on DESC
                        LIMIT 1
                    ) AS last_message,
                    (
                        SELECT m.created_on
                        FROM chat_messages m
                        WHERE (m.sender_id = ? AND m.receiver_id = u.user_id)
                           OR (m.sender_id = u.user_id AND m.receiver_id = ?)
                        ORDER BY m.created_on DESC
                        LIMIT 1
                    ) AS last_message_time,
                    (
                        SELECT COUNT(*)
                        FROM chat_messages m
                        WHERE m.sender_id = u.user_id
                          AND m.receiver_id = ?
                          AND m.is_read = 0
                    ) AS unread_count
                FROM users u
                WHERE u.user_id <> ?
                  AND COALESCE(u.is_active, 'Y') = 'Y'
                ORDER BY u.user_name ASC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            $stmt->close();
            return $users;
        } catch (Exception $e) {
            error_log("Error fetching chat users: " . $e->getMessage());
            return [];
        }
    }
}
?>
