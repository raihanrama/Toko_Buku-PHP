<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get statistics
try {
    $conn = getDBConnection();
    
    // Total orders
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();
    
    // Total products
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();
    
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $total_users = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('completed', 'paid')");
    $total_revenue = $stmt->fetchColumn() ?? 0;
    
    // Recent orders with status counts
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM orders 
        GROUP BY status
    ");
    $order_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent orders
    $stmt = $conn->query("
        SELECT o.*, u.username, u.full_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
}

$current_page = 'dashboard.php';
ob_start();
?>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1>Selamat Datang, Admin!</h1>
        <p>Berikut adalah ringkasan aktivitas toko Anda</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon orders">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-details">
                <h3>Total Pesanan</h3>
                <p class="stat-number"><?php echo number_format($total_orders); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon products">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <h3>Total Produk</h3>
                <p class="stat-number"><?php echo number_format($total_products); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon users">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Total Pengguna</h3>
                <p class="stat-number"><?php echo number_format($total_users); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon revenue">
                <i class="fas fa-money-bill"></i>
            </div>
            <div class="stat-details">
                <h3>Total Pendapatan</h3>
                <p class="stat-number">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></p>
            </div>
        </div>
    </div>

    <!-- Order Status Overview -->
    <div class="dashboard-sections">
        <div class="section-grid">
            <!-- Order Status Cards -->
            <div class="status-overview">
                <h2>Total Pesanan</h2>
                <div class="status-cards">
                    <?php
                    $status_colors = [
                        'pending' => '#ffd54f',
                        'processing' => '#64b5f6',
                        'completed' => '#81c784',
                        'cancelled' => '#e57373',
                        'paid' => '#4fc3f7'
                    ];
                    
                    foreach ($order_stats as $stat): 
                        $color = $status_colors[$stat['status']] ?? '#9e9e9e';
                    ?>
                    <div class="status-card" style="--status-color: <?php echo $color; ?>">
                        <h4><?php echo ucfirst($stat['status']); ?></h4>
                        <p><?php echo $stat['count']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2>Aktivitas Terbaru</h2>
                <div class="activity-list">
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="activity-details">
                            <h4>Pesanan #<?php echo $order['id']; ?></h4>
                            <p>
                                <?php echo htmlspecialchars($order['full_name'] ?? $order['username']); ?> -
                                Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                            </p>
                            <span class="activity-time">
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                        <div class="activity-status <?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.welcome-section {
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #2c3e50, #3498db);
    border-radius: 15px;
    color: white;
}

.welcome-section h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.welcome-section p {
    margin: 0.5rem 0 0;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #1e1e1e;
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 1.5rem;
    color: white;
}

.stat-icon.orders { background: linear-gradient(135deg, #FF6B6B, #ee5253); }
.stat-icon.products { background: linear-gradient(135deg, #4834d4, #686de0); }
.stat-icon.users { background: linear-gradient(135deg, #6c5ce7, #a55eea); }
.stat-icon.revenue { background: linear-gradient(135deg, #00b894, #00cec9); }

.stat-details h3 {
    margin: 0;
    font-size: 1rem;
    color: #a0a0a0;
}

.stat-number {
    margin: 0.5rem 0 0;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
}

.section-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.status-overview, .recent-activity {
    background: #1e1e1e;
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.status-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    border-left: 4px solid var(--status-color);
}

.status-card h4 {
    margin: 0;
    font-size: 0.9rem;
    color: var(--status-color);
}

.status-card p {
    margin: 0.5rem 0 0;
    font-size: 1.2rem;
    font-weight: bold;
    color: white;
}

.activity-list {
    margin-top: 1rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.activity-icon i {
    color: #4fc3f7;
}

.activity-details {
    flex: 1;
}

.activity-details h4 {
    margin: 0;
    font-size: 1rem;
    color: white;
}

.activity-details p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
    color: #a0a0a0;
}

.activity-time {
    font-size: 0.8rem;
    color: #666;
}

.activity-status {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: capitalize;
}

.activity-status.pending { background: rgba(255, 193, 7, 0.2); color: #ffd54f; }
.activity-status.processing { background: rgba(33, 150, 243, 0.2); color: #64b5f6; }
.activity-status.completed { background: rgba(76, 175, 80, 0.2); color: #81c784; }
.activity-status.cancelled { background: rgba(244, 67, 54, 0.2); color: #e57373; }
.activity-status.paid { background: rgba(3, 169, 244, 0.2); color: #4fc3f7; }

h2 {
    margin: 0 0 1rem;
    font-size: 1.25rem;
    color: white;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .welcome-section {
        padding: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .section-grid {
        grid-template-columns: 1fr;
    }
    
    .status-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?> 