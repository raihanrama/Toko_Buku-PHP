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

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received: " . print_r($_POST, true));
    
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'] ?? 0;
        $new_status = $_POST['status'] ?? '';
        
        error_log("Attempting to update order #$order_id to status: $new_status");
        
        if (empty($order_id) || empty($new_status)) {
            $error = 'Data tidak lengkap';
            error_log("Update failed: Incomplete data");
            // Tambahkan respons JSON untuk AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
                exit;
            }
        } else {
            try {
                $conn = getDBConnection();
                
                // Start transaction
                $conn->beginTransaction();
                error_log("Transaction started for order #$order_id");
                
                // Update order status
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $result = $stmt->execute([$new_status, $order_id]);
                
                if (!$result) {
                    error_log("Failed to update order status. SQL Error: " . print_r($stmt->errorInfo(), true));
                    throw new PDOException("Failed to update order status");
                }
                
                error_log("Order status updated in database");
                
                // Get order and user details for email
                $stmt = $conn->prepare("
                    SELECT o.*, u.email, u.username, u.full_name 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?
                ");
                $stmt->execute([$order_id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    error_log("Order details retrieved: " . print_r($order, true));
                    
                    // Send email notification based on status
                    try {
                        $email_body = '';
                        $email_subject = '';
                        
                        switch ($new_status) {
                            case 'processing':
                                $email_body = getOrderProcessingEmail($order['full_name'] ?? $order['username'], $order_id);
                                $email_subject = 'Pesanan Anda Sedang Diproses';
                                break;
                            case 'completed':
                                $email_body = getOrderCompletedEmail($order['full_name'] ?? $order['username'], $order_id);
                                $email_subject = 'Pesanan Anda Selesai';
                                break;
                            case 'cancelled':
                                $email_body = getOrderCancelledEmail($order['full_name'] ?? $order['username'], $order_id);
                                $email_subject = 'Pesanan Anda Dibatalkan';
                                break;
                            case 'paid':
                                $email_body = getOrderPaidEmail($order['full_name'] ?? $order['username'], $order_id);
                                $email_subject = 'Pembayaran Diterima';
                                break;
                        }
                        
                        error_log("Preparing to send email with subject: $email_subject");
                        
                        if ($email_body && $email_subject && !empty($order['email'])) {
                            $emailResult = sendEmail($order['email'], $email_subject, $email_body);
                            if (!$emailResult) {
                                error_log("Failed to send email notification for order #$order_id");
                            } else {
                                error_log("Email sent successfully to: " . $order['email']);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Email error for order #$order_id: " . $e->getMessage());
                    }
                } else {
                    error_log("Order not found after status update");
                }
                
                // Commit transaction
                $conn->commit();
                error_log("Transaction committed successfully");
                $success = 'Status pesanan berhasil diperbarui';
                
                // Tambahkan respons JSON untuk AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => true, 'message' => 'Status pesanan berhasil diperbarui']);
                    exit;
                }
                
            } catch (PDOException $e) {
                // Rollback transaction on error
                $conn->rollBack();
                error_log("Database error: " . $e->getMessage());
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
                
                // Tambahkan respons JSON untuk AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
                    exit;
                }
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("General error: " . $e->getMessage());
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
                
                // Tambahkan respons JSON untuk AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
                    exit;
                }
            }
        }
    } elseif (isset($_POST['verify_payment'])) {
        $order_id = $_POST['order_id'] ?? 0;
        
        try {
            $conn = getDBConnection();
            
            // Update order status to paid
            $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            $success = 'Pembayaran berhasil diverifikasi';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Terjadi kesalahan sistem';
        }
    }
}

// Get all orders
try {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT o.*, u.username, u.full_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
    $orders = [];
}

$current_page = 'orders.php';
ob_start();
?>

