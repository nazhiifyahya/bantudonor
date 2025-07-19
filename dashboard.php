<?php
session_start();
require_once 'models/User.php';
require_once 'models/Donation.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set page variables for header template
$pageTitle = 'Dashboard - BantuDonor';
$currentPage = 'dashboard';

$userModel = new User();
$donationModel = new Donation();

$user = $userModel->getById($_SESSION['user_id']);
$userDonations = $donationModel->getDonationsByUser($_SESSION['user_id']);
$donationStats = $donationModel->getUserDonationStats($_SESSION['user_id']);

// Include header template
include 'layout/header.php';
?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mx-auto max-w-6xl my-4" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" onclick="this.parentElement.parentElement.style.display='none'">
                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
            </svg>
        </span>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_messages'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mx-auto max-w-6xl my-4" role="alert">
        <strong class="font-bold">Terjadi kesalahan:</strong>
        <ul class="mt-2">
            <?php foreach ($_SESSION['error_messages'] as $error): ?>
                <li class="ml-4">• <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
            <svg class="fill-current h-6 w-6 text-red-500" role="button" onclick="this.parentElement.parentElement.style.display='none'">
                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
            </svg>
        </span>
    </div>
    <?php unset($_SESSION['error_messages']); endif; ?>

    <!-- Page Header -->
    <section class="bg-red-500">
        <div class="w-full px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto">
            <h1 class="text-slate-50 text-4xl font-bold">Dashboard</h1>
        </div>
    </section>

    <!-- Dashboard Content -->
    <section class="bg-slate-50">
        <div class="w-full px-20 py-16 max-w-[1280px] relative mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- User Profile Card -->
            <div class="lg:col-span-1 p-6 bg-white rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-gray-900 text-xl font-bold">Profil Saya</h2>
                    <button onclick="openEditProfileModal()" 
                            class="px-3 py-1 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors">
                        Edit Profil
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="text-slate-600 text-sm">Nama:</span>
                        <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600 text-sm">Email:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600 text-sm">Golongan Darah:</span>
                        <p class="text-red-500 font-bold text-lg"><?php echo htmlspecialchars($user['blood_type_abo'] . $user['blood_type_rhesus']); ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600 text-sm">Phone:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    <div>
                        <span class="text-slate-600 text-sm">Lokasi:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($user['city'] . ', ' . $user['province']); ?></p>
                    </div>
                    <?php if (!empty($user['address'])): ?>
                    <div>
                        <span class="text-slate-600 text-sm">Alamat:</span>
                        <p class="text-gray-900 text-sm"><?php echo htmlspecialchars($user['address']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-6 bg-white rounded-lg shadow text-center">
                        <h3 class="text-2xl font-bold text-red-500"><?php echo $donationStats['total_donations']; ?></h3>
                        <p class="text-slate-600">Total Donasi</p>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow text-center">
                        <h3 class="text-2xl font-bold text-red-500"><?php echo $donationStats['total_blood_bags']; ?></h3>
                        <p class="text-slate-600">Kantong Darah</p>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow text-center">
                        <h3 class="text-2xl font-bold text-red-500">
                            <?php 
                            if ($donationStats['last_donation_date']) {
                                echo date('M Y', strtotime($donationStats['last_donation_date']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </h3>
                        <p class="text-slate-600">Donasi Terakhir</p>
                    </div>
                </div>

                <!-- Recent Donations -->
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-gray-900 text-xl font-bold">Riwayat Donasi</h2>
                        <button onclick="openAddDonationModal()" 
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                            Tambah Riwayat
                        </button>
                    </div>
                    <?php if (!empty($userDonations)): ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($userDonations, 0, 5) as $donation): ?>
                            <div class="border-l-4 border-red-500 pl-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        
                                        <p class="text-slate-600"><?php echo htmlspecialchars($donation['location']); ?></p>
                                        
                                        <p class="text-sm text-slate-500">
                                            <?php echo date('d M Y', strtotime($donation['donation_date'])); ?> - 
                                            <?php echo $donation['blood_bags']; ?> kantong - 
                                            <?php echo htmlspecialchars($donation['donation_type']); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex gap-1">
                                            <button onclick="editDonation(<?php echo $donation['id']; ?>)" 
                                                    class="p-1 text-blue-600 hover:text-blue-800 transition-colors"
                                                    title="Edit">
                                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L7.707 12l-4 1 1-4L12.146.146zM11.207 2.5L13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175l-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                                </svg>
                                            </button>
                                            <button onclick="deleteDonation(<?php echo $donation['id']; ?>)" 
                                                    class="p-1 text-red-600 hover:text-red-800 transition-colors"
                                                    title="Hapus">
                                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($userDonations) > 5): ?>
                        <div class="mt-4 text-center">
                            <p class="text-sm text-slate-600">Menampilkan 5 dari <?php echo count($userDonations); ?> riwayat donasi</p>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-slate-600">Belum ada riwayat donasi.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </section>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Edit Profil</h2>
                        <button onclick="closeEditProfileModal()" 
                                class="text-gray-400 hover:text-gray-600 text-2xl">
                            &times;
                        </button>
                    </div>
                    
                    <form id="editProfileForm" method="POST" action="update_profile.php">
                        <!-- Full Name -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Nama Lengkap</label>
                            <input type="text" 
                                   name="full_name" 
                                   id="edit_full_name"
                                   required
                                   value="<?php echo isset($_SESSION['form_data']['full_name']) ? htmlspecialchars($_SESSION['form_data']['full_name']) : htmlspecialchars($user['full_name']); ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Email</label>
                            <input type="email" 
                                   name="email" 
                                   id="edit_email"
                                   required
                                   value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : htmlspecialchars($user['email']); ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Blood Type -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Golongan Darah</label>
                            <div class="flex gap-2">
                                <select name="blood_type_abo" 
                                        required
                                        class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                                    <option value="">Pilih ABO</option>
                                    <option value="A" <?php echo (isset($_SESSION['form_data']['blood_type_abo']) ? ($_SESSION['form_data']['blood_type_abo'] === 'A') : ($user['blood_type_abo'] === 'A')) ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo (isset($_SESSION['form_data']['blood_type_abo']) ? ($_SESSION['form_data']['blood_type_abo'] === 'B') : ($user['blood_type_abo'] === 'B')) ? 'selected' : ''; ?>>B</option>
                                    <option value="AB" <?php echo (isset($_SESSION['form_data']['blood_type_abo']) ? ($_SESSION['form_data']['blood_type_abo'] === 'AB') : ($user['blood_type_abo'] === 'AB')) ? 'selected' : ''; ?>>AB</option>
                                    <option value="O" <?php echo (isset($_SESSION['form_data']['blood_type_abo']) ? ($_SESSION['form_data']['blood_type_abo'] === 'O') : ($user['blood_type_abo'] === 'O')) ? 'selected' : ''; ?>>O</option>
                                </select>
                                
                                <select name="blood_type_rhesus" 
                                        required
                                        class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                                    <option value="">Pilih Rhesus</option>
                                    <option value="+" <?php echo (isset($_SESSION['form_data']['blood_type_rhesus']) ? ($_SESSION['form_data']['blood_type_rhesus'] === '+') : ($user['blood_type_rhesus'] === '+')) ? 'selected' : ''; ?>>Positif (+)</option>
                                    <option value="-" <?php echo (isset($_SESSION['form_data']['blood_type_rhesus']) ? ($_SESSION['form_data']['blood_type_rhesus'] === '-') : ($user['blood_type_rhesus'] === '-')) ? 'selected' : ''; ?>>Negatif (-)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Nomor WhatsApp</label>
                            <input type="tel" 
                                   name="phone" 
                                   id="edit_phone"
                                   required
                                   value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : htmlspecialchars($user['phone']); ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Location Section -->
                        <div class="mb-6">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Domisili</label>
                            
                            <!-- Map Controls -->
                            <div class="flex gap-2 mb-3">
                                <button type="button" id="edit-use-current-location" 
                                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                    Gunakan Lokasi Saya
                                </button>
                                <button type="button" id="edit-toggle-map" 
                                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                    Pilih di Peta
                                </button>
                            </div>
                            
                            <!-- Map Container -->
                            <div id="edit-map-container" class="hidden mb-3">
                                <div id="edit-map" class="w-full h-60 rounded-lg border border-slate-300"></div>
                                <p class="text-sm text-slate-600 mt-2">Klik pada peta untuk memilih lokasi Anda</p>
                            </div>
                            
                            <!-- Coordinate Display -->
                            <div id="edit-location-info" class="mb-3 <?php echo (empty($user['latitude']) && !isset($_SESSION['form_data']['latitude'])) ? 'hidden' : ''; ?>">
                                <div class="p-3 bg-blue-50 rounded-lg">
                                    <p class="text-sm text-slate-700">
                                        <strong>Koordinat:</strong> <span id="edit-coordinates">
                                            <?php 
                                            $lat = isset($_SESSION['form_data']['latitude']) ? $_SESSION['form_data']['latitude'] : ($user['latitude'] ?? '');
                                            $lon = isset($_SESSION['form_data']['longitude']) ? $_SESSION['form_data']['longitude'] : ($user['longitude'] ?? '');
                                            echo (!empty($lat) && !empty($lon)) ? $lat . ', ' . $lon : ''; 
                                            ?>
                                        </span>
                                    </p>
                                    <p class="text-sm text-slate-700">
                                        <strong>Status:</strong> <span id="edit-location-status">
                                            <?php echo (!empty($lat) && !empty($lon)) ? 'Lokasi tersimpan' : 'Lokasi belum dipilih'; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Hidden inputs for coordinates -->
                            <input type="hidden" id="edit-latitude" name="latitude" value="<?php echo isset($_SESSION['form_data']['latitude']) ? htmlspecialchars($_SESSION['form_data']['latitude']) : htmlspecialchars($user['latitude'] ?? ''); ?>">
                            <input type="hidden" id="edit-longitude" name="longitude" value="<?php echo isset($_SESSION['form_data']['longitude']) ? htmlspecialchars($_SESSION['form_data']['longitude']) : htmlspecialchars($user['longitude'] ?? ''); ?>">
                            
                            <!-- Address Field (Read-only) -->
                            <div class="mb-3">
                                <label class="block text-slate-600 text-sm font-medium mb-2">Alamat</label>
                                <input type="text" 
                                       id="edit-address-input"
                                       name="address" 
                                       readonly
                                       placeholder="Alamat akan terisi otomatis setelah memilih lokasi"
                                       value="<?php echo isset($_SESSION['form_data']['address']) ? htmlspecialchars($_SESSION['form_data']['address']) : htmlspecialchars($user['address'] ?? ''); ?>"
                                       class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                            </div>
                            
                            <!-- Province and City Fields (Read-only) -->
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-slate-600 text-sm font-medium mb-2">Provinsi</label>
                                    <input type="text" 
                                           id="edit-province-input"
                                           name="province" 
                                           readonly
                                           required
                                           placeholder="Akan terisi otomatis"
                                           value="<?php echo isset($_SESSION['form_data']['province']) ? htmlspecialchars($_SESSION['form_data']['province']) : htmlspecialchars($user['province']); ?>"
                                           class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                                </div>
                                
                                <div class="flex-1">
                                    <label class="block text-slate-600 text-sm font-medium mb-2">Kota/Kabupaten</label>
                                    <input type="text" 
                                           id="edit-city-input"
                                           name="city" 
                                           readonly
                                           required
                                           placeholder="Akan terisi otomatis"
                                           value="<?php echo isset($_SESSION['form_data']['city']) ? htmlspecialchars($_SESSION['form_data']['city']) : htmlspecialchars($user['city']); ?>"
                                           class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex gap-3 pt-4 border-t">
                            <button type="button" 
                                    onclick="closeEditProfileModal()"
                                    class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Donation Modal -->
    <div id="donationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 id="donationModalTitle" class="text-2xl font-bold text-gray-900">Tambah Riwayat Donasi</h2>
                        <button onclick="closeDonationModal()" 
                                class="text-gray-400 hover:text-gray-600 text-2xl">
                            &times;
                        </button>
                    </div>
                    
                    <form id="donationForm">
                        <input type="hidden" id="donationId" name="id">
                        <input type="hidden" id="donationAction" name="action" value="add">
                        
                        <!-- Tanggal Donasi -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Tanggal Donasi *</label>
                            <input type="date" 
                                   id="donationDate"
                                   name="donation_date" 
                                   required
                                   max="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Jenis Donor -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Jenis Donor *</label>
                            <select id="donationType" 
                                    name="donation_type" 
                                    required
                                    class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                                <option value="">Pilih Jenis Donor</option>
                                <option value="Whole Blood">Whole Blood (Darah Lengkap)</option>
                                <option value="Red Blood Cells">Red Blood Cells (Sel Darah Merah)</option>
                                <option value="Platelets">Platelets (Trombosit)</option>
                                <option value="Plasma">Plasma</option>
                            </select>
                        </div>
                        
                        <!-- Lokasi -->
                        <div class="mb-4">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Lokasi Donasi *</label>
                            <input type="text" 
                                   id="donationLocation"
                                   name="location" 
                                   required
                                   placeholder="Contoh: PMI Jakarta Pusat, RS Cipto Mangunkusumo"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Jumlah Kantong -->
                        <div class="mb-6">
                            <label class="block text-slate-600 text-sm font-medium mb-2">Jumlah Kantong</label>
                            <input type="number" 
                                   id="bloodBags"
                                   name="blood_bags" 
                                   min="1"
                                   max="10"
                                   value="1"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="flex gap-3">
                            <button type="button" 
                                    onclick="closeDonationModal()"
                                    class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    id="donationSubmitBtn"
                                    class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let editMap = null;
        let editMarker = null;
        
        // Modal functions
        function openEditProfileModal() {
            document.getElementById('editProfileModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditProfileModal() {
            document.getElementById('editProfileModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            
            // Clean up map if it exists
            if (editMap) {
                editMap.remove();
                editMap = null;
                editMarker = null;
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('editProfileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditProfileModal();
            }
        });
        
        // Auto-open modal if there are form errors
        <?php if (isset($_SESSION['error_messages']) && isset($_SESSION['form_data'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openEditProfileModal();
        });
        <?php endif; ?>
        
        // Map and location functionality
        document.addEventListener('DOMContentLoaded', function() {
            const editUseLocationBtn = document.getElementById('edit-use-current-location');
            const editToggleMapBtn = document.getElementById('edit-toggle-map');
            const editMapContainer = document.getElementById('edit-map-container');
            const editLocationInfo = document.getElementById('edit-location-info');
            const editCoordinatesSpan = document.getElementById('edit-coordinates');
            const editLocationStatus = document.getElementById('edit-location-status');
            const editLatInput = document.getElementById('edit-latitude');
            const editLonInput = document.getElementById('edit-longitude');
            const editAddressInput = document.getElementById('edit-address-input');
            const editProvinceInput = document.getElementById('edit-province-input');
            const editCityInput = document.getElementById('edit-city-input');
            
            // Toggle map visibility
            editToggleMapBtn.addEventListener('click', function() {
                if (editMapContainer.classList.contains('hidden')) {
                    editMapContainer.classList.remove('hidden');
                    editToggleMapBtn.textContent = 'Sembunyikan Peta';
                    editInitializeMap();
                } else {
                    editMapContainer.classList.add('hidden');
                    editToggleMapBtn.textContent = 'Pilih di Peta';
                }
            });
            
            // Use current location
            editUseLocationBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolocation tidak didukung oleh browser ini.');
                    return;
                }
                
                editUseLocationBtn.disabled = true;
                editUseLocationBtn.innerHTML = 'Mendapatkan lokasi...';
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        editSetLocationFromCoordinates(lat, lon);
                        
                        editUseLocationBtn.disabled = false;
                        editUseLocationBtn.innerHTML = `
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                            </svg>
                            Gunakan Lokasi Saya
                        `;
                    },
                    function(error) {
                        editUseLocationBtn.disabled = false;
                        editUseLocationBtn.innerHTML = `
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                            </svg>
                            Gunakan Lokasi Saya
                        `;
                        
                        let errorMessage = 'Gagal mendapatkan lokasi. ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Izin lokasi ditolak.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Informasi lokasi tidak tersedia.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Permintaan lokasi timeout.';
                                break;
                            default:
                                errorMessage += 'Terjadi kesalahan tidak dikenal.';
                                break;
                        }
                        alert(errorMessage);
                    }
                );
            });
            
            function editInitializeMap() {
                if (editMap) return; // Map already initialized
                
                // Get current coordinates or default to Indonesia center
                const currentLat = editLatInput.value ? parseFloat(editLatInput.value) : -2.5;
                const currentLon = editLonInput.value ? parseFloat(editLonInput.value) : 118;
                const zoomLevel = editLatInput.value ? 13 : 5;
                
                // Initialize map
                editMap = L.map('edit-map').setView([currentLat, currentLon], zoomLevel);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 18
                }).addTo(editMap);
                
                // Add existing marker if coordinates exist
                if (editLatInput.value && editLonInput.value) {
                    editMarker = L.marker([currentLat, currentLon]).addTo(editMap);
                }
                
                // Add click event to map
                editMap.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lon = e.latlng.lng;
                    editSetLocationFromCoordinates(lat, lon);
                });
            }
            
            function editSetLocationFromCoordinates(lat, lon) {
                // Update coordinates display
                editCoordinatesSpan.textContent = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                editLatInput.value = lat;
                editLonInput.value = lon;
                
                // Show location info
                editLocationInfo.classList.remove('hidden');
                editLocationStatus.textContent = 'Memuat informasi lokasi...';
                
                // Clear form fields while loading
                editAddressInput.value = 'Memuat alamat...';
                editProvinceInput.value = 'Memuat...';
                editCityInput.value = 'Memuat...';
                
                // Add/update marker on map if visible
                if (editMap) {
                    if (editMarker) {
                        editMap.removeLayer(editMarker);
                    }
                    editMarker = L.marker([lat, lon]).addTo(editMap);
                    editMap.setView([lat, lon], 13);
                }
                
                // Get address and administrative data
                fetch(`api/reverse_geocode.php?lat=${lat}&lon=${lon}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Fill form fields
                            editAddressInput.value = data.data.address;
                            editProvinceInput.value = data.data.province.name;
                            editCityInput.value = data.data.regency.name;
                            
                            editLocationStatus.textContent = 'Lokasi berhasil dipilih';
                        } else {
                            editLocationStatus.textContent = 'Gagal memuat informasi: ' + data.message;
                            editAddressInput.value = '';
                            editProvinceInput.value = '';
                            editCityInput.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        editLocationStatus.textContent = 'Terjadi kesalahan saat memuat informasi lokasi';
                        editAddressInput.value = '';
                        editProvinceInput.value = '';
                        editCityInput.value = '';
                    });
            }
        });
    </script>

    <script>
        // Donation management functions
        function openAddDonationModal() {
            document.getElementById('donationModalTitle').textContent = 'Tambah Riwayat Donasi';
            document.getElementById('donationAction').value = 'add';
            document.getElementById('donationId').value = '';
            document.getElementById('donationSubmitBtn').textContent = 'Simpan';
            
            // Reset form
            document.getElementById('donationForm').reset();
            document.getElementById('bloodBags').value = '1';
            
            document.getElementById('donationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeDonationModal() {
            document.getElementById('donationModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function editDonation(id) {
            // Get donation data
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('handler/manage_donations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Fill form with donation data
                    document.getElementById('donationModalTitle').textContent = 'Edit Riwayat Donasi';
                    document.getElementById('donationAction').value = 'edit';
                    document.getElementById('donationId').value = data.data.id;
                    document.getElementById('donationDate').value = data.data.donation_date;
                    document.getElementById('donationType').value = data.data.donation_type;
                    document.getElementById('donationLocation').value = data.data.location || '';
                    document.getElementById('bloodBags').value = data.data.blood_bags;
                    document.getElementById('donationSubmitBtn').textContent = 'Update';
                    
                    document.getElementById('donationModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat data donasi');
            });
        }
        
        function deleteDonation(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus riwayat donasi ini?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('handler/manage_donations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); // Reload page to update the list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus donasi');
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('donationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDonationModal();
            }
        });
        
        // Handle donation form submission
        document.getElementById('donationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('donationSubmitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
            
            const formData = new FormData(this);
            
            fetch('handler/manage_donations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    closeDonationModal();
                    location.reload(); // Reload page to update the list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan donasi');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    </script>

<?php 
// Clear form data session after displaying
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
include 'layout/footer.php'; 
?>