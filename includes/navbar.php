<?php
// Get current page for active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <nav class="navbar">
        <div class="logo">
            <h1>Aqilafotokopi</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>Beranda</a></li>
            <li><a href="products.php" <?php echo $current_page === 'products.php' ? 'class="active"' : ''; ?>>Produk ATK</a></li>
            <li><a href="print.php" <?php echo $current_page === 'print.php' ? 'class="active"' : ''; ?>>Layanan Cetak</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="cart.php" <?php echo $current_page === 'cart.php' ? 'class="active"' : ''; ?>>Keranjang</a></li>
                <li><a href="orders.php" <?php echo $current_page === 'orders.php' ? 'class="active"' : ''; ?>>Pesanan Saya</a></li>
                <li><a href="logout.php">Keluar</a></li>
            <?php else: ?>
                <li><a href="login.php" <?php echo $current_page === 'login.php' ? 'class="active"' : ''; ?>>Masuk</a></li>
                <li><a href="register.php" <?php echo $current_page === 'register.php' ? 'class="active"' : ''; ?>>Daftar</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<style>
.navbar {
    background: var(--dark-bg);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 0 rgba(255,255,255,0.1);
}

.logo h1 {
    margin: 0;
    color: var(--text-light);
    font-size: 1.5rem;
}

.nav-links {
    display: flex;
    gap: 2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links a {
    color: var(--text-gray);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.nav-links a:hover,
.nav-links a.active {
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        padding: 1rem;
    }
    
    .nav-links {
        margin-top: 1rem;
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>