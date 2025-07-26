<?php
    session_start();
    require_once 'config/database.php';
    require_once 'models/User.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: dashboard.php');
        exit;
    }

    $userModel = new User();
    $errors = [];
    $success = false;

    try {
        // Validate required fields
        if (empty($_POST['full_name'])) {
            $errors[] = 'Nama lengkap harus diisi';
        }
        
        if (empty($_POST['email'])) {
            $errors[] = 'Email harus diisi';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }
        
        if (empty($_POST['blood_type_abo'])) {
            $errors[] = 'Golongan darah ABO harus dipilih';
        }
        
        if (empty($_POST['blood_type_rhesus'])) {
            $errors[] = 'Rhesus darah harus dipilih';
        }
        
        if (empty($_POST['phone'])) {
            $errors[] = 'Nomor WhatsApp harus diisi';
        }
        
        if (empty($_POST['province'])) {
            $errors[] = 'Provinsi harus dipilih';
        }
        
        if (empty($_POST['city'])) {
            $errors[] = 'Kota/Kabupaten harus dipilih';
        }
        
        if (empty($_POST['latitude']) || empty($_POST['longitude'])) {
            $errors[] = 'Lokasi harus dipilih di peta atau menggunakan GPS';
        }
        
        // Check if email is already used by another user
        if (empty($errors) && $_POST['email']) {
            $existingUser = $userModel->getByEmail($_POST['email']);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                $errors[] = 'Email sudah digunakan oleh pengguna lain';
            }
        }
        
        // If no errors, update the profile
        if (empty($errors)) {
            $updateData = [
                'id' => $_SESSION['user_id'],
                'full_name' => trim($_POST['full_name']),
                'email' => trim($_POST['email']),
                'blood_type_abo' => $_POST['blood_type_abo'],
                'blood_type_rhesus' => $_POST['blood_type_rhesus'],
                'location' => $userModel->coordinatesToPoint(
                    (float)$_POST['latitude'], 
                    (float)$_POST['longitude']
                ),
                'phone' => trim($_POST['phone']),
                'province' => trim($_POST['province']),
                'city' => trim($_POST['city']),
                'address' => trim($_POST['address'] ?? ''),
            ];
            
            if ($userModel->updateProfile($updateData)) {
                $_SESSION['success_message'] = 'Profil berhasil diperbarui!';
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Gagal memperbarui profil. Silakan coba lagi.';
            }
        }
        
    } catch (Exception $e) {
        error_log('Update profile error: ' . $e->getMessage());
        $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    }

    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }

    header('Location: dashboard.php');
    exit;
?>