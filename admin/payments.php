<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_payment'])) {
        $payment_id = $_POST['payment_id'] ?? 0;
        $type = $_POST['type'] ?? ''; // 'order' or 'print_job'
        
        if (empty($payment_id) || empty($type)) {
            $error = 'Data tidak lengkap';
        } else {
            try {
                $conn = getDBConnection();
                
                if ($type === 'order') {
                    // Update order status
                    $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                    $stmt->execute([$payment_id]);
                } else {
                    // Update print job status
                    $stmt = $conn->prepare("UPDATE print_jobs SET status = 'processing' WHERE id = ?");
                    $stmt->execute([$payment_id]);
                }
                
                $success = 'Pembayaran berhasil diverifikasi';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    }
}

// Get all pending payments
try {
    $conn = getDBConnection();
    
    // Get pending order payments
    $stmt = $conn->query("
        SELECT o.*, u.username, u.full_name, u.email, u.phone,
               'order' as type, o.total_amount as amount
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending'
        ORDER BY o.created_at DESC
    ");
    $order_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending print job payments
    $stmt = $conn->query("
        SELECT pj.*, u.username, u.full_name, u.email, u.phone,
               'print_job' as type, pj.total_cost as amount
        FROM print_jobs pj
        JOIN users u ON pj.user_id = u.id
        WHERE pj.status = 'pending'
        ORDER BY pj.created_at DESC
    ");
    $print_job_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and sort all payments
    $payments = array_merge($order_payments, $print_job_payments);
    usort($payments, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
    $payments = [];
}

$current_page = 'payments.php';
ob_start();
?>

<header class="admin-header">
    <h1>Kelola Pembayaran</h1>
</header>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Jenis</th>
                    <th>Total</th>
                    <th>Metode</th>
                    <th>Tanggal</th>
                    <th>Bukti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>#<?php echo $payment['id']; ?></td>
                        <td>
                            <div class="customer-info">
                                <strong><?php echo htmlspecialchars($payment['full_name']); ?></strong>
                                <p class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($payment['phone']); ?></p>
                            </div>
                        </td>
                        <td>
                            <?php if ($payment['type'] === 'order'): ?>
                                <span class="type-badge type-order">Pesanan</span>
                            <?php else: ?>
                                <span class="type-badge type-print">Cetak</span>
                            <?php endif; ?>
                        </td>
                        <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <?php if ($payment['payment_proof']): ?>
                                <a href="../<?php echo htmlspecialchars($payment['payment_proof']); ?>" 
                                   target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-image"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="verifyPayment(<?php echo $payment['id']; ?>, '<?php echo $payment['type']; ?>')">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php if ($payment['type'] === 'order'): ?>
                                <a href="order-detail.php?order_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php else: ?>
                                <a href="print-job-detail.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function verifyPayment(paymentId, type) {
    if (confirm('Apakah Anda yakin ingin memverifikasi pembayaran ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="payment_id" value="${paymentId}">
            <input type="hidden" name="type" value="${type}">
            <input type="hidden" name="verify_payment" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
.customer-info {
    font-size: 0.9rem;
}

.customer-info strong {
    display: block;
    margin-bottom: 0.3rem;
    color: #e0e0e0;
}

.text-muted {
    color: #aaaaaa;
    margin: 0;
    font-size: 0.8rem;
}

.type-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.type-order {
    background: #7a3cff;
    color: #fff;
}

.type-print {
    background: #3fa8ff;
    color: #fff;
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?> 