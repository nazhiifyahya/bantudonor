<?php
session_start();
require_once __DIR__ . '/../models/Donation.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$donationModel = new Donation();
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            // Validate input
            $donationType = $_POST['donation_type'] ?? '';
            $location = $_POST['location'] ?? '';
            $donationDate = $_POST['donation_date'] ?? date('Y-m-d');
            
            if (empty($donationType) || empty($location) || empty($donationDate)) {
                $response = ['status' => 'error', 'message' => 'Semua field wajib diisi'];
                break;
            }

            try {
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'donation_type' => $donationType,
                    'location' => $location,
                    'donation_date' => $donationDate,
                ];
                
                // Debug: log the data being inserted
                error_log('Attempting to create donation with data: ' . json_encode($data));
                
                $result = $donationModel->create($data);
                
                if ($result) {
                    $response = ['status' => 'success', 'message' => 'Riwayat donasi berhasil ditambahkan', 'id' => $result];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal menambahkan riwayat donasi'];
                }
            } catch (Exception $e) {
                error_log('Donation creation error: ' . $e->getMessage());
                $response = ['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
            }
            break;
            
        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $donationType = $_POST['donation_type'] ?? '';
            $location = $_POST['location'] ?? '';
            $donationDate = $_POST['donation_date'] ?? date('Y-m-d');
            
            if ($id <= 0 || empty($donationType) || empty($location) || empty($donationDate)) {
                $response = ['status' => 'error', 'message' => 'Data tidak valid'];
                break;
            }

            try {
                // Check if donation exist and belongs to the user
                $donation = $donationModel->getById($id);
                if (!$donation || $donation['user_id'] != $_SESSION['user_id']) {
                    $response = ['status' => 'error', 'message' => 'Donasi tidak ditemukan atau tidak dapat diedit'];
                    break;
                }
                
                $data = [
                    'donation_type' => $donationType,
                    'location' => $location,
                    'donation_date' => $donationDate
                ];
                
                $result = $donationModel->update($id, $data);
                
                if ($result) {
                    $response = ['status' => 'success', 'message' => 'Riwayat donasi berhasil diupdate'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal mengupdate riwayat donasi'];
                }
            } catch (Exception $e) {
                error_log('Donation update error: ' . $e->getMessage());
                $response = ['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $response = ['status' => 'error', 'message' => 'ID tidak valid'];
                break;
            }
            
            try {
                // Check if donation exist and belongs to the user
                $donation = $donationModel->getById($id);
                if (!$donation || $donation['user_id'] != $_SESSION['user_id']) {
                    $response = ['status' => 'error', 'message' => 'Donasi tidak ditemukan atau tidak dapat dihapus'];
                    break;
                }
                
                $result = $donationModel->delete($id);
                
                if ($result) {
                    $response = ['status' => 'success', 'message' => 'Riwayat donasi berhasil dihapus'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Gagal menghapus riwayat donasi'];
                }
            } catch (Exception $e) {
                error_log('Donation delete error: ' . $e->getMessage());
                $response = ['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
            }
            break;
            
        case 'get':
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $response = ['status' => 'error', 'message' => 'ID tidak valid'];
                break;
            }
            
            try {
                $donation = $donationModel->getById($id);
                if ($donation && $donation['user_id'] == $_SESSION['user_id']) {
                    $response = ['status' => 'success', 'data' => $donation];
                } else {
                    $response = ['status' => 'error', 'message' => 'Donasi tidak ditemukan'];
                }
            } catch (Exception $e) {
                error_log('Donation get error: ' . $e->getMessage());
                $response = ['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
            }
            break;
    }
}

echo json_encode($response);
?>