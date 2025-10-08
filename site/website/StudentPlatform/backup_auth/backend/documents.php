<?php
require_once __DIR__ . '/../config/database.php';

class Documents {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Create new document
    public function createDocument($user_id, $title, $doc_type, $content = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO documents (user_id, title, doc_type, content) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $user_id, $title, $doc_type, $content);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'document_id' => $stmt->insert_id,
                'message' => 'Document created successfully'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create document'];
    }
    
    // Get user documents
    public function getUserDocuments($user_id, $doc_type = null) {
        if ($doc_type) {
            $stmt = $this->conn->prepare("
                SELECT id, title, doc_type, thumbnail, is_public, created_at, updated_at
                FROM documents 
                WHERE user_id = ? AND doc_type = ?
                ORDER BY updated_at DESC
            ");
            $stmt->bind_param("is", $user_id, $doc_type);
        } else {
            $stmt = $this->conn->prepare("
                SELECT id, title, doc_type, thumbnail, is_public, created_at, updated_at
                FROM documents 
                WHERE user_id = ?
                ORDER BY updated_at DESC
            ");
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        return ['success' => true, 'documents' => $documents];
    }
    
    // Get document by ID
    public function getDocument($document_id, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, u.username as owner_username
            FROM documents d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ? AND (d.user_id = ? OR d.is_public = TRUE OR EXISTS (
                SELECT 1 FROM document_shares WHERE document_id = d.id AND shared_with_user_id = ?
            ))
        ");
        $stmt->bind_param("iii", $document_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => true, 'document' => $result->fetch_assoc()];
        }
        
        return ['success' => false, 'message' => 'Document not found or access denied'];
    }
    
    // Update document
    public function updateDocument($document_id, $user_id, $content, $title = null) {
        if ($title) {
            $stmt = $this->conn->prepare("
                UPDATE documents 
                SET content = ?, title = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssii", $content, $title, $document_id, $user_id);
        } else {
            $stmt = $this->conn->prepare("
                UPDATE documents 
                SET content = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("sii", $content, $document_id, $user_id);
        }
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Document updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update document or access denied'];
    }
    
    // Delete document
    public function deleteDocument($document_id, $user_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM documents 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $document_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Document deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete document or access denied'];
    }
    
    // Share document
    public function shareDocument($document_id, $user_id, $share_with_user_id, $permission = 'view') {
        // Check if user owns the document
        $stmt = $this->conn->prepare("SELECT id FROM documents WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $document_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Document not found or access denied'];
        }
        
        // Share document
        $stmt = $this->conn->prepare("
            INSERT INTO document_shares (document_id, shared_with_user_id, permission) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE permission = ?
        ");
        $stmt->bind_param("iiss", $document_id, $share_with_user_id, $permission, $permission);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Document shared successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to share document'];
    }
    
    // Get shared documents
    public function getSharedDocuments($user_id) {
        $stmt = $this->conn->prepare("
            SELECT d.id, d.title, d.doc_type, d.thumbnail, d.updated_at, 
                   u.username as owner_username, ds.permission
            FROM documents d
            JOIN document_shares ds ON d.id = ds.document_id
            JOIN users u ON d.user_id = u.id
            WHERE ds.shared_with_user_id = ?
            ORDER BY d.updated_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        return ['success' => true, 'documents' => $documents];
    }
}
?>