<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Check if print_job_id is provided
if (!isset($_GET['id'])) {
    header('Location: print-jobs.php');
    exit;
}

$print_job_id = $_GET['id'];
$error = '';

try {
    $conn = getDBConnection();
    
    // Get print job details
    $stmt = $conn->prepare("
        SELECT pj.*, u.username, u.email, u.phone, u.address 
        FROM print_jobs pj 
        JOIN users u ON pj.user_id = u.id 
        WHERE pj.id = ?
    ");
    $stmt->execute([$print_job_id]);
    $print_job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$print_job) {
        header('Location: print-jobs.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Layanan Cetak - Admin Bulefotokopi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <h2>Admin Panel</h2>
            </div>
            <nav class="admin-menu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="orders.php">Pesanan</a></li>
                    <li><a href="print-jobs.php" class="active">Layanan Cetak</a></li>
                    <li><a href="products.php">Produk</a></li>
                    <li><a href="users.php">Pengguna</a></li>
                    <li><a href="../logout.php">Keluar</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <h1>Detail Layanan Cetak #<?php echo $print_job_id; ?></h1>
                <a href="print-jobs.php" class="btn secondary">Kembali</a>
            </header>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="print-job-detail">
                <section class="customer-info">
                    <h2>Informasi Pelanggan</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nama</label>
                            <p><?php echo htmlspecialchars($print_job['username']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($print_job['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Telepon</label>
                            <p><?php echo htmlspecialchars($print_job['phone'] ?? '-'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Alamat</label>
                            <p><?php echo htmlspecialchars($print_job['address'] ?? '-'); ?></p>
                        </div>
                    </div>
                </section>

                <section class="print-job-info">
                    <h2>Informasi Cetak</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Status</label>
                            <p>
                                <span class="status-badge status-<?php echo $print_job['status']; ?>">
                                    <?php echo ucfirst($print_job['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="info-item">
                            <label>Metode Pembayaran</label>
                            <p><?php echo ucfirst(str_replace('_', ' ', $print_job['payment_method'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Tanggal Pesanan</label>
                            <p><?php echo date('d/m/Y H:i', strtotime($print_job['created_at'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Total Pembayaran</label>
                            <p class="total">Rp <?php echo number_format($print_job['total_price'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </section>

                <section class="print-details">
                    <h2>Detail Cetak</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>File</label>
                            <p><?php echo htmlspecialchars($print_job['file_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Jumlah Cetak</label>
                            <p><?php echo $print_job['copies']; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Ukuran Kertas</label>
                            <p><?php echo $print_job['paper_size']; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Cetak Warna</label>
                            <p><?php echo $print_job['is_color'] ? 'Ya' : 'Tidak'; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Bolak-Balik</label>
                            <p><?php echo $print_job['is_double_sided'] ? 'Ya' : 'Tidak'; ?></p>
                        </div>
                    </div>
                </section>

                <?php if ($print_job['payment_method'] === 'bank_transfer' && $print_job['payment_proof']): ?>
                    <section class="payment-proof">
                        <h2>Bukti Pembayaran</h2>
                        <img src="../<?php echo htmlspecialchars($print_job['payment_proof']); ?>" 
                             alt="Bukti Pembayaran" class="payment-proof-image">
                    </section>
                <?php endif; ?>

                <section class="file-preview">
                    <h2>Preview File</h2>
                    <div class="file-preview-container">
                        <?php
                        $file_extension = strtolower(pathinfo($print_job['file_name'], PATHINFO_EXTENSION));
                        if ($file_extension === 'pdf'): ?>
                            <iframe src="../<?php echo htmlspecialchars($print_job['file_path']); ?>" 
                                    class="pdf-preview"></iframe>
                        <?php else: ?>
                            <p class="no-preview">Preview tidak tersedia untuk file <?php echo strtoupper($file_extension); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="../<?php echo htmlspecialchars($print_job['file_path']); ?>" 
                       class="btn primary" download>
                        Download File
                    </a>
                </section>
            </div>
        </main>
    </div>
</body>
</html> 