<?php
require_once 'config/database.php';

/**
 * Mendapatkan ID keranjang aktif untuk user
 */
function getUserCartId($user_id) {
    try {
        $conn = getDBConnection();
        
        // Cek apakah user sudah punya keranjang
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cart) {
            return $cart['id'];
        } else {
            // Buat keranjang baru jika belum ada
            $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            return $conn->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Menambah produk ke keranjang
 */
function addToCart($user_id, $product_id, $quantity = 1) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return false;
        }
        
        // Cek apakah produk sudah ada di keranjang
        $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cart_id, $product_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            // Update quantity jika produk sudah ada
            $new_qty = $item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_qty, $item['id']]);
        } else {
            // Tambah item baru jika produk belum ada
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$cart_id, $product_id, $quantity]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Mengupdate quantity produk di keranjang
 */
function updateCartItemQuantity($user_id, $product_id, $quantity) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return false;
        }
        
        if ($quantity <= 0) {
            // Hapus item jika quantity 0 atau kurang
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $stmt->execute([$cart_id, $product_id]);
        } else {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $cart_id, $product_id]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Menghapus produk dari keranjang
 */
function removeFromCart($user_id, $product_id) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return false;
        }
        
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cart_id, $product_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Mendapatkan semua item di keranjang
 */
function getCartItems($user_id) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return [];
        }
        
        $stmt = $conn->prepare("
            SELECT ci.*, p.name, p.price, p.image_url, p.stock
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cart_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Menghitung total harga keranjang
 */
function getCartTotal($user_id) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return 0;
        }
        
        $stmt = $conn->prepare("
            SELECT SUM(ci.quantity * p.price) as total
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Menghapus semua item di keranjang
 */
function clearCart($user_id) {
    try {
        $conn = getDBConnection();
        $cart_id = getUserCartId($user_id);
        
        if (!$cart_id) {
            return false;
        }
        
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
} 