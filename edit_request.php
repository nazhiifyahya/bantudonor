<?php
session_start();
require_once 'models/BloodRequest.php';
require_once 'models/User.php';
require_once 'config/envloader.php';

// Set page variables for header template
$pageTitle = 'Detail Permohonan Donor Darah - BantuDonor';
$currentPage = 'edit_request';

$message = '';
$error = '';
$request = null;
$eligibleVolunteers = [];

// Get token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $error = 'Link tidak valid. Token tidak ditemukan.';
} else {
    $bloodRequestModel = new BloodRequest();
    $request = $bloodRequestModel->getByToken($token);
    
    if (!$request) {
        $error = 'Permohonan tidak ditemukan atau link sudah tidak valid.';
    } elseif ($request['status'] !== 'Active') {
        $error = 'Permohonan ini sudah tidak aktif dan tidak dapat diedit.';
        $request = null; // Prevent form from displaying
    } else {
        // Get eligible volunteers willing to share their phone
        $userModel = new User();
        $eligibleVolunteers = $userModel->getEligibleVolunteersWithPhone(
            $request['blood_type_abo'],
            $request['blood_type_rhesus'],
            $request['latitude'] ?? null,
            $request['longitude'] ?? null,
            20 // Maximum distance in kilometers
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request) {
    // Validate input
    $patientName = trim($_POST['patient_name']);
    $bloodBagsNeeded = intval($_POST['blood_bags_needed']);
    $donationType = $_POST['donation_type'];
    $contactPerson = trim($_POST['contact_person']);
    $contactPhone = trim($_POST['contact_phone']);
    $status = isset($_POST['status']) ? $_POST['status'] : 'Active';
    
    // Basic validation
    if (empty($patientName) || empty($bloodBagsNeeded) || empty($donationType) ||
        empty($contactPerson) || empty($contactPhone)) {
        $error = 'Semua field wajib diisi.';
    } elseif ($bloodBagsNeeded < 1 || $bloodBagsNeeded > 10) {
        $error = 'Jumlah kantong darah harus antara 1-10.';
    } elseif (!in_array($status, ['Active', 'Cancelled', 'Fulfilled'])) {
        $error = 'Status tidak valid.';
    } else {
        // Update blood request (only editable fields)
        $updateData = [
            'patient_name' => $patientName,
            'blood_bags_needed' => $bloodBagsNeeded,
            'donation_type' => $donationType,
            'contact_person' => $contactPerson,
            'contact_phone' => $contactPhone,
            'status' => $status
        ];
        
        $updated = $bloodRequestModel->update($request['id'], $updateData);
        if ($updated) {
            // Reload request data
            $request = $bloodRequestModel->getByToken($token);
            
            // Check if status is now inactive
            if ($request['status'] !== 'Active') {
                $message = 'Permohonan berhasil diperbarui! Status telah diubah menjadi ' . $request['status'] . '.';
                $request = null; // Prevent form from displaying again
            } else {
                $message = 'Permohonan berhasil diperbarui!';
                // Reload eligible volunteers since data might have changed
                $userModel = new User();
                $eligibleVolunteers = $userModel->getEligibleVolunteersWithPhone(
                    $request['blood_type_abo'],
                    $request['blood_type_rhesus'],
                    $request['latitude'] ?? null,
                    $request['longitude'] ?? null,
                    20 // Maximum distance in kilometers
                );
            }
        } else {
            $error = 'Terjadi kesalahan saat memperbarui permohonan. Silakan coba lagi.';
        }
    }
}

// Include header template
include 'layout/header.php';
?>

    <!-- Page Header -->
    <section class="bg-red-500">
        <div class="w-full px-4 sm:px-6 lg:px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] mx-auto">
            <h1 class="text-slate-50 text-4xl font-bold text-center">Detail Permohonan Donor Darah</h1>
            <p class="text-red-50 text-center max-w-2xl">Kelola detail permohonan Anda dan lihat relawan yang tersedia di sekitar lokasi</p>
        </div>
    </section>

    <!-- Edit Form -->
    <section class="bg-slate-50">
        <div class="w-full px-4 sm:px-6 lg:px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto">
            
            <?php if ($error): ?>
                <div class="w-full max-w-4xl p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="w-full max-w-4xl p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($request && !empty($eligibleVolunteers)): ?>
            <!-- Eligible Volunteers Section -->
            <div class="w-full max-w-4xl bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-red-500 px-6 py-4">
                    <h2 class="text-white text-xl font-bold flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Relawan Siap Dihubungi (<?php echo count($eligibleVolunteers); ?>)
                    </h2>
                    <p class="text-red-50 text-sm mt-1">
                        Relawan dengan golongan darah <?php echo htmlspecialchars($request['blood_type_abo'] . $request['blood_type_rhesus']); ?> 
                        dalam radius 20 km yang bersedia membagikan nomor mereka
                    </p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($eligibleVolunteers as $volunteer): ?>
                        <div class="border border-slate-200 rounded-lg p-4 hover:border-red-300 hover:bg-red-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($volunteer['full_name']); ?></h3>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-slate-600 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                            </svg>
                                            <a href="https://wa.me/<?php echo htmlspecialchars($volunteer['phone']); ?>" 
                                               target="_blank"
                                               class="text-green-600 hover:text-green-700 font-medium">
                                                <?php echo htmlspecialchars($volunteer['phone']); ?>
                                            </a>
                                        </p>
                                        <p class="text-sm text-slate-600 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <?php echo htmlspecialchars($volunteer['city']); ?>, <?php echo htmlspecialchars($volunteer['province']); ?>
                                        </p>
                                        <?php if (isset($volunteer['distance'])): ?>
                                        <p class="text-sm text-slate-600 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <span class="font-medium text-blue-600">~<?php echo number_format($volunteer['distance'], 1); ?> km</span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <?php echo htmlspecialchars($volunteer['blood_type_abo'] . $volunteer['blood_type_rhesus']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <strong>Tips:</strong> Klik nomor WhatsApp untuk langsung menghubungi relawan melalui WhatsApp
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($request): ?>
            <form method="POST" class="w-full max-w-4xl bg-white rounded-lg px-6 py-8 space-y-6 shadow-md">
                <!-- Patient Information Section -->
                <h2 class="w-full text-gray-900 text-2xl font-bold mb-4">Informasi Pasien</h2>
                <div class="w-full h-0 border-t border-slate-200 mb-6"></div>
                
                <div class="w-full flex flex-col gap-4 mb-8">
                    <!-- Patient Name -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Nama Pasien <span class="text-red-500">*</span></label>
                        <input type="text" name="patient_name" value="<?php echo htmlspecialchars($request['patient_name']); ?>" required
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                    </div>
                </div>

                <!-- Hospital Information Section -->
                <h2 class="w-full text-gray-900 text-2xl font-bold mb-4">Informasi Rumah Sakit</h2>
                <div class="w-full h-0 border-t border-slate-200 mb-6"></div>
                
                <div class="w-full flex flex-col gap-4 mb-8">
                    <!-- Hospital Name (Read-only) -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Nama Rumah Sakit</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['hospital_name']); ?>" readonly
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600">
                        <span class="text-slate-500 text-sm">Lokasi rumah sakit tidak dapat diubah</span>
                    </div>
                    
                    <!-- Hospital Address -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Alamat Rumah Sakit</label>
                        <textarea readonly class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600 h-24"><?php echo htmlspecialchars($request['hospital_address']); ?></textarea>
                    </div>
                    
                    <!-- City -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Kota/Kabupaten</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['city']); ?>" readonly
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600">
                    </div>
                    
                    <!-- Province -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Provinsi</label>
                        <input type="text" value="<?php echo htmlspecialchars($request['province']); ?>" readonly
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600">
                    </div>
                </div>

                <!-- Blood Information Section -->
                <h2 class="w-full text-gray-900 text-2xl font-bold mb-4">Informasi Darah</h2>
                <div class="w-full h-0 border-t border-slate-200 mb-6"></div>
                
                <div class="w-full flex flex-col gap-4 mb-8">
                    <!-- Blood Type (Read-only) -->
                    <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-gray-900 text-base font-medium">Golongan Darah (ABO)</label>
                            <input type="text" value="<?php echo htmlspecialchars($request['blood_type_abo']); ?>" readonly
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600">
                        </div>
                        
                        <div class="flex flex-col gap-2">
                            <label class="text-gray-900 text-base font-medium">Rhesus</label>
                            <input type="text" value="<?php echo htmlspecialchars($request['blood_type_rhesus']); ?>" readonly
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-slate-100 text-slate-600">
                        </div>
                    </div>
                    <span class="text-slate-500 text-sm">Golongan darah tidak dapat diubah</span>
                    
                    <!-- Blood Bags Needed -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Jumlah Kantong Darah Dibutuhkan <span class="text-red-500">*</span></label>
                        <input type="number" name="blood_bags_needed" value="<?php echo htmlspecialchars($request['blood_bags_needed']); ?>" min="1" max="10" required
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                        <span class="text-slate-600 text-sm">Masukkan jumlah antara 1-10 kantong</span>
                    </div>
                    
                    <!-- Donation Type -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Jenis Donor <span class="text-red-500">*</span></label>
                        <select name="donation_type" required
                                class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                            <option value="">Pilih Jenis Donor</option>
                            <option value="Whole Blood" <?php echo $request['donation_type'] == 'Whole Blood' ? 'selected' : ''; ?>>Whole Blood</option>
                            <option value="Red Blood Cells" <?php echo $request['donation_type'] == 'Red Blood Cells' ? 'selected' : ''; ?>>Red Blood Cells</option>
                            <option value="Platelets" <?php echo $request['donation_type'] == 'Platelets' ? 'selected' : ''; ?>>Platelets</option>
                            <option value="Plasma" <?php echo $request['donation_type'] == 'Plasma' ? 'selected' : ''; ?>>Plasma</option>
                        </select>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <h2 class="w-full text-gray-900 text-2xl font-bold mb-4">Informasi Narahubung</h2>
                <div class="w-full h-0 border-t border-slate-200 mb-6"></div>
                
                <div class="w-full flex flex-col gap-4 mb-8">
                    <!-- Contact Person -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Nama Narahubung <span class="text-red-500">*</span></label>
                        <input type="text" name="contact_person" value="<?php echo htmlspecialchars($request['contact_person']); ?>" required
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                    </div>
                    
                    <!-- Contact Phone -->
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Nomor WhatsApp <span class="text-red-500">*</span></label>
                        <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($request['contact_phone']); ?>" required
                               placeholder="Contoh: 081234567890"
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                    </div>
                </div>

                <!-- Status Section -->
                <h2 class="w-full text-gray-900 text-2xl font-bold mb-4">Status Permohonan</h2>
                <div class="w-full h-0 border-t border-slate-200 mb-6"></div>
                
                <div class="w-full flex flex-col gap-4 mb-8">
                    <div class="w-full flex flex-col gap-2">
                        <label class="text-gray-900 text-base font-medium">Status Saat Ini: 
                            <span class="font-semibold <?php 
                                echo $request['status'] == 'Active' ? 'text-green-600' : 
                                     ($request['status'] == 'Fulfilled' ? 'text-blue-600' : 'text-red-600'); 
                            ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </label>
                    </div>
                    
                    <div class="w-full flex flex-col gap-3 bg-slate-50 p-4 rounded-lg">
                        <label class="text-gray-900 text-base font-medium">Ubah Status</label>
                        
                        <div class="flex flex-col gap-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="status" value="Active" 
                                       <?php echo $request['status'] == 'Active' ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-green-600 focus:ring-green-500">
                                <span class="text-gray-900">
                                    <span class="font-semibold text-green-600">Aktif</span> - Permohonan masih membutuhkan donor
                                </span>
                            </label>
                            
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="status" value="Fulfilled" 
                                       <?php echo $request['status'] == 'Fulfilled' ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                <span class="text-gray-900">
                                    <span class="font-semibold text-blue-600">Terpenuhi</span> - Kebutuhan donor sudah terpenuhi
                                </span>
                            </label>
                            
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="status" value="Cancelled" 
                                       <?php echo $request['status'] == 'Cancelled' ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-red-600 focus:ring-red-500">
                                <span class="text-gray-900">
                                    <span class="font-semibold text-red-600">Dibatalkan</span> - Permohonan dibatalkan
                                </span>
                            </label>
                        </div>
                        
                        <p class="text-slate-600 text-sm mt-2">
                            <strong>Catatan:</strong> Permohonan yang berstatus "Terpenuhi" atau "Dibatalkan" tidak akan ditampilkan di daftar kebutuhan darah publik.
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="w-full flex justify-end gap-4">
                    <a href="blood_requests.php" class="px-8 py-3 rounded-full border border-red-500 text-red-500 text-base font-semibold hover:bg-red-50">
                        Batal
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-full bg-red-500 text-white text-base font-semibold hover:bg-red-600">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </section>

<?php include 'layout/footer.php'; ?>

<script>
    // No hospital search functionality needed since it's read-only
</script>
