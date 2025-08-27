<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$order_id = $_POST['order_id'] ?? 0;
$type = $_POST['type'] ?? '';

if (empty($order_id) || empty($type)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    if ($type === 'print_job') {
        // Get print job details first
        $stmt = $conn->prepare("SELECT file_path FROM print_jobs WHERE id = ?");
        $stmt->execute([$order_id]);
        $print_job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($print_job) {
            // Delete the physical file if it exists
            if (!empty($print_job['file_path']) && file_exists('../' . $print_job['file_path'])) {
                unlink('../' . $print_job['file_path']);
            }
            
            // Delete the print job record
            $stmt = $conn->prepare("DELETE FROM print_jobs WHERE id = ?");
            $stmt->execute([$order_id]);
        }
    } else if ($type === 'order') {
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Delete payment records if any
        $stmt = $conn->prepare("DELETE FROM payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
    }
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log($e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log($e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'System error occurred']);
} 