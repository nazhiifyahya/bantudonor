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
if ($city) {
    $filters['city'] = $city;
}

// Use smart sorting that considers user preferences
if (empty($filters)) {
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
        <form method="GET" class="w-full px-4 py-4 bg-white rounded-lg flex flex-wrap sm:flex-nowrap gap-3 justify-start items-center">
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
                   name="city" 
                   value="<?php echo htmlspecialchars($city); ?>"
                   placeholder="Cari kota/kabupaten" 
                   class="flex-grow min-w-[150px] px-4 py-3 rounded-lg border border-slate-300">
            
            <button type="submit" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition w-full sm:w-auto">
                Cari
            </button>
            
            <?php if (isset($_SESSION['user_id']) && (!empty($userBloodType) || !empty($userCity)) && ($bloodType !== 'all' || !empty($city))): ?>
            <a href="blood_requests.php?show_all=1" class="px-8 py-3 rounded-full bg-red-500 text-white text-base font-semibold hover:bg-red-600">
                Lihat Semua
            </a>
            <?php endif; ?>
        </form>

        <!-- Sorting Info -->
        <?php if (isset($_SESSION['user_id']) && (!empty($userBloodType) || !empty($userCity))): ?>
        <div class="w-full px-4 py-3 flex flex-col gap-6 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="mdi mdi-sort text-blue-600"></i>
                <span class="text-blue-800 text-sm font-medium">
                    Hasil diurutkan berdasarkan: Golongan darah yang cocok → Lokasi terdekat → Tingkat urgensi → Tanggal dibutuhkan
                </span>
            </div>
            <div class="flex flex-wrap gap-4 text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-orange-400 rounded"></div>
                    <span class="text-gray-600">Golongan Darah Cocok</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-blue-400 rounded"></div>
                    <span class="text-gray-600">Lokasi Dekat</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Results -->
        <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <?php if (!empty($bloodRequests)): ?>
                <?php foreach ($bloodRequests as $request): ?>
                <?php
                // Check if this request matches user preferences for visual indicators
                $bloodTypeMatch = false;
                $cityMatch = false;
                
                if (isset($_SESSION['user_id'])) {
                    $requestBloodType = $request['blood_type_abo'] . $request['blood_type_rhesus'];
                    $bloodTypeMatch = ($requestBloodType === $userBloodType);
                    $cityMatch = ($request['city'] === $userCity);
                }
                
                // Determine card styling based on matches
                $cardClass = 'p-5 bg-white flex flex-col justify-start items-start gap-4 relative';
                $priorityIndicator = '';
                
                if ($bloodTypeMatch && $cityMatch) {
                    $cardClass .= ' border-l-4 border-red-500';
                    $priorityIndicator = '<div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">Prioritas Tinggi</div>';
                } elseif ($bloodTypeMatch) {
                    $cardClass .= ' border-l-4 border-orange-400';
                    $priorityIndicator = '<div class="absolute top-2 right-2 bg-orange-400 text-white text-xs px-2 py-1 rounded">Golongan Cocok</div>';
                } elseif ($cityMatch) {
                    $cardClass .= ' border-l-4 border-blue-400';
                    $priorityIndicator = '<div class="absolute top-2 right-2 bg-blue-400 text-white text-xs px-2 py-1 rounded">Lokasi Dekat</div>';
                }
                ?>
                <div class="<?php echo $cardClass; ?>">
                    <?php echo $priorityIndicator; ?>
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
                        <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', $request['contact_phone']); ?>" 
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
</script>