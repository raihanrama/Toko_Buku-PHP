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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $error = 'Semua field harus diisi';
        } else {
            try {
                $conn = getDBConnection();
                
                // Check if username exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username sudah digunakan';
                } else {
                    // Check if email exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'Email sudah digunakan';
                    } else {
                        // Insert new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            INSERT INTO users (username, email, password, full_name, phone, address, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $role]);
                        
                        $success = 'Pengguna berhasil ditambahkan';
                    }
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    } elseif (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        $email = $_POST['email'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($email) || empty($full_name)) {
            $error = 'Email dan nama lengkap harus diisi';
        } else {
            try {
                $conn = getDBConnection();
                
                // Check if email exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $error = 'Email sudah digunakan';
                } else {
                    // Update user
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET email = ?, full_name = ?, phone = ?, address = ?, role = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $full_name, $phone, $address, $role, $user_id]);
                    
                    // Update password if provided
                    if (!empty($_POST['password'])) {
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                    }
                    
                    $success = 'Pengguna berhasil diperbarui';
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $conn = getDBConnection();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$user_id]);
            
            $success = 'Pengguna berhasil dihapus';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Terjadi kesalahan sistem';
        }
    }
}

// Get all users
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
    $users = [];
}

$current_page = 'users.php';
ob_start();
?>

<header class="admin-header">
    <h1>Kelola Pengguna</h1>
    <button class="btn btn-success" onclick="showAddUserModal()">
        <i class="fas fa-plus"></i> Tambah Pengguna
    </button>
</header>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="showEditUserModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmDeleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <h2>Tambah Pengguna</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Telepon</label>
                <input type="tel" id="phone" name="phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="address">Alamat</label>
                <textarea id="address" name="address" class="form-control"></textarea>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_user" class="btn btn-success">Tambah</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <h2>Edit Pengguna</h2>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label for="edit_username">Username</label>
                <input type="text" id="edit_username" class="form-control" disabled>
            </div>
            
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_password">Password</label>
                <input type="password" id="edit_password" name="password" class="form-control">
                <p class="help-text">Biarkan kosong jika tidak ingin mengubah password</p>
            </div>
            
            <div class="form-group">
                <label for="edit_full_name">Nama Lengkap</label>
                <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_phone">Telepon</label>
                <input type="tel" id="edit_phone" name="phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="edit_address">Alamat</label>
                <textarea id="edit_address" name="address" class="form-control"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_role">Role</label>
                <select id="edit_role" name="role" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_user" class="btn btn-success">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content">
        <h2>Konfirmasi Hapus</h2>
        <p>Apakah Anda yakin ingin menghapus pengguna ini?</p>
        <form method="POST">
            <input type="hidden" name="user_id" id="delete_user_id">
            <div class="form-actions">
                <button type="submit" name="delete_user" class="btn btn-danger">Hapus</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddUserModal() {
    document.getElementById('addUserModal').style.display = 'block';
}

function showEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_phone').value = user.phone;
    document.getElementById('edit_address').value = user.address;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('editUserModal').style.display = 'block';
}

function confirmDeleteUser(userId) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('deleteUserModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<style>
.role-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.role-admin {
    background: #7a3cff;
    color: #fff;
}

.role-user {
    background: #3fa8ff;
    color: #fff;
}

.help-text {
    font-size: 0.8rem;
    color: #aaaaaa;
    margin-top: 0.3rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
}

.modal-content {
    background: #222222;
    width: 90%;
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    color: #e0e0e0;
}

.modal p {
    color: #e0e0e0;
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?> 