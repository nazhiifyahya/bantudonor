<?php
session_start();
require_once 'models/User.php';
require_once 'models/BloodRequest.php';
require_once 'models/Donation.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Admin Dashboard - BantuDonor';
$currentPage = 'admin_dashboard';

$userModel = new User();
$bloodRequestModel = new BloodRequest();
$donationModel = new Donation();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_user') {
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        if ($userId <= 0) {
            $errorMessage = 'ID user tidak valid.';
        } else {
            $updateData = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'blood_type_abo' => $_POST['blood_type_abo'] ?? '',
                'blood_type_rhesus' => $_POST['blood_type_rhesus'] ?? '',
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'province' => trim($_POST['province'] ?? ''),
                'share_phone' => ($_POST['share_phone'] ?? 'tidak') === 'ya' ? 'ya' : 'tidak',
                'whatsapp_notification' => ($_POST['whatsapp_notification'] ?? 'tidak') === 'ya' ? 'ya' : 'tidak',
                'is_active' => ($_POST['is_active'] ?? '1') === '1' ? 1 : 0,
                'telegram_chat_id' => trim($_POST['telegram_chat_id'] ?? ''),
                'unique_token' => trim($_POST['unique_token'] ?? ''),
                'last_donation_date' => !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : null
            ];

            if (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
                $updateData['location'] = $userModel->coordinatesToPoint((float) $_POST['latitude'], (float) $_POST['longitude']);
            }

            $updated = $userModel->update($userId, $updateData);
            if ($updated) {
                $successMessage = 'Data user berhasil diperbarui.';
            } else {
                $errorMessage = 'Gagal memperbarui data user.';
            }
        }
    }

    if ($action === 'update_donation') {
        $donationId = isset($_POST['donation_id']) ? (int) $_POST['donation_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        if ($donationId <= 0 || $userId <= 0) {
            $errorMessage = 'Data donasi tidak valid.';
        } else {
            $donation = $donationModel->getById($donationId);
            if (!$donation || (int) $donation['user_id'] !== $userId) {
                $errorMessage = 'Donasi tidak ditemukan.';
            } else {
                $updateData = [
                    'donation_type' => trim($_POST['donation_type'] ?? ''),
                    'location' => trim($_POST['location'] ?? ''),
                    'donation_date' => trim($_POST['donation_date'] ?? '')
                ];

                $updated = $donationModel->update($donationId, $updateData);
                if ($updated) {
                    $successMessage = 'Riwayat donasi berhasil diperbarui.';
                } else {
                    $errorMessage = 'Gagal memperbarui riwayat donasi.';
                }
            }
        }
    }

    if ($action === 'update_request') {
        $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;

        if ($requestId <= 0) {
            $errorMessage = 'ID permohonan tidak valid.';
        } else {
            $updateData = [
                'unique_token' => trim($_POST['unique_token'] ?? ''),
                'patient_name' => trim($_POST['patient_name'] ?? ''),
                'hospital_name' => trim($_POST['hospital_name'] ?? ''),
                'hospital_address' => trim($_POST['hospital_address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'province' => trim($_POST['province'] ?? ''),
                'blood_type_abo' => $_POST['blood_type_abo'] ?? '',
                'blood_type_rhesus' => $_POST['blood_type_rhesus'] ?? '',
                'blood_bags_needed' => isset($_POST['blood_bags_needed']) ? (int) $_POST['blood_bags_needed'] : 0,
                'donation_type' => trim($_POST['donation_type'] ?? ''),
                'contact_person' => trim($_POST['contact_person'] ?? ''),
                'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'status' => trim($_POST['status'] ?? 'Active')
            ];

            if (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
                $updateData['location'] = $bloodRequestModel->coordinatesToPoint((float) $_POST['latitude'], (float) $_POST['longitude']);
            }

            $updated = $bloodRequestModel->update($requestId, $updateData);
            if ($updated) {
                $successMessage = 'Data permohonan darah berhasil diperbarui.';
            } else {
                $errorMessage = 'Gagal memperbarui data permohonan darah.';
            }
        }
    }
}

$users = $userModel->getAllForAdmin();
$bloodRequests = $bloodRequestModel->getAllForAdmin();
$allDonations = $donationModel->getAll(null, null, 'donation_date DESC');

$donationsByUser = [];
foreach ($allDonations as $donation) {
    $donationsByUser[$donation['user_id']][] = $donation;
}

include 'layout/header.php';
?>

<section class="bg-red-500">
    <div class="w-full max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-20 py-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-slate-50 text-center">Admin Dashboard</h1>
        <p class="text-red-50 text-center mt-2">Kelola data user dan semua data permohonan darah</p>
    </div>
</section>

<section class="bg-slate-50">
    <div class="w-full max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-20 py-10 space-y-6">

        <?php if (!empty($successMessage)): ?>
            <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">User</h2>
                    <span class="text-sm text-slate-500"><?php echo count($users); ?> data</span>
                </div>
                <div class="max-h-[70vh] overflow-y-auto divide-y divide-slate-100">
                    <?php foreach ($users as $user): ?>
                        <div class="px-5 py-4 flex items-center justify-between gap-3">
                            <p class="text-sm text-gray-900 break-all"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="openModal('profile-modal-<?php echo (int) $user['id']; ?>')" class="px-3 py-2 text-xs sm:text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg">Profile</button>
                                <button type="button" onclick="openModal('donation-modal-<?php echo (int) $user['id']; ?>')" class="px-3 py-2 text-xs sm:text-sm bg-slate-700 hover:bg-slate-800 text-white rounded-lg">Donation</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Blood Request</h2>
                    <span class="text-sm text-slate-500"><?php echo count($bloodRequests); ?> data</span>
                </div>
                <div class="max-h-[70vh] overflow-y-auto divide-y divide-slate-100">
                    <?php foreach ($bloodRequests as $request): ?>
                        <div class="px-5 py-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($request['request_code']); ?></p>
                                <p class="text-sm text-slate-700 truncate"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                                <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($request['city']); ?></p>
                            </div>
                            <button type="button" onclick="openModal('request-modal-<?php echo (int) $request['id']; ?>')" class="px-3 py-2 text-xs sm:text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg shrink-0">Edit</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php foreach ($users as $user): ?>
            <div id="profile-modal-<?php echo (int) $user['id']; ?>" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
                <div class="mx-auto mt-6 sm:mt-10 w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg shadow-lg">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Profile User</h3>
                        <button type="button" onclick="closeModal('profile-modal-<?php echo (int) $user['id']; ?>')" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" class="p-5 space-y-4">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">

                        <div>
                            <label class="text-sm text-slate-600">Email (read-only)</label>
                            <input type="text" readonly value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 bg-slate-100 rounded-lg text-slate-600">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm text-slate-600">Nama Lengkap</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Phone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">ABO</label>
                                <select name="blood_type_abo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <?php foreach (['A', 'B', 'AB', 'O'] as $abo): ?>
                                        <option value="<?php echo $abo; ?>" <?php echo ($user['blood_type_abo'] ?? '') === $abo ? 'selected' : ''; ?>><?php echo $abo; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Rhesus</label>
                                <select name="blood_type_rhesus" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <option value="+" <?php echo ($user['blood_type_rhesus'] ?? '') === '+' ? 'selected' : ''; ?>>+</option>
                                    <option value="-" <?php echo ($user['blood_type_rhesus'] ?? '') === '-' ? 'selected' : ''; ?>>-</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Share Phone</label>
                                <select name="share_phone" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <option value="ya" <?php echo ($user['share_phone'] ?? '') === 'ya' ? 'selected' : ''; ?>>ya</option>
                                    <option value="tidak" <?php echo ($user['share_phone'] ?? '') === 'tidak' ? 'selected' : ''; ?>>tidak</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">WhatsApp Notification</label>
                                <select name="whatsapp_notification" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <option value="ya" <?php echo ($user['whatsapp_notification'] ?? '') === 'ya' ? 'selected' : ''; ?>>ya</option>
                                    <option value="tidak" <?php echo ($user['whatsapp_notification'] ?? '') === 'tidak' ? 'selected' : ''; ?>>tidak</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Is Active</label>
                                <select name="is_active" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <option value="1" <?php echo ((string) ($user['is_active'] ?? '0')) === '1' ? 'selected' : ''; ?>>1</option>
                                    <option value="0" <?php echo ((string) ($user['is_active'] ?? '0')) === '0' ? 'selected' : ''; ?>>0</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Telegram Chat ID</label>
                                <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($user['telegram_chat_id'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Last Donation Date</label>
                                <input type="date" name="last_donation_date" value="<?php echo !empty($user['last_donation_date']) ? htmlspecialchars(date('Y-m-d', strtotime($user['last_donation_date']))) : ''; ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Kota</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Provinsi</label>
                                <input type="text" name="province" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Latitude</label>
                                <input type="number" step="any" name="latitude" value="<?php echo htmlspecialchars((string) ($user['latitude'] ?? '')); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Longitude</label>
                                <input type="number" step="any" name="longitude" value="<?php echo htmlspecialchars((string) ($user['longitude'] ?? '')); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Unique Token</label>
                                <input type="text" name="unique_token" value="<?php echo htmlspecialchars($user['unique_token'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Alamat</label>
                                <textarea name="address" rows="2" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="closeModal('profile-modal-<?php echo (int) $user['id']; ?>')" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="donation-modal-<?php echo (int) $user['id']; ?>" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
                <div class="mx-auto mt-6 sm:mt-10 w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg shadow-lg">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Donation History</h3>
                        <button type="button" onclick="closeModal('donation-modal-<?php echo (int) $user['id']; ?>')" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>

                    <div class="p-5 space-y-4">
                        <p class="text-sm text-slate-600 break-all"><?php echo htmlspecialchars($user['email']); ?></p>

                        <?php if (!empty($donationsByUser[$user['id']])): ?>
                            <?php foreach ($donationsByUser[$user['id']] as $donation): ?>
                                <form method="POST" class="border border-slate-200 rounded-lg p-4 space-y-3">
                                    <input type="hidden" name="action" value="update_donation">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                    <input type="hidden" name="donation_id" value="<?php echo (int) $donation['id']; ?>">

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="text-sm text-slate-600">Donation Type</label>
                                            <input type="text" name="donation_type" value="<?php echo htmlspecialchars($donation['donation_type'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                        </div>
                                        <div>
                                            <label class="text-sm text-slate-600">Location</label>
                                            <input type="text" name="location" value="<?php echo htmlspecialchars($donation['location'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                        </div>
                                        <div>
                                            <label class="text-sm text-slate-600">Donation Date</label>
                                            <input type="date" name="donation_date" value="<?php echo !empty($donation['donation_date']) ? htmlspecialchars(date('Y-m-d', strtotime($donation['donation_date']))) : ''; ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm">Simpan Perubahan</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-500">User ini belum memiliki riwayat donasi.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php foreach ($bloodRequests as $request): ?>
            <div id="request-modal-<?php echo (int) $request['id']; ?>" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
                <div class="mx-auto mt-6 sm:mt-10 w-full max-w-4xl max-h-[90vh] overflow-y-auto bg-white rounded-lg shadow-lg">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Edit Blood Request</h3>
                        <button type="button" onclick="closeModal('request-modal-<?php echo (int) $request['id']; ?>')" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>
                    <form method="POST" class="p-5 space-y-4">
                        <input type="hidden" name="action" value="update_request">
                        <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">

                        <div>
                            <label class="text-sm text-slate-600">Request Code (read-only)</label>
                            <input type="text" readonly value="<?php echo htmlspecialchars($request['request_code'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 bg-slate-100 rounded-lg text-slate-600">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Patient Name</label>
                                <input type="text" name="patient_name" value="<?php echo htmlspecialchars($request['patient_name'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Hospital Name</label>
                                <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($request['hospital_name'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Hospital Address</label>
                                <textarea name="hospital_address" rows="2" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg"><?php echo htmlspecialchars($request['hospital_address'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">City/Kabupaten</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($request['city'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Province</label>
                                <input type="text" name="province" value="<?php echo htmlspecialchars($request['province'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">ABO</label>
                                <select name="blood_type_abo" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <?php foreach (['A', 'B', 'AB', 'O'] as $abo): ?>
                                        <option value="<?php echo $abo; ?>" <?php echo ($request['blood_type_abo'] ?? '') === $abo ? 'selected' : ''; ?>><?php echo $abo; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Rhesus</label>
                                <select name="blood_type_rhesus" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <option value="+" <?php echo ($request['blood_type_rhesus'] ?? '') === '+' ? 'selected' : ''; ?>>+</option>
                                    <option value="-" <?php echo ($request['blood_type_rhesus'] ?? '') === '-' ? 'selected' : ''; ?>>-</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Blood Bags Needed</label>
                                <input type="number" min="1" max="50" name="blood_bags_needed" value="<?php echo htmlspecialchars((string) ($request['blood_bags_needed'] ?? '1')); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Donation Type</label>
                                <input type="text" name="donation_type" value="<?php echo htmlspecialchars($request['donation_type'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Contact Person</label>
                                <input type="text" name="contact_person" value="<?php echo htmlspecialchars($request['contact_person'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Contact Phone</label>
                                <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($request['contact_phone'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Contact Email</label>
                                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($request['contact_email'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Status</label>
                                <select name="status" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                                    <?php foreach (['Active', 'Fulfilled', 'Cancelled'] as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($request['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Latitude</label>
                                <input type="number" step="any" name="latitude" value="<?php echo htmlspecialchars((string) ($request['latitude'] ?? '')); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label class="text-sm text-slate-600">Longitude</label>
                                <input type="number" step="any" name="longitude" value="<?php echo htmlspecialchars((string) ($request['longitude'] ?? '')); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-slate-600">Unique Token</label>
                                <input type="text" name="unique_token" value="<?php echo htmlspecialchars($request['unique_token'] ?? ''); ?>" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="closeModal('request-modal-<?php echo (int) $request['id']; ?>')" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('click', function(event) {
        const target = event.target;
        if (target.classList.contains('bg-black/50')) {
            target.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
</script>

<?php include 'layout/footer.php'; ?>
