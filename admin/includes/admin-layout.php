<?php
// Include this at the top to ensure proper session handling
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bulefotokopi</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --text-color: #e0e0e0;
            --bg-dark: #121212;
            --bg-darker: #0a0a0a;
            --bg-light: #222222;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-darker);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 10;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
            display: block;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar-nav-item {
            display: block;
            padding: 0.8rem 1.5rem;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: var(--primary-color);
        }

        .sidebar-nav-item.active {
            background-color: rgba(52, 152, 219, 0.1);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }

        .sidebar-nav-item i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 300;
            color: var(--text-color);
        }

        .admin-card {
            background-color: var(--bg-light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th, .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-table th {
            background-color: rgba(0, 0, 0, 0.2);
            font-weight: 600;
            color: var(--text-color);
        }

        .admin-table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-sm {
            padding: 0.35rem 0.7rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-info {
            background-color: #3498db;
            color: white;
        }

        .btn-info:hover {
            background-color: #2980b9;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: var(--bg-dark);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid var(--success-color);
            color: #2ecc71;
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--danger-color);
            color: #e74c3c;
        }

        .alert-warning {
            background-color: rgba(243, 156, 18, 0.2);
            border: 1px solid var(--warning-color);
            color: #f39c12;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: var(--bg-light);
            width: 90%;
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            color: var(--text-color);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .nav-toggle {
                display: block;
            }
        }

        /* Nav toggle for mobile */
        .nav-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 20;
        }

        /* Utilities */
        .text-center {
            text-align: center;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">BULEFOTOKOPI</a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="products.php" class="sidebar-nav-item <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Produk
            </a>
            <a href="orders.php" class="sidebar-nav-item <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Pesanan
            </a>
            <a href="print-jobs.php" class="sidebar-nav-item <?php echo $current_page === 'print-jobs.php' ? 'active' : ''; ?>">
                <i class="fas fa-print"></i> Print Jobs
            </a>
            <a href="users.php" class="sidebar-nav-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Pengguna
            </a>
            <a href="payments.php" class="sidebar-nav-item <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill"></i> Pembayaran
            </a>
            <a href="../logout.php" class="sidebar-nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Mobile Nav Toggle -->
    <button class="nav-toggle" id="navToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <main class="main-content">
        <?php echo $content; ?>
    </main>

    <!-- Scripts -->
    <script>
        // Mobile navigation toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close modals when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            // Get all modals
            var modals = document.querySelectorAll('.modal');
            
            // When the user clicks anywhere outside of the modal content, close it
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    for (var i = 0; i < modals.length; i++) {
                        if (modals[i] === event.target) {
                            modals[i].style.display = "none";
                        }
                    }
                }
            };
        });

        // Fix for modal issue
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
    </script>
</body>
</html>