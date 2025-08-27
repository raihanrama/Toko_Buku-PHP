<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo 'Unauthorized access';
    exit;
}

if (isset($_GET['job_id'])) {
    $job_id = $_GET['job_id'];
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT pj.*, u.username, u.full_name, u.email, u.phone
            FROM print_jobs pj
            JOIN users u ON pj.user_id = u.id
            WHERE pj.id = ?
        ");
        $stmt->execute([$job_id]);
        $print_job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($print_job) {
            ?>
            <div class="print-job-detail">
                <h2>Detail Layanan Cetak #<?php echo $print_job['id']; ?></h2>
                
                <div class="print-job-info">
                    <div class="info-card">
                        <h3>Informasi Pelanggan</h3>
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($print_job['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($print_job['email']); ?></p>
                        <p><strong>Telepon:</strong> <?php echo htmlspecialchars($print_job['phone']); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Detail Pesanan</h3>
                        <p><strong>File:</strong> <?php echo htmlspecialchars($print_job['file_name']); ?></p>
                        <p><strong>Jumlah Halaman:</strong> <?php echo $print_job['page_count']; ?></p>
                        <p><strong>Jumlah Copy:</strong> <?php echo $print_job['copies']; ?></p>
                        <p><strong>Total Harga:</strong> Rp <?php echo number_format($print_job['total_price'], 0, ',', '.'); ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Status & Waktu</h3>
                        <p><strong>Status:</strong> <?php echo ucfirst($print_job['status']); ?></p>
                        <p><strong>Tanggal Pesanan:</strong> <?php echo date('d/m/Y H:i', strtotime($print_job['created_at'])); ?></p>
                        <?php if ($print_job['updated_at']): ?>
                            <p><strong>Terakhir Diperbarui:</strong> <?php echo date('d/m/Y H:i', strtotime($print_job['updated_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="file-preview">
                    <h3>File Dokumen</h3>
                    <div class="file-actions">
                        <button onclick="openFile('../<?php echo htmlspecialchars($print_job['file_path']); ?>')" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Lihat File
                        </button>
                        <button onclick="printFile('../<?php echo htmlspecialchars($print_job['file_path']); ?>')" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="../<?php echo htmlspecialchars($print_job['file_path']); ?>" download class="btn btn-primary">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
            <?php
        } else {
            echo '<div class="error-message">Print job tidak ditemukan</div>';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo '<div class="error-message">Terjadi kesalahan sistem</div>';
    }
} else {
    echo '<div class="error-message">Invalid request</div>';
}
?> 