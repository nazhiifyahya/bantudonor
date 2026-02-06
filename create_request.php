<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once 'models/BloodRequest.php';
require_once 'models/User.php';
require_once 'config/envloader.php';
require_once 'config/whatsapp.php';

use Telegram\Bot\Api;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set page variables for header template
$pageTitle = 'Ajukan Permohonan - BantuDonor';
$currentPage = 'create_request';

// Check if user is logged in and get their data for auto-fill
$loggedInUser = null;
if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $loggedInUser = $userModel->getUserById($_SESSION['user_id']);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bloodRequestModel = new BloodRequest();
    $userModel = new User();
    
    // Validate input
    $patientName = trim($_POST['patient_name']);
    $hospitalName = trim($_POST['hospital_name']);
    $hospitalAddress = trim($_POST['hospital_address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $latitude = isset($_POST['latitude']) && !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $bloodTypeAbo = $_POST['blood_type_abo'];
    $bloodTypeRhesus = $_POST['blood_type_rhesus'];
    $bloodBagsNeeded = intval($_POST['blood_bags_needed']);
    $donationType = $_POST['donation_type'];
    $contactPerson = trim($_POST['contact_person']);
    $contactPhone = trim($_POST['contact_phone']);
    $contactEmail = trim($_POST['contact_email']);
    
    // Basic validation
    if (empty($patientName) || empty($hospitalName) || empty($hospitalAddress) || 
        empty($city) || empty($province) || empty($bloodTypeAbo) || empty($bloodTypeRhesus) || 
        empty($bloodBagsNeeded) || empty($donationType) ||
        empty($contactPerson) || empty($contactPhone) || empty($contactEmail)) {
        $error = 'Semua field wajib diisi. Silakan cari dan pilih rumah sakit untuk melengkapi data lokasi.';
    } elseif (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } elseif ($bloodBagsNeeded < 1 || $bloodBagsNeeded > 10) {
        $error = 'Jumlah kantong darah harus antara 1-10.';
    }else {
        // Create blood request
        $requestData = [
            'patient_name' => $patientName,
            'hospital_name' => $hospitalName,
            'hospital_address' => $hospitalAddress,
            'location'=> $bloodRequestModel->coordinatesToPoint($latitude, $longitude),
            'city' => $city,
            'province' => $province,
            'blood_type_abo' => $bloodTypeAbo,
            'blood_type_rhesus' => $bloodTypeRhesus,
            'blood_bags_needed' => $bloodBagsNeeded,
            'donation_type' => $donationType,
            'contact_person' => $contactPerson,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail
        ];
        
        $requestId = $bloodRequestModel->createRequest($requestData);
        if ($requestId) {
            // Get the created request to get unique_token
            $createdRequest = $bloodRequestModel->getById($requestId);
            $editLink = $_ENV['WEBSITE_DOMAIN'] . '/edit_request.php?token=' . $createdRequest['unique_token'];
            
            // Send email with edit link
            try {
                $emailSubject = "Permohonan Donor Darah Berhasil Diajukan - BantuDonor";
                $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                        .button { display: inline-block; padding: 12px 30px; background-color: #ef4444; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .info-box { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #ef4444; }
                        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>BantuDonor</h1>
                            <p>Permohonan Donor Darah Berhasil Diajukan</p>
                        </div>
                        <div class='content'>
                            <h2>Terima kasih atas pengajuan permohonan!</h2>
                            <p>Permohonan donor darah Anda telah berhasil diajukan dengan detail sebagai berikut:</p>
                            
                            <div class='info-box'>
                                <strong>Nama Pasien:</strong> $patientName<br>
                                <strong>Rumah Sakit:</strong> $hospitalName<br>
                                <strong>Golongan Darah:</strong> $bloodTypeAbo$bloodTypeRhesus<br>
                                <strong>Jumlah Kantong:</strong> $bloodBagsNeeded<br>
                                <strong>Jenis Donor:</strong> $donationType<br>
                                <strong>Narahubung:</strong> $contactPerson ($contactPhone)
                            </div>
                            
                            <p>Sistem kami akan mengirimkan notifikasi kepada para pendonor yang sesuai dengan kebutuhan Anda.</p>
                            
                            <h3>Edit Permohonan</h3>
                            <p>Jika Anda perlu mengubah data permohonan, silakan klik tombol di bawah ini:</p>
                            
                            <center>
                                <a href='$editLink' class='button'>Edit Permohonan</a>
                            </center>
                            
                            <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>
                                <strong>Catatan:</strong> Link di atas hanya dapat digunakan selama permohonan masih aktif. 
                                Simpan email ini untuk keperluan edit di kemudian hari.
                            </p>
                        </div>
                        <div class='footer'>
                            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                            <p>&copy; 2025 BantuDonor. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email using PHPMailer
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USERNAME'];
                $mail->Password   = $_ENV['MAIL_PASSWORD'];
                $mail->SMTPSecure = ($_ENV['MAIL_PORT'] ?? 587) == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
                
                // Recipients
                $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bantudonor.online', $_ENV['MAIL_FROM_NAME'] ?? 'BantuDonor');
                $mail->addAddress($contactEmail);
                
                // Content
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $emailSubject;
                $mail->Body    = $emailBody;
                
                $mail->send();
            } catch (Exception $e) {
                // Log error but don't stop the process
                error_log("Email sending error: " . $e->getMessage());
            }
            
            // Send Telegram notifications
            try {
                $suitableUser = $userModel->getAllUsersTelegramChatIdsByProximity($requestId);
                if (!empty($suitableUser)) {
                    $telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);
                    foreach ($suitableUser as $user) {
                        // Skip if chat_id is empty or null
                        if (empty($user['telegram_chat_id'])) {
                            continue;
                        }
                        
                        try {
                            $telegram->sendMessage([
                                'chat_id' => $user['telegram_chat_id'],
                                'text' => "Permohonan baru telah diajukan:\n\n" .
                                          "Nama Pasien: $patientName\n" .
                                          "Rumah Sakit: $hospitalName\n" .
                                          "Alamat: $hospitalAddress\n" .
                                          "Golongan Darah: $bloodTypeAbo$bloodTypeRhesus\n" .
                                          "Jumlah Kantong: $bloodBagsNeeded\n" .
                                          "Jenis Donor: $donationType\n" .
                                          "Narahubung: $contactPerson ($contactPhone, $contactEmail)\n\n" .
                                          "Silakan cek aplikasi untuk detail lebih lanjut."
                            ]);
                        } catch (Exception $telegramError) {
                            // Log individual send error but continue with other users
                            error_log("Telegram send error for user {$user['telegram_chat_id']}: " . $telegramError->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't stop the process
                error_log("Telegram notification error: " . $e->getMessage());
            }
            
            // Send WhatsApp notifications
            try {
                $whatsappUsers = $userModel->getUsersWithWhatsAppByProximity($requestId);
                if (!empty($whatsappUsers)) {
                    $phoneNumbers = array_column($whatsappUsers, 'phone');
                    
                    $whatsappMessage = "*PERMOHONAN DONOR DARAH BARU*\n\n" .
                                      "Nama Pasien: *$patientName*\n" .
                                      "Rumah Sakit: *$hospitalName*\n" .
                                      "Alamat: $hospitalAddress\n" .
                                      "Kota: $city, $province\n\n" .
                                      "Golongan Darah: *$bloodTypeAbo$bloodTypeRhesus*\n" .
                                      "Jumlah Kantong: *$bloodBagsNeeded*\n" .
                                      "Jenis Donor: $donationType\n\n" .
                                      "Narahubung:\n" .
                                      "Nama: $contactPerson\n" .
                                      "Telepon: $contactPhone\n" .
                                      "Email: $contactEmail\n\n" .
                                      "Segera hubungi narahubung jika Anda bersedia mendonor!";
                    
                    $whatsappResponse = sendWhatsAppMessage($phoneNumbers, $whatsappMessage);
                }
            } catch (Exception $e) {
                // Log error but don't stop the process
                error_log("WhatsApp notification error: " . $e->getMessage());
            }
            
            $message = 'Permohonan berhasil diajukan!';
            // Clear form data
            $_POST = [];
        } else {
            $error = 'Terjadi kesalahan saat mengajukan permohonan. Silakan coba lagi.';
        }
    }
}

// Include header template
include 'layout/header.php';
?>

    <!-- Page Header -->
    <section class="bg-red-500">
        <div class="w-full px-4 sm:px-6 lg:px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] mx-auto">
            <h1 class="text-4xl font-bold text-center text-slate-50">Ajukan Permohonan</h1>
        </div>
    </section>

    <!-- Request Form -->
    <section class="bg-slate-50">
        <div class="w-full px-4 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto sm:px-6 lg:px-20">
            
            <?php if ($error): ?>
                <div class="w-full max-w-4xl p-4 text-red-700 bg-red-100 border border-red-400 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="w-full max-w-4xl p-4 text-green-700 bg-green-100 border border-green-400 rounded">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="w-full max-w-4xl px-6 py-8 space-y-6 bg-white rounded-lg shadow-md">
                <!-- Patient Information Section -->
                <h2 class="w-full mb-4 text-2xl font-bold text-gray-900">Informasi Pasien</h2>
                <div class="w-full h-0 mb-6 border-t border-slate-200"></div>
                
                <div class="flex flex-col w-full gap-4 mb-8">
                    <!-- Patient Name -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Nama Pasien</label>
                        <input type="text" 
                               name="patient_name" 
                               required
                               value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : ''; ?>"
                               class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                    </div>
                    
                    <!-- Hospital Search -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Rumah Sakit</label>
                        <div class="relative">
                            <input type="text" 
                                   id="hospital-search"
                                   name="hospital_name" 
                                   required
                                   placeholder="Ketik nama rumah sakit untuk mencari..."
                                   value="<?php echo isset($_POST['hospital_name']) ? htmlspecialchars($_POST['hospital_name']) : ''; ?>"
                                   class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none"
                                   autocomplete="off">
                            <div id="hospital-search-results" class="absolute left-0 right-0 z-10 hidden mt-1 overflow-y-auto bg-white border rounded-lg shadow-lg top-full border-slate-300 max-h-60">
                            </div>
                            <div id="search-loading" class="absolute hidden right-3 top-3">
                                <div class="w-6 h-6 border-b-2 border-red-500 rounded-full animate-spin"></div>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">Mulai ketik nama rumah sakit untuk melihat hasil pencarian</p>
                    </div>
                    
                    <!-- Hospital Address (Read-only) -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Alamat Rumah Sakit</label>
                        <textarea name="hospital_address" 
                                  id="hospital-address"
                                  required
                                  readonly
                                  rows="3"
                                  placeholder="Alamat akan terisi otomatis setelah memilih rumah sakit"
                                  class="w-full px-4 py-3 text-gray-700 border rounded-lg cursor-not-allowed border-slate-300 bg-gray-50 focus:outline-none"><?php echo isset($_POST['hospital_address']) ? htmlspecialchars($_POST['hospital_address']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Location (Read-only) -->
                    <div class="flex w-full gap-2">
                        <div class="flex flex-col flex-1 gap-2">
                            <label class="text-base font-normal text-slate-600">Kota/Kabupaten</label>
                            <input type="text" 
                                   name="city" 
                                   id="hospital-city"
                                   required
                                   readonly
                                   placeholder="Akan terisi otomatis"
                                   value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>"
                                   class="w-full px-4 py-3 text-gray-700 border rounded-lg cursor-not-allowed border-slate-300 bg-gray-50 focus:outline-none">
                        </div>
                        <div class="flex flex-col flex-1 gap-2">
                            <label class="text-base font-normal text-slate-600">Provinsi</label>
                            <input type="text" 
                                   name="province"
                                   id="hospital-province"
                                   required
                                   readonly
                                   placeholder="Akan terisi otomatis"
                                   value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>"
                                   class="w-full px-4 py-3 text-gray-700 border rounded-lg cursor-not-allowed border-slate-300 bg-gray-50 focus:outline-none">
                        </div>
                    </div>
                    
                    <!-- Hidden Coordinates -->
                    <input type="hidden" name="latitude" id="hospital-latitude" value="<?php echo isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : ''; ?>">
                    <input type="hidden" name="longitude" id="hospital-longitude" value="<?php echo isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : ''; ?>">
                    
                    <!-- Location Info Display -->
                    <div id="location-info" class="hidden w-full p-3 border border-green-200 rounded-lg bg-green-50">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="text-sm text-green-700">
                                <strong>Lokasi dipilih:</strong> <span id="selected-location"></span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Blood Type -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Golongan Darah</label>
                        <div class="flex w-full gap-2">
                            <select name="blood_type_abo" 
                                    required
                                    class="flex-1 px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                                <option value="">Pilih ABO</option>
                                <option value="A" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'A') ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'B') ? 'selected' : ''; ?>>B</option>
                                <option value="AB" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'AB') ? 'selected' : ''; ?>>AB</option>
                                <option value="O" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'O') ? 'selected' : ''; ?>>O</option>
                            </select>
                            
                            <select name="blood_type_rhesus" 
                                    required
                                    class="flex-1 px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                                <option value="">Pilih Rhesus</option>
                                <option value="+" <?php echo (isset($_POST['blood_type_rhesus']) && $_POST['blood_type_rhesus'] === '+') ? 'selected' : ''; ?>>Positif (+)</option>
                                <option value="-" <?php echo (isset($_POST['blood_type_rhesus']) && $_POST['blood_type_rhesus'] === '-') ? 'selected' : ''; ?>>Negatif (-)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Blood Bags -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Jumlah Kantong</label>
                        <input type="number" 
                               name="blood_bags_needed" 
                               required
                               min="1"
                               max="10"
                               value="<?php echo isset($_POST['blood_bags_needed']) ? htmlspecialchars($_POST['blood_bags_needed']) : '1'; ?>"
                               class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                    </div>
                    
                    <!-- Donation Type -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Jenis Donor</label>
                        <select name="donation_type" 
                                required
                                class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                            <option value="">Pilih Jenis Donor</option>
                            <option value="Whole Blood" <?php echo (isset($_POST['donation_type']) && $_POST['donation_type'] === 'Whole Blood') ? 'selected' : ''; ?>>Whole Blood</option>
                            <option value="Red Blood Cells" <?php echo (isset($_POST['donation_type']) && $_POST['donation_type'] === 'Red Blood Cells') ? 'selected' : ''; ?>>Red Blood Cells</option>
                            <option value="Platelets" <?php echo (isset($_POST['donation_type']) && $_POST['donation_type'] === 'Platelets') ? 'selected' : ''; ?>>Platelets</option>
                            <option value="Plasma" <?php echo (isset($_POST['donation_type']) && $_POST['donation_type'] === 'Plasma') ? 'selected' : ''; ?>>Plasma</option>
                        </select>
                    </div>
                    
                </div>

                <!-- Contact Information Section -->
                <h2 class="w-full mb-4 text-2xl font-bold text-gray-900">Informasi Narahubung/Contact Person</h2>
                <div class="w-full h-0 mb-6 border-t border-slate-200"></div>
                
                <div class="flex flex-col w-full gap-4 mb-8">
                    <!-- Contact Person -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Nama Narahubung</label>
                        <input type="text" 
                               name="contact_person" 
                               required
                               value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ($loggedInUser ? htmlspecialchars($loggedInUser['full_name']) : ''); ?>"
                               class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                    </div>
                    
                    <!-- Contact Phone -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Nomor WhatsApp</label>
                        <input type="tel" 
                               name="contact_phone" 
                               required
                               placeholder="Contoh: 085xxxxxxxx"
                               value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ($loggedInUser ? htmlspecialchars($loggedInUser['phone']) : ''); ?>"
                               class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                    </div>
                    
                    <!-- Contact Email -->
                    <div class="flex flex-col w-full gap-2">
                        <label class="text-base font-normal text-slate-600">Email (Untuk mendapatkan update)</label>
                        <input type="email" 
                               name="contact_email" 
                               required
                               value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ($loggedInUser ? htmlspecialchars($loggedInUser['email']) : ''); ?>"
                               class="w-full px-4 py-3 border rounded-lg border-slate-300 focus:border-red-500 focus:outline-none">
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-8 py-3 text-base font-semibold transition-colors bg-red-500 rounded-full text-slate-50 hover:bg-red-600">
                    Kirim Permohonan
                </button>
            </form>
        </div>
    </section>

<?php include 'layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hospitalSearch = document.getElementById('hospital-search');
    const searchResults = document.getElementById('hospital-search-results');
    const searchLoading = document.getElementById('search-loading');
    const hospitalAddress = document.getElementById('hospital-address');
    const hospitalCity = document.getElementById('hospital-city');
    const hospitalProvince = document.getElementById('hospital-province');
    const hospitalLatitude = document.getElementById('hospital-latitude');
    const hospitalLongitude = document.getElementById('hospital-longitude');
    const locationInfo = document.getElementById('location-info');
    const selectedLocation = document.getElementById('selected-location');
    
    let searchTimeout;
    let isSelectingFromDropdown = false;
    
    // Hospital search functionality
    hospitalSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            hideSearchResults();
            return;
        }
        
        // Don't search if user is selecting from dropdown
        if (isSelectingFromDropdown) {
            isSelectingFromDropdown = false;
            return;
        }
        
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            searchHospitals(query);
        }, 500);
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!hospitalSearch.contains(e.target) && !searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });
    
    // Show existing results when focusing on search input
    hospitalSearch.addEventListener('focus', function() {
        if (searchResults.children.length > 0) {
            searchResults.classList.remove('hidden');
        }
    });
    
    function searchHospitals(query) {
        searchLoading.classList.remove('hidden');
        fetch(`/handler/search_hospitals?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                searchLoading.classList.add('hidden');
                
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    displaySearchResults(data.data);
                } else {
                    displayNoResults();
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchLoading.classList.add('hidden');
                displayError();
            });
    }
    
    function displaySearchResults(hospitals) {
        searchResults.innerHTML = '';
        
        hospitals.forEach(hospital => {
            const resultItem = document.createElement('div');
            resultItem.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-slate-100 last:border-b-0';
            
            resultItem.innerHTML = `
                <div class="font-medium text-gray-900">${escapeHtml(hospital.name.split(',')[0])}</div>
                <div class="text-sm text-gray-600">${escapeHtml(hospital.address)}</div>
                <div class="mt-1 text-xs text-gray-500">
                    ${escapeHtml(hospital.city)} • ${escapeHtml(hospital.province)}
                </div>
            `;
            
            resultItem.addEventListener('click', () => {
                selectHospital(hospital);
            });
            
            searchResults.appendChild(resultItem);
        });
        
        searchResults.classList.remove('hidden');
    }
    
    function displayNoResults() {
        searchResults.innerHTML = `
            <div class="px-4 py-3 text-center text-gray-500">
                <div class="text-sm">Tidak ada rumah sakit ditemukan</div>
                <div class="mt-1 text-xs">Coba gunakan kata kunci yang berbeda</div>
            </div>
        `;
        searchResults.classList.remove('hidden');
    }
    
    function displayError() {
        searchResults.innerHTML = `
            <div class="px-4 py-3 text-center text-red-500">
                <div class="text-sm">Terjadi kesalahan saat pencarian</div>
                <div class="mt-1 text-xs">Silakan coba lagi</div>
            </div>
        `;
        searchResults.classList.remove('hidden');
    }
    
    function selectHospital(hospital) {
        isSelectingFromDropdown = true;
        
        // Fill form fields
        hospitalSearch.value = hospital.name.split(',')[0];
        hospitalAddress.value = hospital.address;
        hospitalCity.value = hospital.city;
        hospitalProvince.value = hospital.province;
        hospitalLatitude.value = hospital.latitude;
        hospitalLongitude.value = hospital.longitude;
        
        // Show location info
        selectedLocation.textContent = `${hospital.city}, ${hospital.province}`;
        locationInfo.classList.remove('hidden');
        
        // Hide search results
        hideSearchResults();
    }
    
    function hideSearchResults() {
        searchResults.classList.add('hidden');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Clear location info when hospital search is cleared
    hospitalSearch.addEventListener('input', function() {
        if (this.value.trim() === '') {
            clearLocationFields();
        }
    });
    
    function clearLocationFields() {
        hospitalAddress.value = '';
        hospitalCity.value = '';
        hospitalProvince.value = '';
        hospitalLatitude.value = '';
        hospitalLongitude.value = '';
        locationInfo.classList.add('hidden');
    }
    
    // Show location info if coordinates exist (for form errors)
    if (hospitalLatitude.value && hospitalLongitude.value && hospitalCity.value) {
        selectedLocation.textContent = `${hospitalCity.value}, ${hospitalProvince.value}`;
        locationInfo.classList.remove('hidden');
    }
});
</script>