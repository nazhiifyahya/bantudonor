<?php
session_start();

// Set page variables for header template
$pageTitle = 'BantuDonor - Jadilah Pahlawan Mulai dari Satu Tetes Darah';
$currentPage = 'home';

// Get featured blood requests
$featuredRequests = [];

// Include header template
include 'layout/header.php';
?>

<!-- Hero Section -->
<section>
    <div class="w-full px-6 sm:px-10 md:px-20 py-16 flex flex-col md:flex-row justify-between items-center max-w-[1280px] relative mx-auto">
        <div class="w-full md:w-[486px] flex flex-col justify-start items-start gap-4 mb-8 md:mb-0">
            <div class="text-gray-900 text-base font-semibold">Selamat Datang di BantuDonor</div>
            <h1 class="text-gray-900 text-3xl sm:text-4xl md:text-5xl font-semibold leading-tight">
                Jadilah Pahlawan Mulai dari Satu Tetes Darah
            </h1>
            <p class="text-slate-600 text-base font-normal max-w-md">
                Solusi mudah untuk menemukan dan terhubung dengan pendonor darah di sekitarmu
            </p>
            <div class="flex flex-col sm:flex-row justify-start items-start sm:items-center gap-4 mt-4">
                <a href="create_request.php" class="px-8 py-3 bg-red-500 rounded-full text-slate-50 text-base font-semibold text-center w-full sm:w-auto">Ajukan Permohonan</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="px-8 py-3 rounded-full border border-gray-900 text-gray-900 text-base font-semibold text-center w-full sm:w-auto">Jadi Relawan</a>
                <?php else: ?>
                    <a href="blood_requests.php" class="px-8 py-3 rounded-full border border-gray-900 text-gray-900 text-base font-semibold text-center w-full sm:w-auto">Jadi Relawan</a>
                <?php endif; ?>
            </div>
        </div>
        <img class="w-full sm:w-[690px] h-64 sm:h-80 md:h-96 object-cover" src="/images/hero-img.png" alt="Hero Image" />
    </div>
</section>

<!-- Featured Blood Requests -->
<section class="bg-red-500">
    <div class="w-full px-6 sm:px-10 md:px-20 py-16 flex flex-col justify-center items-start gap-10 max-w-[1280px] relative mx-auto">
        <div class="w-full flex flex-col sm:flex-row justify-between items-center gap-4 sm:gap-0">
            <h2 class="text-slate-50 text-2xl font-semibold">Daftar Kebutuhan Darah</h2>
            <a href="blood_requests.php" class="px-8 py-3 rounded-full border border-slate-50 text-slate-50 text-base font-semibold text-center">Lihat Semua</a>
        </div>
        
        <div class="w-full flex flex-col sm:flex-row justify-start items-start gap-6 sm:gap-10 flex-wrap">
            <?php if (!empty($featuredRequests)): ?>
                <?php foreach ($featuredRequests as $request): ?>
                <div class="flex-1 min-w-[280px] max-w-sm p-5 bg-white flex flex-col justify-start items-start gap-2 rounded-lg shadow-md">
                    <div class="text-slate-600 text-sm font-normal">#<?php echo htmlspecialchars($request['request_code']); ?></div>
                    <div class="w-full flex justify-start items-start gap-4">
                        <div class="flex-1 text-gray-900 text-xl font-semibold"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                        <div class="text-red-500 text-xl font-semibold"><?php echo htmlspecialchars($request['blood_type_abo'] . $request['blood_type_rhesus']); ?></div>
                    </div>
                    <div class="flex justify-start items-start gap-4">
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-map-marker text-red-500"></i>
                            <div class="text-slate-600 text-xs font-normal"><?php echo htmlspecialchars($request['city']); ?></div>
                        </div>
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-blood-bag text-red-500"></i>
                            <div class="text-slate-600 text-xs font-normal"><?php echo htmlspecialchars($request['blood_bags_needed']); ?> Kantong</div>
                        </div>
                        <div class="flex justify-start items-center gap-2">
                            <i class="mdi mdi-calendar-month text-red-500"></i>
                            <div class="text-slate-600 text-xs font-normal"><?php echo htmlspecialchars($request['deadline']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-slate-50 text-base">Tidak ada kebutuhan darah yang ditampilkan saat ini.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer template
include 'layout/footer.php';
?>
