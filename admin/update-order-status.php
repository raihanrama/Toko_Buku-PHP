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
    $order_id = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (empty($order_id) || empty($status)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
        exit;
    }
    
    try {
        $conn = getDBConnection();
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        // Get order and user details for email
        $stmt = $conn->prepare("
            SELECT o.*, u.email, u.username 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Send email notification based on status
            switch ($status) {
                case 'paid':
                    $email_body = "Halo {$order['username']},\n\n";
                    $email_body .= "Pembayaran Anda untuk pesanan #{$order['id']} telah dikonfirmasi.\n";
                    $email_body .= "Pesanan Anda akan segera diproses.\n\n";
                    $email_body .= "Terima kasih telah berbelanja di Bulefotokopi.";
                    mail($order['email'], 'Pembayaran Dikonfirmasi', $email_body);
                    break;
                    
                case 'processing':
                    $email_body = "Halo {$order['username']},\n\n";
                    $email_body .= "Pesanan Anda #{$order['id']} sedang diproses.\n";
                    $email_body .= "Kami akan segera mengirimkan pesanan Anda.\n\n";
                    $email_body .= "Terima kasih telah berbelanja di Bulefotokopi.";
                    mail($order['email'], 'Pesanan Sedang Diproses', $email_body);
                    break;
                    
                case 'completed':
                    $email_body = "Halo {$order['username']},\n\n";
                    $email_body .= "Pesanan Anda #{$order['id']} telah selesai diproses.\n";
                    $email_body .= "Silakan ambil pesanan Anda di toko kami.\n\n";
                    $email_body .= "Terima kasih telah berbelanja di Bulefotokopi.";
                    mail($order['email'], 'Pesanan Selesai', $email_body);
                    break;
                    
                case 'cancelled':
                    $email_body = "Halo {$order['username']},\n\n";
                    $email_body .= "Pesanan Anda #{$order['id']} telah dibatalkan.\n";
                    $email_body .= "Jika Anda memiliki pertanyaan, silakan hubungi kami.\n\n";
                    $email_body .= "Terima kasih telah berbelanja di Bulefotokopi.";
                    mail($order['email'], 'Pesanan Dibatalkan', $email_body);
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