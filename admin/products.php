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

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        
        if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
            $error = 'Semua field harus diisi dengan benar';
        } else {
            try {
                $conn = getDBConnection();
                
                // Handle image upload
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image = 'uploads/products/' . $filename;
                    }
                }
                
                // Insert product
                $stmt = $conn->prepare("
                    INSERT INTO products (name, description, price, stock, image) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $price, $stock, $image]);
                
                $success = 'Produk berhasil ditambahkan';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    } elseif (isset($_POST['update_product'])) {
        $product_id = $_POST['product_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        
        if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
            $error = 'Semua field harus diisi dengan benar';
        } else {
            try {
                $conn = getDBConnection();
                
                // Handle image upload
                $image = $_POST['current_image'] ?? '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image = 'uploads/products/' . $filename;
                    }
                }
                
                // Update product
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, stock = ?, image = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $stock, $image, $product_id]);
                
                $success = 'Produk berhasil diperbarui';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Terjadi kesalahan sistem';
            }
        }
    } elseif (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'] ?? 0;
        
        try {
            $conn = getDBConnection();
            // Hapus semua order_items yang memakai produk ini
            $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
            $stmt->execute([$product_id]);
            // Hapus produk
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $success = 'Produk berhasil dihapus';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Terjadi kesalahan sistem';
        }
    }
}

// Get all products
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan sistem';
    $products = [];
}

$current_page = 'products.php';
ob_start();
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<header class="admin-header">
    <h1>Kelola Produk</h1>
    <button class="btn btn-success" onclick="showAddProductModal()">
        <i class="fas fa-plus"></i> Tambah Produk
    </button>
</header>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Gambar</th>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>#<?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="product-thumbnail">
                            <?php else: ?>
                                <div class="product-thumbnail placeholder">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="showEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <h2>Tambah Produk</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Nama Produk</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" class="form-control" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Harga</label>
                <input type="number" id="price" name="price" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stok</label>
                <input type="number" id="stock" name="stock" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="image">Gambar</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_product" class="btn btn-success">Tambah</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <h2>Edit Produk</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="edit_product_id">
            <input type="hidden" name="current_image" id="edit_current_image">
            
            <div class="form-group">
                <label for="edit_name">Nama Produk</label>
                <input type="text" id="edit_name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Deskripsi</label>
                <textarea id="edit_description" name="description" class="form-control" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_price">Harga</label>
                <input type="number" id="edit_price" name="price" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_stock">Stok</label>
                <input type="number" id="edit_stock" name="stock" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_image">Gambar</label>
                <input type="file" id="edit_image" name="image" class="form-control" accept="image/*">
                <p class="help-text">Biarkan kosong jika tidak ingin mengubah gambar</p>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_product" class="btn btn-success">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Product Confirmation Modal -->
<div id="deleteProductModal" class="modal">
    <div class="modal-content">
        <h2>Konfirmasi Hapus</h2>
        <p>Apakah Anda yakin ingin menghapus produk ini?</p>
        <form method="POST">
            <input type="hidden" name="product_id" id="delete_product_id">
            <div class="form-actions">
                <button type="submit" name="delete_product" class="btn btn-danger">Hapus</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteProductModal')">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}

function showEditProductModal(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_stock').value = product.stock;
    document.getElementById('edit_current_image').value = product.image;
    document.getElementById('editProductModal').style.display = 'block';
}

function confirmDeleteProduct(productId) {
    document.getElementById('delete_product_id').value = productId;
    document.getElementById('deleteProductModal').style.display = 'block';
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
.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.product-thumbnail.placeholder {
    background: #333333;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #aaaaaa;
    font-size: 0.8rem;
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

.modal h2 {
    margin: 0 0 1.5rem 0;
    color: #e0e0e0;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.help-text {
    font-size: 0.8rem;
    color: #aaaaaa;
    margin-top: 0.3rem;
}
</style>

<?php
$content = ob_get_clean();
include 'includes/admin-layout.php';
?> 