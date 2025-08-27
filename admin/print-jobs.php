<?php
session_start();
require_once '../config/database.php';
require_once '../config/mail.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Handle print job actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $print_job_id = $_POST['print_job_id'] ?? 0;
        $new_status = $_POST['status'] ?? '';
        
        if (empty($print_job_id) || empty($new_status)) {
            $error = 'Data tidak lengkap';
        } else {
            try {
                $conn = getDBConnection();
                
                // Update print job status
                $stmt = $conn->prepare("UPDATE print_jobs SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $print_job_id]);
                
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
                    switch ($new_status) {
                        case 'processing':
                            $email_body = getPrintJobProcessingEmail($print_job['username'], $print_job_id);
                            sendEmail($print_job['email'], 'Dokumen Anda Sedang Diproses', $email_body);
                            break;
                        case 'ready':
                            $email_body = getPrintJobReadyEmail($print_job['username'], $print_job_id);
                            sendEmail($print_job['email'], 'Dokumen Anda Siap Diambil', $email_body);
                            break;
                        case 'completed':
                            $email_body = getPrintJobCompletedEmail($print_job['username'], $print_job_id);
                            sendEmail($print_job['email'], 'Dokumen Anda Telah Diambil', $email_body);
                            break;
                        case 'cancelled':
                            $email_body = getPrintJobCancelledEmail($print_job['username'], $print_job_id);
                            sendEmail($print_job['email'], 'Dokumen Anda Dibatalkan', $email_body);
                            break;
                    }
                }
                
                $success = 'Status layanan cetak berhasil diperbarui';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    }
}

// Get all print jobs
try {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT pj.*, u.username, u.full_name, u.email, u.phone
        FROM print_jobs pj
        JOIN users u ON pj.user_id = u.id
        ORDER BY pj.created_at DESC
    ");
    $print_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
    $print_jobs = [];
}

$current_page = 'print-jobs.php';
ob_start();
?>

<header class="admin-header">
    <h1>Kelola Layanan Cetak</h1>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>File</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($print_jobs as $job): ?>
                    <tr>
                        <td>#<?php echo $job['id']; ?></td>
                        <td>
                            <div class="customer-info">
                                <strong><?php echo htmlspecialchars($job['full_name']); ?></strong>
                                <p class="text-muted"><?php echo htmlspecialchars($job['email']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($job['phone']); ?></p>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($job['file_name']); ?></td>
                        <td>Rp <?php echo number_format($job['total_price'], 0, ',', '.'); ?></td>
                        <td>
                            <select class="status-select" onchange="updatePrintJobStatus(<?php echo $job['id']; ?>, this.value)">
                                <option value="pending" <?php echo $job['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $job['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="ready" <?php echo $job['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                <option value="completed" <?php echo $job['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $job['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($job['created_at'])); ?></td>
                        <td>
                            <button onclick="viewPrintJobDetail(<?php echo $job['id']; ?>)" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="deletePrintJob(<?php echo $job['id']; ?>)" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print Job Detail Modal -->
<div id="printJobDetailModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="printJobDetailContent">
            <!-- Print job detail will be loaded here -->
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</div>

<script>
function updatePrintJobStatus(jobId, newStatus) {
    if (confirm('Apakah Anda yakin ingin mengubah status layanan cetak ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="print_job_id" value="${jobId}">
            <input type="hidden" name="status" value="${newStatus}">
            <input type="hidden" name="update_status" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deletePrintJob(id) {
    if (confirm('Apakah Anda yakin ingin menghapus layanan cetak ini? Tindakan ini tidak dapat dibatalkan.')) {
        fetch('delete-print-job.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `print_job_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menghapus layanan cetak: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan sistem');
        });
    }
}

function viewPrintJobDetail(jobId) {
    // Show modal and loading spinner
    const modal = document.getElementById('printJobDetailModal');
    const content = document.getElementById('printJobDetailContent');
    modal.style.display = 'block';
    content.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
    
    // Fetch print job details
    fetch(`get-print-job-detail.php?job_id=${jobId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="error-message">Terjadi kesalahan saat memuat detail print job</div>';
        });
}

// Close modal when clicking X
document.querySelector('.close-modal').onclick = function() {
    document.getElementById('printJobDetailModal').style.display = 'none';
}

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    const modal = document.getElementById('printJobDetailModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Function to open file in new tab
function openFile(filePath) {
    window.open(filePath, '_blank');
}

// Function to print file
function printFile(filePath) {
    window.open(filePath, '_blank').print();
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

.status-select {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    border: 1px solid #444444;
    background: #333333;
    color: #e0e0e0;
    font-size: 0.9rem;
}

.status-select:focus {
    border-color: #7a3cff;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(122, 60, 255, 0.25);
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: #fff;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.85);
}

.modal-content {
    position: relative;
    background-color: #212121;
    margin: 5% auto;
    padding: 20px;
    border-radius: 12px;
    width: 80%;
    max-width: 1200px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.close-modal {
    position: absolute;
    right: 20px;
    top: 10px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10;
}

.close-modal:hover,
.close-modal:focus {
    color: var(--primary-color);
    text-decoration: none;
}

/* Loading spinner */
.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 0;
}

.spinner {
    border: 5px solid rgba(255, 255, 255, 0.1);
    border-top: 5px solid var(--primary-color);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Print Job Detail Styles */
.print-job-detail {
    color: #e0e0e0;
}

.print-job-detail h2 {
    margin-bottom: 1.5rem;
    color: var(--primary-color);
}

.print-job-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 1rem;
    border-radius: 8px;
}

.info-card h3 {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    color: #aaa;
}

.info-card p {
    margin: 0;
    font-size: 1.1rem;
    color: #fff;
}

.file-preview {
    margin-top: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.file-preview h3 {
    margin: 0 0 1rem;
    color: var(--primary-color);
}

.file-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-color-dark);
}

.btn-secondary {
    background: #666;
    color: white;
}

.btn-secondary:hover {
    background: #555;
}

.error-message {
    background: rgba(220, 53, 69, 0.1);
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?> 