<?php
session_start();
require_once 'models/BloodRequest.php';
require_once 'models/User.php';

// Set page variables for header template
$pageTitle = 'Daftar Kebutuhan Darah - BantuDonor';
$currentPage = 'requests';

$bloodRequestModel = new BloodRequest();

// Get user blood type and city for default filter
$userBloodType = '';
$userCity = '';
if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $user = $userModel->getById($_SESSION['user_id']);
    if ($user) {
        $userBloodType = $user['blood_type_abo'] . $user['blood_type_rhesus'];
        $userCity = $user['city'];
    }
}

// Handle search - use user blood type and city as default if no filter is specified
$bloodType = isset($_GET['blood_type']) ? $_GET['blood_type'] : '';
$showAll = isset($_GET['show_all']) ? $_GET['show_all'] : '';
$city = isset($_GET['city']) ? $_GET['city'] : '';
$userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$userLon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$useLocation = isset($_GET['use_location']) ? $_GET['use_location'] : '';

// Only apply auto-filter if user hasn't explicitly chosen to show all
if (empty($bloodType) && !empty($userBloodType) && $showAll !== '1') {
    $bloodType = $userBloodType;
}

// Only apply city auto-filter if user hasn't specified a city and hasn't chosen to show all
if (empty($city) && !empty($userCity) && $showAll !== '1') {
    $city = $userCity;
}

$filters = [];
if ($bloodType && $bloodType !== 'all') {
    $filters['blood_type'] = $bloodType;
}
if ($city && !$useLocation) {
    $filters['city'] = $city;
}

// Use coordinate-based search if user location is provided
if ($useLocation === '1' && $userLat !== null && $userLon !== null) {
    $bloodRequests = $bloodRequestModel->searchByLocation($userLat, $userLon, 20, $filters, $userBloodType);
} elseif (empty($filters)) {
    $bloodRequests = $bloodRequestModel->getActiveRequestsWithSmartSorting($userBloodType, $userCity);
} else {
    $bloodRequests = $bloodRequestModel->searchRequestsWithSmartSorting($filters, $userBloodType, $userCity);
}

