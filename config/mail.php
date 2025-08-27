<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Email Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'muhammadraihan291003@gmail.com');
// Ganti dengan password aplikasi, bukan password akun Gmail
define('MAIL_PASSWORD', 'vcgu cupo vdsr atbi'); // Gunakan App Password dari Google
define('MAIL_FROM_ADDRESS', 'muhammadraihan291003@gmail.com');
define('MAIL_FROM_NAME', 'Aqilafotokopi');

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // Turunkan level debug agar tidak terlalu banyak output
        $mail->SMTPDebug = 0; // 0: off, 1: client messages, 2: client and server messages
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        
        // Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($body);
        $mail->CharSet = 'UTF-8';
        
        // Send email
        $result = $mail->send();
        error_log("Email sent successfully to: $to");
        return $result;
    } catch (Exception $e) {
        error_log("Email sending failed. Error: " . $mail->ErrorInfo);
        error_log("Details - To: $to, Subject: $subject");
        return false;
    }
}

// Email Templates
function getPrintJobReadyEmail($userName, $jobId) {
    return "
    <h2>Dokumen Anda Siap Diambil</h2>
    <p>Halo {$userName},</p>
    <p>Dokumen Anda dengan ID #{$jobId} telah siap untuk diambil di toko kami.</p>
    <p>Silakan datang ke toko kami dengan membawa bukti pembayaran.</p>
    <p>Terima kasih telah menggunakan layanan kami.</p>
    ";
}

function getOrderReceiptEmail($userName, $orderId, $items, $total) {
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= "<tr>
            <td>{$item['name']}</td>
            <td>{$item['quantity']}</td>
            <td>Rp " . number_format($item['price'], 0, ',', '.') . "</td>
            <td>Rp " . number_format($item['total'], 0, ',', '.') . "</td>
        </tr>";
    }

    return "
    <h2>Struk Pembelian</h2>
    <p>Halo {$userName},</p>
    <p>Terima kasih atas pembelian Anda. Berikut adalah detail pesanan Anda:</p>
    <table border='1' style='border-collapse: collapse; width: 100%;'>
        <tr>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Harga</th>
            <th>Total</th>
        </tr>
        {$itemsHtml}
    </table>
    <p><strong>Total Pembayaran: Rp " . number_format($total, 0, ',', '.') . "</strong></p>
    <p>Silakan tunjukkan email ini saat mengambil barang di toko kami.</p>
    <p>Terima kasih telah berbelanja di Aqilafotokopi.</p>
    ";
}

function getOrderProcessingEmail($username, $order_id) {
    return "
    <h2>Pesanan Sedang Diproses</h2>
    <p>Halo $username,</p>
    <p>Pesanan #$order_id Anda sedang diproses. Kami akan segera menyiapkan pesanan Anda.</p>
    <p>Terima kasih telah berbelanja di Aqilafotokopi.</p>
    <br>
    <p>Salam,<br>Tim Aqilafotokopi</p>
    ";
}

function getOrderCompletedEmail($username, $order_id) {
    return "
    <h2>Pesanan Selesai</h2>
    <p>Halo $username,</p>
    <p>Pesanan #$order_id Anda telah selesai diproses dan siap untuk diambil/dikirim.</p>
    <p>Terima kasih telah berbelanja di Aqilafotokopi.</p>
    <br>
    <p>Salam,<br>Tim Aqilafotokopi</p>
    ";
}

function getOrderCancelledEmail($username, $order_id) {
    return "
    <h2>Pesanan Dibatalkan</h2>
    <p>Halo $username,</p>
    <p>Pesanan #$order_id Anda telah dibatalkan.</p>
    <p>Jika Anda telah melakukan pembayaran, dana akan dikembalikan sesuai dengan metode pembayaran yang Anda gunakan.</p>
    <p>Jika Anda memiliki pertanyaan, silakan hubungi customer service kami.</p>
    <p>Terima kasih atas pengertian Anda.</p>
    <br>
    <p>Salam,<br>Tim Aqilafotokopi</p>
    ";
}

function getOrderPaidEmail($username, $order_id) {
    return "
    <h2>Pembayaran Diterima</h2>
    <p>Halo $username,</p>
    <p>Pembayaran untuk pesanan #$order_id Anda telah kami terima dan diverifikasi.</p>
    <p>Kami akan segera memproses pesanan Anda.</p>
    <p>Terima kasih telah berbelanja di Aqilafotokopi.</p>
    <br>
    <p>Salam,<br>Tim Aqilafotokopi</p>
    ";
}

function getPrintJobProcessingEmail($username, $jobId) {
    return "
    <h2>Dokumen Anda Sedang Diproses</h2>
    <p>Halo {$username},</p>
    <p>Dokumen Anda dengan ID #{$jobId} sedang diproses oleh tim kami.</p>
    <p>Kami akan menghubungi Anda jika dokumen sudah siap diambil.</p>
    <p>Terima kasih telah menggunakan layanan kami.</p>
    ";
}

function getPrintJobCompletedEmail($username, $jobId) {
    return "
    <h2>Dokumen Telah Diambil</h2>
    <p>Halo {$username},</p>
    <p>Dokumen Anda dengan ID #{$jobId} telah diambil. Terima kasih telah menggunakan layanan kami.</p>
    <p>Salam,<br>Bulefotokopi</p>
    ";
}

function getPrintJobCancelledEmail($username, $jobId) {
    return "
    <h2>Dokumen Dibatalkan</h2>
    <p>Halo {$username},</p>
    <p>Layanan cetak Anda dengan ID #{$jobId} telah dibatalkan.</p>
    <p>Jika Anda memiliki pertanyaan, silakan hubungi kami.</p>
    <p>Terima kasih telah menggunakan layanan kami.</p>
    ";
}