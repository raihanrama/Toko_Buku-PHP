<?php
function validateImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengupload file'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF'];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => 'Ukuran file terlalu besar. Maksimal 5MB'];
    }
    
    return ['success' => true];
}

function processImage($file, $upload_dir) {
    $validation = validateImage($file);
    if (isset($validation['error'])) {
        return $validation;
    }
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath
        ];
    }
    
    return ['error' => 'Gagal menyimpan file'];
}

function deleteImage($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
} 