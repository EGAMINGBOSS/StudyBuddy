<?php
require_once __DIR__ . '/../config/database.php';

class BibleVerse {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Get daily verse for user
    public function getDailyVerse($user_id) {
        $today = date('Y-m-d');
        
        // Check if user already has a verse for today
        $stmt = $this->conn->prepare("
            SELECT bv.id, bv.verse_text, bv.reference, bv.book, bv.chapter, bv.verse, bv.category,
                   udv.is_favorited
            FROM user_daily_verses udv
            JOIN bible_verses bv ON udv.verse_id = bv.id
            WHERE udv.user_id = ? AND udv.shown_date = ?
        ");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'verse' => $result->fetch_assoc(), 'is_new' => false];
        }
        
        // Get a random verse that user hasn't seen recently (last 30 days)
        $stmt = $this->conn->prepare("
            SELECT bv.id, bv.verse_text, bv.reference, bv.book, bv.chapter, bv.verse, bv.category
            FROM bible_verses bv
            WHERE bv.id NOT IN (
                SELECT verse_id FROM user_daily_verses 
                WHERE user_id = ? AND shown_date > DATE_SUB(?, INTERVAL 30 DAY)
            )
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $verse = $result->fetch_assoc();
            
            // Save this verse for the user
            $stmt = $this->conn->prepare("
                INSERT INTO user_daily_verses (user_id, verse_id, shown_date) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iis", $user_id, $verse['id'], $today);
            $stmt->execute();
            
            $verse['is_favorited'] = false;
            return ['success' => true, 'verse' => $verse, 'is_new' => true];
        }
        
        // If all verses have been shown recently, get any random verse
        $stmt = $this->conn->prepare("
            SELECT id, verse_text, reference, book, chapter, verse, category
            FROM bible_verses
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $verse = $result->fetch_assoc();
            
            $stmt = $this->conn->prepare("
                INSERT INTO user_daily_verses (user_id, verse_id, shown_date) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE shown_date = ?
            ");
            $stmt->bind_param("iiss", $user_id, $verse['id'], $today, $today);
            $stmt->execute();
            
            $verse['is_favorited'] = false;
            return ['success' => true, 'verse' => $verse, 'is_new' => true];
        }
        
        return ['success' => false, 'message' => 'No verses available'];
    }
    
    // Toggle favorite verse
    public function toggleFavorite($user_id, $verse_id) {
        $today = date('Y-m-d');
        
        $stmt = $this->conn->prepare("
            UPDATE user_daily_verses 
            SET is_favorited = NOT is_favorited 
            WHERE user_id = ? AND verse_id = ? AND shown_date = ?
        ");
        $stmt->bind_param("iis", $user_id, $verse_id, $today);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Favorite status updated'];
        }
        
        return ['success' => false, 'message' => 'Failed to update favorite status'];
    }
    
    // Get user's favorite verses
    public function getFavoriteVerses($user_id) {
        $stmt = $this->conn->prepare("
            SELECT bv.id, bv.verse_text, bv.reference, bv.book, bv.chapter, bv.verse, bv.category,
                   udv.shown_date
            FROM user_daily_verses udv
            JOIN bible_verses bv ON udv.verse_id = bv.id
            WHERE udv.user_id = ? AND udv.is_favorited = TRUE
            ORDER BY udv.shown_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $verses = [];
        while ($row = $result->fetch_assoc()) {
            $verses[] = $row;
        }
        
        return ['success' => true, 'verses' => $verses];
    }
    
    // Get random verse by category
    public function getVerseByCategory($category) {
        $stmt = $this->conn->prepare("
            SELECT id, verse_text, reference, book, chapter, verse, category
            FROM bible_verses
            WHERE category = ?
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'verse' => $result->fetch_assoc()];
        }
        
        return ['success' => false, 'message' => 'No verses found for this category'];
    }
    
    // Get all categories
    public function getCategories() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT category 
            FROM bible_verses 
            WHERE category IS NOT NULL
            ORDER BY category
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return ['success' => true, 'categories' => $categories];
    }
}
?>