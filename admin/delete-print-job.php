<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['print_job_id'])) {
    $print_job_id = $_POST['print_job_id'];
    
    try {
        $conn = getDBConnection();
        
        // First get the file path to delete the file
        $stmt = $conn->prepare("SELECT file_path FROM print_jobs WHERE id = ?");
        $stmt->execute([$print_job_id]);
        $print_job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($print_job) {
            // Delete the file if it exists
            $file_path = '../' . $print_job['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete the print job from database
            $stmt = $conn->prepare("DELETE FROM print_jobs WHERE id = ?");
            $stmt->execute([$print_job_id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Print job tidak ditemukan']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
} 