<?php

// Set page variables for header template
$pageTitle = 'BantuDonor - Jadilah Pahlawan Mulai dari Satu Tetes Darah';
$currentPage = 'home';

// Get featured blood requests
$featuredRequests = [];

// Include header template
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section>
        <div class="w-full px-20 py-16 flex justify-between items-center max-w-[1280px] relative mx-auto">
            <div class="w-[486px] flex flex-col justify-start items-start gap-4">
                <div class="text-gray-900 text-base font-semibold">Selamat Datang di BantuDonor</div>
                <h1 class="text-gray-900 text-5xl font-semibold">Jadilah Pahlawan Mulai dari Satu Tetes Darah</h1>
                <p class="text-slate-600 text-base font-normal">Solusi mudah untuk menemukan dan terhubung dengan pendonor darah di sekitarmu</p>
                <div class="flex justify-start items-start gap-4">
                    <a href="create_request.php" class="px-8 py-3 bg-red-500 rounded-full text-slate-50 text-base font-semibold">Ajukan Permohonan</a>
                    <a href="register.php" class="px-8 py-3 rounded-full border border-gray-900 text-gray-900 text-base font-semibold">Jadi Relawan</a>
                </div>
            </div>
            <img class="w-[690px] h-96" src="/images/hero-img.png" alt="Hero Image" />
        </div>
    </section>

    <!-- Featured Blood Requests -->
    <section class="bg-red-500">
        <div class="w-full px-20 py-16 flex flex-col justify-center items-start gap-10 max-w-[1280px] relative mx-auto">
            <div class="w-full flex justify-between items-center">
                <h2 class="text-slate-50 text-2xl font-semibold">Daftar Kebutuhan Darah</h2>
                <a href="blood_requests.php" class="px-8 py-3 rounded-full border border-slate-50 text-slate-50 text-base font-semibold">Lihat Semua</a>
            </div>
            
            <div class="w-full flex justify-start items-start gap-10">
                <?php if (!empty($featuredRequests)): ?>
                    <?php foreach ($featuredRequests as $request): ?>
                    <div class="flex-1 p-5 bg-white flex flex-col justify-start items-start gap-2">
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
                                <div class="text-slate-600 text-xs font-normal"><?php echo date('j M Y', strtotime($request['needed_date'])); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="w-full text-center text-slate-50 text-lg">Belum ada permintaan darah saat ini.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>