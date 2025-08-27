<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $print_job_id = $_POST['print_job_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (empty($print_job_id) || empty($status)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
        exit;
    }
    
    try {
        $conn = getDBConnection();
        
        // Update print job status
        $stmt = $conn->prepare("UPDATE print_jobs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $print_job_id]);
        
        // Get print job and user details for email
        $stmt = $conn->prepare("
            SELECT pj.*, u.email, u.username 
            FROM print_jobs pj 
            JOIN users u ON pj.user_id = u.id 
            WHERE pj.id = ?
        ");
        $stmt->execute([$print_job_id]);
        $print_job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($print_job) {
            // Send email notification based on status
            switch ($status) {
                case 'paid':
                    $email_body = "Halo {$print_job['username']},\n\n";
                    $email_body .= "Pembayaran Anda untuk layanan cetak #{$print_job['id']} telah dikonfirmasi.\n";
                    $email_body .= "Dokumen Anda akan segera diproses.\n\n";
                    $email_body .= "Terima kasih telah menggunakan layanan kami.";
                    mail($print_job['email'], 'Pembayaran Dikonfirmasi', $email_body);
                    break;
                    
                case 'processing':
                    $email_body = "Halo {$print_job['username']},\n\n";
                    $email_body .= "Dokumen Anda #{$print_job['id']} sedang diproses.\n";
                    $email_body .= "Kami akan segera menyelesaikan pencetakan.\n\n";
                    $email_body .= "Terima kasih telah menggunakan layanan kami.";
                    mail($print_job['email'], 'Dokumen Sedang Diproses', $email_body);
                    break;
                    
                case 'ready':
                    $email_body = "Halo {$print_job['username']},\n\n";
                    $email_body .= "Dokumen Anda #{$print_job['id']} telah siap diambil.\n";
                    $email_body .= "Silakan datang ke toko kami untuk mengambil dokumen Anda.\n\n";
                    $email_body .= "Terima kasih telah menggunakan layanan kami.";
                    mail($print_job['email'], 'Dokumen Siap Diambil', $email_body);
                    break;
                    
                case 'completed':
                    $email_body = "Halo {$print_job['username']},\n\n";
                    $email_body .= "Dokumen Anda #{$print_job['id']} telah diambil.\n";
                    $email_body .= "Terima kasih telah menggunakan layanan kami.\n\n";
                    $email_body .= "Kami berharap dapat melayani Anda kembali.";
                    mail($print_job['email'], 'Dokumen Telah Diambil', $email_body);
                    break;
                    
                case 'cancelled':
                    $email_body = "Halo {$print_job['username']},\n\n";
                    $email_body .= "Layanan cetak Anda #{$print_job['id']} telah dibatalkan.\n";
                    $email_body .= "Jika Anda memiliki pertanyaan, silakan hubungi kami.\n\n";
                    $email_body .= "Terima kasih telah menggunakan layanan kami.";
                    mail($print_job['email'], 'Layanan Cetak Dibatalkan', $email_body);
                    break;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan sistem']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} 