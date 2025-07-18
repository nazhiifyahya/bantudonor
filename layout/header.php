<?php
/**
 * Header Template for BantuDonor
 * Usage: include 'includes/header.php';
 */

// Set default values if not defined
$pageTitle = isset($pageTitle) ? $pageTitle : 'BantuDonor - Jadilah Pahlawan Mulai dari Satu Tetes Darah';
$currentPage = isset($currentPage) ? $currentPage : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico" />
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet" />
    
    <!-- Leaflet CSS for OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .leaflet-container { 
            font-family: 'Inter', sans-serif; 
        }
    </style>
</head>
<body class="bg-white">

<!-- Header Navigation -->
<header>
    <div class="w-full px-6 sm:px-20 py-4 border-b border-slate-200 flex justify-between items-center max-w-[1280px] relative mx-auto">
        <a href="index.php">
            <img src="/images/logo-bantu-donor.png" alt="BantuDonor" class="h-12 w-[208px]" />
        </a>

        <!-- Desktop Menu -->
        <nav class="hidden md:flex justify-start items-center gap-5">
            <a href="index.php" class="<?php echo $currentPage === 'home' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?> text-base hover:text-red-500 transition-colors">Home</a>
            <a href="blood_requests.php" class="<?php echo $currentPage === 'requests' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?> text-base hover:text-red-500 transition-colors">Daftar Kebutuhan Darah</a>
            <a href="create_request.php" class="<?php echo $currentPage === 'create_request' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?> text-base hover:text-red-500 transition-colors">Ajukan Permohonan</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged in user menu -->
                <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?> text-base hover:text-red-500 transition-colors">Dashboard</a>
                <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">Logout</a>
            <?php else: ?>
                <!-- Guest user menu -->
                <a href="register.php" class="<?php echo $currentPage === 'register' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?> text-base hover:text-red-500 transition-colors">Jadi Relawan</a>
                <a href="login.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">Login</a>
            <?php endif; ?>
        </nav>

        <!-- Mobile Menu Burger Icon -->
        <button id="menu-toggle" class="md:hidden focus:outline-none" aria-label="Toggle menu">
            <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Mobile Dropdown Menu -->
    <nav id="mobile-menu" class="hidden md:hidden px-6 pb-4 max-w-[1280px] mx-auto bg-white border-b border-slate-200">
        <a href="index.php" class="block py-2 text-base hover:text-red-500 <?php echo $currentPage === 'home' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?>">Home</a>
        <a href="blood_requests.php" class="block py-2 text-base hover:text-red-500 <?php echo $currentPage === 'requests' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?>">Daftar Kebutuhan Darah</a>
        <a href="create_request.php" class="block py-2 text-base hover:text-red-500 <?php echo $currentPage === 'create_request' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?>">Ajukan Permohonan</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="block py-2 text-base hover:text-red-500 <?php echo $currentPage === 'dashboard' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?>">Dashboard</a>
            <a href="logout.php" class="block py-2 px-4 mt-2 bg-red-500 text-white rounded-lg text-center hover:bg-red-600 transition-colors">Logout</a>
        <?php else: ?>
            <a href="register.php" class="block py-2 text-base hover:text-red-500 <?php echo $currentPage === 'register' ? 'text-red-500 font-semibold' : 'text-slate-600'; ?>">Jadi Relawan</a>
            <a href="login.php" class="block py-2 px-4 mt-2 bg-red-500 text-white rounded-lg text-center hover:bg-red-600 transition-colors">Login</a>
        <?php endif; ?>
    </nav>
</header>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        var menu = document.getElementById('mobile-menu');
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
        }
    });
</script>
