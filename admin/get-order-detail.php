<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied';
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo '<div class="error-message">ID pesanan tidak valid</div>';
    exit;
}

$order_id = $_GET['order_id'];
$error = '';

try {
    $conn = getDBConnection();
    
    // Get order details with user information
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name, u.email, u.phone, u.address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo '<div class="error-message">Pesanan tidak ditemukan</div>';
        exit;
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name as product_name, p.price as product_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo '<div class="error-message">Terjadi kesalahan sistem</div>';
    exit;
}

// Initialize variables with default values
$payment_method = isset($order['payment_method']) ? $order['payment_method'] : 'N/A';
$full_name = isset($order['full_name']) ? htmlspecialchars($order['full_name']) : 'N/A';
$email = isset($order['email']) ? htmlspecialchars($order['email']) : 'N/A';
$phone = isset($order['phone']) ? htmlspecialchars($order['phone']) : 'N/A';
$address = isset($order['address']) ? htmlspecialchars($order['address']) : 'N/A';
?>

<div class="order-header">
    <h1>Detail Pesanan #<?php echo $order_id; ?></h1>
</div>

<div class="info-card">
    <h2>Informasi Pelanggan</h2>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Nama</div>
            <div class="info-value"><?php echo $full_name; ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><?php echo $email; ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Telepon</div>
            <div class="info-value"><?php echo $phone; ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Alamat</div>
            <div class="info-value"><?php echo $address; ?></div>
        </div>
    </div>
</div>

<div class="info-card">
    <h2>Informasi Pesanan</h2>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">
                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">Metode Pembayaran</div>
            <div class="info-value"><?php echo ucfirst($payment_method); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Tanggal Pesanan</div>
            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
        </div>
    </div>

    <?php if (isset($order['payment_proof']) && !empty($order['payment_proof'])): ?>
    <div class="payment-proof">
        <h3>Bukti Pembayaran</h3>
        <img src="<?php echo '../' . $order['payment_proof']; ?>" alt="Bukti Pembayaran">
    </div>
    <?php endif; ?>

    <h3>Item Pesanan</h3>
    <table class="order-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($order_items) && !empty($order_items)): ?>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td>Rp <?php echo number_format($item['product_price'], 0, ',', '.'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>Rp <?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="order-total">Total</td>
                <td class="order-total">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<style>
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.info-card {
    background: #2d2d2d; /* Solid background color instead of variable */
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.info-card h2 {
    color: #e0e0e0; /* Solid color instead of variable */
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #444444; /* Solid color instead of transparent */
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    margin-bottom: 0.5rem;
}

.info-label {
    color: #aaaaaa; /* Solid color instead of variable */
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.info-value {
    color: #e0e0e0; /* Solid color instead of variable */
    font-weight: 500;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.order-table th,
.order-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #444444; /* Solid color instead of transparent */
}

.order-table th {
    background: #383838; /* Solid color instead of transparent */
    color: #e0e0e0; /* Solid color instead of variable */
    font-weight: 500;
}

.order-total {
    text-align: right;
    padding: 1rem;
    font-weight: bold;
    color: #9d68ff; /* Solid color instead of variable */
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background-color: #3d3000; /* Solid color instead of transparent */
    color: #ffc107;
}

.status-processing {
    background-color: #002a61; /* Solid color instead of transparent */
    color: #0d6efd;
}

.status-completed {
    background-color: #00402a; /* Solid color instead of transparent */
    color: #198754;
}

.status-cancelled {
    background-color: #3d0a11; /* Solid color instead of transparent */
    color: #dc3545;
}

.status-paid {
    background-color: #00402a; /* Solid color instead of transparent */
    color: #198754;
}

.payment-proof {
    margin-top: 2rem;
}

.payment-proof img {
    max-width: 100%;
    max-height: 400px;
    border-radius: 8px;
    border: 1px solid #444444; /* Solid color instead of transparent */
}

.error-message {
    background: #3d0a11; /* Solid color instead of transparent */
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}
</style>