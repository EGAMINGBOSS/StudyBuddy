<?php
require_once __DIR__ . '/../config/database.php';

class Chat {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Find or create a random chat session
    public function findRandomStranger($user_id) {
        // First, check if user is already in an active session
        $stmt = $this->conn->prepare("
            SELECT id, session_id, user1_id, user2_id 
            FROM chat_sessions 
            WHERE (user1_id = ? OR user2_id = ?) 
            AND status = 'active'
            ORDER BY started_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $session = $result->fetch_assoc();
            $stranger_id = ($session['user1_id'] == $user_id) ? $session['user2_id'] : $session['user1_id'];
            
            return [
                'success' => true,
                'session_id' => $session['session_id'],
                'stranger_id' => $stranger_id,
                'status' => 'active'
            ];
        }
        
        // Look for waiting sessions (excluding own sessions)
        $stmt = $this->conn->prepare("
            SELECT id, session_id, user1_id 
            FROM chat_sessions 
            WHERE status = 'waiting' 
            AND user1_id != ?
            ORDER BY RAND() 
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Join existing waiting session
            $session = $result->fetch_assoc();
            
            $stmt = $this->conn->prepare("
                UPDATE chat_sessions 
                SET user2_id = ?, status = 'active', started_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $user_id, $session['id']);
            $stmt->execute();
            
            // Create notification for user1
            $this->createNotification($session['user1_id'], 'New Chat', 'A stranger has joined your chat!', 'chat');
            
            return [
                'success' => true,
                'session_id' => $session['session_id'],
                'stranger_id' => $session['user1_id'],
                'status' => 'matched'
            ];
        }
        
        // Create new waiting session
        $session_id = uniqid('chat_', true);
        $stmt = $this->conn->prepare("
            INSERT INTO chat_sessions (session_id, user1_id, status) 
            VALUES (?, ?, 'waiting')
        ");
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        
        return [
            'success' => true,
            'session_id' => $session_id,
            'status' => 'waiting',
            'message' => 'Waiting for a stranger to join...'
        ];
    }
    
    // Shuffle to find new stranger
    public function shuffleStranger($user_id, $current_session_id) {
        // End current session
        $this->endSession($current_session_id);
        
        // Find new stranger
        return $this->findRandomStranger($user_id);
    }
    
    // Send message
    public function sendMessage($session_id, $sender_id, $message, $message_type = 'text') {
        // Get session
        $stmt = $this->conn->prepare("
            SELECT id, user1_id, user2_id, status 
            FROM chat_sessions 
            WHERE session_id = ?
        ");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Session not found'];
        }
        
        $session = $result->fetch_assoc();
        
        if ($session['status'] !== 'active') {
            return ['success' => false, 'message' => 'Session is not active'];
        }
        
        // Insert message
        $stmt = $this->conn->prepare("
            INSERT INTO chat_messages (session_id, sender_id, message, message_type) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $session['id'], $sender_id, $message, $message_type);
        
        if ($stmt->execute()) {
            // Notify receiver
            $receiver_id = ($session['user1_id'] == $sender_id) ? $session['user2_id'] : $session['user1_id'];
            $this->createNotification($receiver_id, 'New Message', 'You have a new message from stranger', 'chat');
            
            return [
                'success' => true,
                'message_id' => $stmt->insert_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to send message'];
    }
    
    // Get messages for a session
    public function getMessages($session_id, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT cs.id as session_db_id
            FROM chat_sessions cs
            WHERE cs.session_id = ?
        ");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Session not found'];
        }
        
        $session = $result->fetch_assoc();
        
        $stmt = $this->conn->prepare("
            SELECT cm.id, cm.sender_id, cm.message, cm.message_type, cm.is_read, cm.created_at,
                   u.username, u.profile_picture
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.session_id = ?
            ORDER BY cm.created_at ASC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $session['session_db_id'], $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        return ['success' => true, 'messages' => $messages];
    }
    
    // End chat session
    public function endSession($session_id) {
        $stmt = $this->conn->prepare("
            UPDATE chat_sessions 
            SET status = 'ended', ended_at = NOW() 
            WHERE session_id = ?
        ");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Session ended'];
    }
    
    // Create notification
    private function createNotification($user_id, $title, $message, $type) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        $stmt->execute();
    }
    
    // Get active session for user
    public function getActiveSession($user_id) {
        $stmt = $this->conn->prepare("
            SELECT session_id, user1_id, user2_id, status, started_at
            FROM chat_sessions 
            WHERE (user1_id = ? OR user2_id = ?) 
            AND status IN ('waiting', 'active')
            ORDER BY started_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'session' => $result->fetch_assoc()];
        }
        
        return ['success' => false, 'message' => 'No active session'];
    }
}
?>