<header class="admin-header">
    <h1>Kelola Pesanan</h1>
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
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>
                            <div class="customer-info">
                                <strong><?php echo htmlspecialchars($order['full_name'] ?? $order['username']); ?></strong>
                                <p class="text-muted"><?php echo htmlspecialchars($order['email'] ?? 'Email tidak tersedia'); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($order['phone'] ?? 'No. Telp tidak tersedia'); ?></p>
                            </div>
                        </td>
                        <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                        <td>
                            <select class="status-select" data-current="<?php echo $order['status']; ?>" onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            </select>
                            <div class="status-feedback" id="status-feedback-<?php echo $order['id']; ?>"></div>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <button onclick="viewOrderDetail(<?php echo $order['id']; ?>)" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="deleteOrder(<?php echo $order['id']; ?>, 'order')" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Detail Modal -->
<div id="orderDetailModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="orderDetailContent">
            <!-- Order detail will be loaded here -->
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(orderId, newStatus) {
    if (!confirm('Apakah Anda yakin ingin mengubah status pesanan ini?')) {
        // Reset the select to previous value if user cancels
        const select = document.querySelector(`select[onchange="updateOrderStatus(${orderId}, this.value)"]`);
        select.value = select.getAttribute('data-current');
        return;
    }

    // Show loading state
    const statusSelect = document.querySelector(`select[onchange="updateOrderStatus(${orderId}, this.value)"]`);
    const originalStatus = statusSelect.value;
    statusSelect.disabled = true;
    
    // Get feedback element
    const feedbackElement = document.getElementById(`status-feedback-${orderId}`);
    feedbackElement.innerHTML = '<div class="spinner-border spinner-border-sm ms-2">Memproses...</div>';
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    formData.append('update_status', '1');

    // Tambahkan header X-Requested-With untuk identifikasi AJAX
    const fetchOptions = {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    // Use fetch API to submit the form
    fetch(window.location.href, fetchOptions)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Ubah ke JSON
    })
    .then(data => {
        // Check if the operation was successful
        if (data.success) {
            // Update the data-current attribute
            statusSelect.setAttribute('data-current', newStatus);
            
            // Show success message
            feedbackElement.innerHTML = '<div class="alert alert-success">Status berhasil diperbarui</div>';
            
            // Remove feedback after 3 seconds
            setTimeout(() => {
                feedbackElement.innerHTML = '';
            }, 3000);
        } else {
            throw new Error(data.message || 'Update gagal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error message
        feedbackElement.innerHTML = `<div class="alert alert-danger">Gagal mengupdate status: ${error.message}</div>`;
        
        // Reset select to original value
        statusSelect.value = originalStatus;
    })
    .finally(() => {
        // Re-enable select
        statusSelect.disabled = false;
    });
}

function deleteOrder(id, type) {
    if (confirm('Apakah Anda yakin ingin menghapus pesanan ini? Tindakan ini tidak dapat dibatalkan.')) {
        fetch('delete-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `order_id=${id}&type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menghapus pesanan: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan sistem');
        });
    }
}

// Modal functionality
const modal = document.getElementById('orderDetailModal');
const closeModal = document.getElementsByClassName('close-modal')[0];
const orderDetailContent = document.getElementById('orderDetailContent');

function viewOrderDetail(orderId) {
    // Show modal and loading spinner
    modal.style.display = 'block';
    orderDetailContent.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
    
    // Fetch order details
    fetch(`get-order-detail.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            orderDetailContent.innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            orderDetailContent.innerHTML = '<div class="error-message">Terjadi kesalahan saat memuat detail pesanan</div>';
        });
}

// Close modal when clicking X
closeModal.onclick = function() {
    modal.style.display = 'none';
}

// Close modal when clicking outside the modal content
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
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

/* Status feedback */
.status-feedback {
    font-size: 0.8rem;
    margin-top: 5px;
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
    background-color: rgba(0, 0, 0, 0.85); /* Darker overlay for better contrast */
}

.modal-content {
    position: relative;
    background-color: #212121; /* Solid dark background color */
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

.alert {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #ff6b6b;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    color: #2ecc71;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.error-message {
    background: rgba(220, 53, 69, 0.1);
    color: #ff6b6b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.spinner-border {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 0.2em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
    vertical-align: middle;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.2em;
}

.ms-2 {
    margin-left: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?>