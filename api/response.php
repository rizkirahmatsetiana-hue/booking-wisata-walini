<?php
/**
 * Helper untuk JSON Response API
 * Sesuai SOP S2-03: Core API & Kontrak Data JSON
 */

// Set header JSON
function setJsonHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Response sukses
function successResponse($message, $data = null, $code = 200) {
    setJsonHeaders();
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Response error
function errorResponse($message, $errors = null, $code = 400) {
    setJsonHeaders();
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Cek login & role
function requireLogin($requiredRole = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['id_user'])) {
        errorResponse('Unauthorized. Silakan login terlebih dahulu.', null, 401);
    }
    
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        errorResponse('Forbidden. Anda tidak memiliki akses untuk fitur ini.', null, 403);
    }
    
    return $_SESSION;
}

// Generate kode booking unik
function generateKodeBooking() {
    return 'WS' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

// Generate kode e-tiket unik
function generateKodeEtiket() {
    return 'ET' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}
?>