// Include header template
include 'layout/header.php';
?>

    <!-- Page Header -->
    <section class="bg-red-500">
        <div class="w-full px-4 sm:px-8 md:px-12 lg:px-20 py-12 flex flex-col justify-center items-center gap-4 max-w-[1280px] relative mx-auto">
            <h1 class="text-slate-50 text-3xl sm:text-4xl font-bold text-center">Daftar Kebutuhan Darah</h1>
            <?php if (isset($_SESSION['user_id']) && (!empty($userBloodType) || !empty($userCity)) && empty($_GET['blood_type']) && empty($_GET['city']) && $showAll !== '1'): ?>
            <div class="text-slate-50 text-sm sm:text-base font-normal text-center px-2">
                Filter otomatis disesuaikan dengan 
                <?php if (!empty($userBloodType) && !empty($userCity)): ?>
                    golongan darah Anda: <span class="font-semibold"><?php echo htmlspecialchars($userBloodType); ?></span> 
                    dan lokasi Anda: <span class="font-semibold"><?php echo htmlspecialchars($userCity); ?></span>
                <?php elseif (!empty($userBloodType)): ?>
                    golongan darah Anda: <span class="font-semibold"><?php echo htmlspecialchars($userBloodType); ?></span>
                <?php elseif (!empty($userCity)): ?>
                    lokasi Anda: <span class="font-semibold"><?php echo htmlspecialchars($userCity); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Search and Content -->
    <section class="bg-slate-50">
        <div class="w-full px-4 sm:px-8 md:px-12 lg:px-20 py-12 flex flex-col justify-center items-start gap-8 max-w-[1280px] relative mx-auto">
        <!-- Search Form -->
        <form method="GET" id="searchForm" class="w-full px-4 py-4 bg-white rounded-lg flex flex-col gap-3">
            <div class="flex flex-wrap sm:flex-nowrap gap-3 justify-start items-center">
                <select name="blood_type" class="w-full sm:w-64 px-4 py-3 rounded-lg border border-slate-300">
                    <option value="all">Semua Golongan Darah</option>
                    <?php 
                    $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                    foreach ($bloodTypes as $type): 
                    ?>
                        <option value="<?php echo $type; ?>" <?php echo ($bloodType === $type) ? 'selected' : ''; ?>>
                            <?php echo $type; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" 
                       id="cityInput"
                       name="city" 
                       value="<?php echo htmlspecialchars($city); ?>"
                       placeholder="Cari kota/kabupaten" 
                       class="flex-grow min-w-[150px] px-4 py-3 rounded-lg border border-slate-300">
                
                <input type="hidden" name="use_location" id="useLocationInput" value="">
                <input type="hidden" name="lat" id="latInput" value="">
                <input type="hidden" name="lon" id="lonInput" value="">
                
                <button type="submit" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition w-full sm:w-auto">
                    Cari
                </button>
                
                <a href="blood_requests?show_all=1" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition w-full sm:w-auto">
                    Lihat Semua
                </a>
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" id="useMyLocation" class="w-4 h-4 text-red-500 border-gray-300 rounded focus:ring-red-500">
                <label for="useMyLocation" class="text-sm text-gray-700 cursor-pointer">Gunakan lokasi saya (radius 20km)</label>
            </div>
        </form>

        <!-- Results -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <?php if (!empty($bloodRequests)): ?>
                <?php foreach ($bloodRequests as $request): ?>
                <div class="p-5 bg-white flex flex-col justify-start items-start gap-4 relative">
                    <div class="w-full flex justify-start items-end gap-4">
                        <div class="flex-1 flex flex-col justify-start items-start gap-2">
                            <div class="text-slate-600 text-sm font-normal">#<?php echo htmlspecialchars($request['request_code']); ?></div>
                            <div class="text-gray-900 text-xl font-semibold"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                        </div>
                        <div class="text-red-500 text-xl font-semibold">
                            <?php echo htmlspecialchars($request['blood_type_abo'] . $request['blood_type_rhesus']); ?>
                        </div>
                    </div>
                    
                    <div class="flex flex-col justify-start items-start gap-4">
                        <!-- Location -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-map-marker text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['city'] . ', ' . $request['province']); ?>
                            </div>
                        </div>
                        
                        <!-- Blood bags needed -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-blood-bag text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['blood_bags_needed']); ?> Kantong
                            </div>
                        </div>
                        
                        <!-- Donation type -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-water-check text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['donation_type']); ?>
                            </div>
                        </div>
                        
                        <!-- Needed date -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-calendar-month text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo date('j M Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                        
                        <!-- Hospital -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-hospital-building text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['hospital_name']); ?>
                            </div>
                        </div>
                        
                        <!-- Contact person -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-account text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['contact_person']); ?>
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-whatsapp text-red-500"></i>
                            <div class="text-slate-600 text-base font-normal">
                                <?php echo htmlspecialchars($request['contact_phone']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action buttons -->
                    <div class="flex justify-start items-start gap-4">
                        <a href="https://wa.me/<?php 
                            $phoneNumber = str_replace(['+', '-', ' ', '(', ')'], '', $request['contact_phone']); 
                            // Convert Indonesian local format (08xx) to international format (628xx)
                            if (substr($phoneNumber, 0, 1) === '0') {
                                $phoneNumber = '62' . substr($phoneNumber, 1);
                            }
                            echo $phoneNumber;
                        ?>" 
                           target="_blank"
                           class="px-5 py-2 bg-green-700 rounded-full flex justify-center items-center gap-2">
                            <i class="mdi mdi-whatsapp text-slate-50"></i>
                            <div class="text-slate-50 text-base font-semibold">Chat</div>
                        </a>
                        
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($request['contact_phone']); ?>')"
                                class="px-5 py-2 bg-slate-200 rounded-full flex justify-center items-center gap-2">
                            <i class="mdi mdi-clipboard-file text-gray-900"></i>
                            <div class="text-gray-900 text-base font-semibold">Salin Nomor</div>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-10">
                    <p class="text-slate-600 text-lg">Tidak ada permintaan darah yang sesuai dengan pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </section>

<?php include 'layout/footer.php'; ?>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Nomor berhasil disalin: ' + text);
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    
    // Handle location checkbox
    const useMyLocationCheckbox = document.getElementById('useMyLocation');
    const cityInput = document.getElementById('cityInput');
    const searchForm = document.getElementById('searchForm');
    const useLocationInput = document.getElementById('useLocationInput');
    const latInput = document.getElementById('latInput');
    const lonInput = document.getElementById('lonInput');
    
    useMyLocationCheckbox.addEventListener('change', function() {
        if (this.checked) {
            cityInput.value = '';
            cityInput.disabled = true;
            cityInput.classList.add('bg-gray-100');
        } else {
            cityInput.disabled = false;
            cityInput.classList.remove('bg-gray-100');
            useLocationInput.value = '';
            latInput.value = '';
            lonInput.value = '';
        }
    });
    
    // Handle form submission
    searchForm.addEventListener('submit', function(e) {
        if (useMyLocationCheckbox.checked) {
            e.preventDefault();
            
            if ('geolocation' in navigator) {
                // Options for better Safari compatibility
                const geoOptions = {
                    enableHighAccuracy: true,
                    timeout: 10000,  // 10 seconds
                    maximumAge: 30000  // Accept cached position up to 30 seconds old
                };
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    useLocationInput.value = '1';
                    latInput.value = position.coords.latitude;
                    lonInput.value = position.coords.longitude;
                    searchForm.submit();
                }, function(error) {
                    let errorMessage = 'Tidak dapat mengakses lokasi Anda. ';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Anda menolak akses lokasi. Silakan aktifkan izin lokasi di pengaturan browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Informasi lokasi tidak tersedia.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Permintaan lokasi timeout. Silakan coba lagi.';
                            break;
                        default:
                            errorMessage += 'Terjadi kesalahan yang tidak diketahui.';
                    }
                    
                    alert(errorMessage);
                    console.error('Geolocation error:', error);
                    
                    // Uncheck the box so user can try again or use city search
                    useMyLocationCheckbox.checked = false;
                    cityInput.disabled = false;
                    cityInput.classList.remove('bg-gray-100');
                }, geoOptions);
            } else {
                alert('Browser Anda tidak mendukung geolocation.');
            }
        }
    });
</script>