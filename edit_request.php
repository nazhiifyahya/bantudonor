<?php
session_start();
require_once 'models/BloodRequest.php';
require_once 'config/envloader.php';

// Set page variables for header template
$pageTitle = 'Edit Permohonan - BantuDonor';
$currentPage = 'edit_request';

$message = '';
$error = '';
$request = null;

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
            $message = 'Permohonan berhasil diperbarui!';
            // Reload request data
            $request = $bloodRequestModel->getByToken($token);
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
            <h1 class="text-slate-50 text-4xl font-bold text-center">Edit Permohonan Donor Darah</h1>
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
                            <option value="Darah Lengkap" <?php echo $request['donation_type'] == 'Darah Lengkap' ? 'selected' : ''; ?>>Darah Lengkap (Whole Blood)</option>
                            <option value="Trombosit" <?php echo $request['donation_type'] == 'Trombosit' ? 'selected' : ''; ?>>Trombosit (Platelet)</option>
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
                               placeholder="Contoh: +6281234567890"
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:outline-none focus:border-red-500">
                        <span class="text-slate-600 text-sm">Format: +62 atau 08xx</span>
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
