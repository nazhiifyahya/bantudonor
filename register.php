<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

// Set page variables for header template
$pageTitle = 'Jadi Relawan - BantuDonor';
$currentPage = 'register';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userModel = new User();
    
    // Validate input
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $bloodTypeAbo = $_POST['blood_type_abo'];
    $bloodTypeRhesus = $_POST['blood_type_rhesus'];
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $latitude = isset($_POST['latitude']) && !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    // Basic validation
    if (empty($email) || empty($password) || empty($fullName) || empty($phone) || 
        empty($bloodTypeAbo) || empty($bloodTypeRhesus) || empty($city) || empty($province) || empty($address)) {
        $error = 'Semua field wajib diisi. Silakan pilih lokasi Anda menggunakan peta atau GPS.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Check if email already exists
        $existingUser = $userModel->getUserByEmail($email);
        if ($existingUser) {
            $error = 'Email sudah terdaftar.';
        } else {
            // Create user
            $userData = [
                'email' => $email,
                'password' => $password,
                'full_name' => $fullName,
                'phone' => $phone,
                'blood_type_abo' => $bloodTypeAbo,
                'blood_type_rhesus' => $bloodTypeRhesus,
                'location' => $userModel->coordinatesToPoint($latitude, $longitude),
                'address' => $address,
                'city' => $city,
                'province' => $province,
            ];
            
            $userId = $userModel->createUser($userData);
            if ($userId) {
                $message = 'Registrasi berhasil! Silakan login.';
                // Redirect after 2 seconds
                header("Refresh: 2; url=login.php");
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
            }
        }
    }
}

// Include header template
include 'layout/header.php';
?>

    <!-- Page Header -->
    <section class="bg-red-500">
        <div class="w-full px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto">
            <h1 class="text-slate-50 text-4xl font-bold">Jadi Relawan</h1>
        </div>
    </section>

    <!-- Registration Form -->
    <section class="bg-slate-50">
        <div class="w-full px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto">
        <div class="w-full max-w-[768px] px-10 py-8 bg-white rounded-lg flex flex-col justify-start items-center gap-5">
            <div class="w-full flex flex-col justify-start items-center gap-2">
                <h2 class="text-gray-900 text-3xl font-bold">Daftarkan Dirimu</h2>
                <p class="text-center text-slate-600 text-base font-normal">
                    Setiap tetes darah yang kamu donorkan berarti harapan dan kehidupan bagi mereka yang membutuhkan
                </p>
            </div>
            
            <div class="w-full h-0 border-t border-slate-200"></div>
            
            <?php if ($error): ?>
                <div class="w-full p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="w-full p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="w-full flex flex-col justify-start items-center gap-4">
                <!-- Email -->
                <div class="w-full flex flex-col justify-start items-start gap-2">
                    <label class="text-slate-600 text-base font-normal">Email</label>
                    <input type="email" 
                           name="email" 
                           required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                </div>
                
                <!-- Password -->
                <div class="w-full flex flex-col justify-start items-start gap-2">
                    <label class="text-slate-600 text-base font-normal">Password</label>
                    <input type="password" 
                           name="password" 
                           required
                           minlength="6"
                           class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                </div>
                
                <!-- Full Name -->
                <div class="w-full flex flex-col justify-start items-start gap-2">
                    <label class="text-slate-600 text-base font-normal">Nama Lengkap</label>
                    <input type="text" 
                           name="full_name" 
                           required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                           class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                </div>
                
                <!-- Blood Type -->
                <div class="w-full flex flex-col justify-start items-start gap-2">
                    <label class="text-slate-600 text-base font-normal">Golongan Darah</label>
                    <div class="w-full flex justify-start items-start gap-2">
                        <select name="blood_type_abo" 
                                required
                                class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                            <option value="">Pilih ABO</option>
                            <option value="A" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'A') ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="AB" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'AB') ? 'selected' : ''; ?>>AB</option>
                            <option value="O" <?php echo (isset($_POST['blood_type_abo']) && $_POST['blood_type_abo'] === 'O') ? 'selected' : ''; ?>>O</option>
                        </select>
                        
                        <select name="blood_type_rhesus" 
                                required
                                class="flex-1 px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                            <option value="">Pilih Rhesus</option>
                            <option value="+" <?php echo (isset($_POST['blood_type_rhesus']) && $_POST['blood_type_rhesus'] === '+') ? 'selected' : ''; ?>>Positif (+)</option>
                            <option value="-" <?php echo (isset($_POST['blood_type_rhesus']) && $_POST['blood_type_rhesus'] === '-') ? 'selected' : ''; ?>>Negatif (-)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="w-full flex flex-col justify-start items-start gap-2">
                    <label class="text-slate-600 text-base font-normal">Nomor WhatsApp</label>
                    <input type="tel" 
                           name="phone" 
                           required
                           placeholder="Contoh: 085xxxxxxxx"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                </div>
                
                <!-- Location -->
                <div class="w-full flex flex-col justify-start items-start gap-4">
                    <label class="text-slate-600 text-base font-normal">Domisili</label>
                    
                    <!-- Map Controls -->
                    <div class="w-full flex flex-col gap-3">
                        <div class="flex gap-2">
                            <button type="button" id="use-current-location" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                </svg>
                                Gunakan Lokasi Saya
                            </button>
                            <button type="button" id="toggle-map" 
                                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                Pilih di Peta
                            </button>
                        </div>
                        
                        <!-- Map Container -->
                        <div id="map-container" class="hidden">
                            <div id="map" class="w-full h-80 rounded-lg border border-slate-300"></div>
                            <p class="text-sm text-slate-600 mt-2">Klik pada peta untuk memilih lokasi Anda</p>
                        </div>
                        
                        <!-- Coordinate Display -->
                        <div id="location-info" class="hidden">
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-slate-700">
                                    <strong>Koordinat:</strong> <span id="coordinates"></span>
                                </p>
                                <p class="text-sm text-slate-700">
                                    <strong>Status:</strong> <span id="location-status">Lokasi belum dipilih</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden inputs for coordinates -->
                    <input type="hidden" id="latitude" name="latitude" value="">
                    <input type="hidden" id="longitude" name="longitude" value="">
                    
                    <!-- Address Field (Read-only) -->
                    <div class="w-full flex flex-col justify-start items-start gap-2">
                        <label class="text-slate-600 text-base font-normal">Alamat</label>
                        <input type="text" 
                               id="address-input"
                               name="address" 
                               readonly
                               placeholder="Alamat akan terisi otomatis setelah memilih lokasi"
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                    </div>
                    
                    <!-- Province and City Fields (Read-only) -->
                    <div class="w-full flex justify-start items-start gap-2">
                        <div class="flex-1 flex flex-col justify-start items-start gap-2">
                            <label class="text-slate-600 text-base font-normal">Provinsi</label>
                            <input type="text" 
                                   id="province-input"
                                   name="province" 
                                   readonly
                                   required
                                   placeholder="Akan terisi otomatis"
                                   value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                        </div>
                        
                        <div class="flex-1 flex flex-col justify-start items-start gap-2">
                            <label class="text-slate-600 text-base font-normal">Kota/Kabupaten</label>
                            <input type="text" 
                                   id="city-input"
                                   name="city" 
                                   readonly
                                   required
                                   placeholder="Akan terisi otomatis"
                                   value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>"
                                   class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-gray-50 text-gray-700 cursor-not-allowed">
                        </div>
                    </div>
                    
                    <div class="w-full p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Catatan:</strong> Silakan gunakan tombol "Gunakan Lokasi Saya" atau "Pilih di Peta" untuk mengisi data lokasi Anda.
                        </p>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-8 py-3 bg-red-500 rounded-full text-slate-50 text-base font-semibold hover:bg-red-600 transition-colors">
                    Daftar
                </button>
            </form>
            
            <div class="text-center">
                <span class="text-slate-600 text-base font-normal">Sudah punya akun? </span>
                <a href="login.php" class="text-red-500 text-base font-semibold underline">Login</a>
            </div>
        </div>
        </div>
    </section>

<!-- Leaflet JS for OpenStreetMap -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const useLocationBtn = document.getElementById('use-current-location');
    const toggleMapBtn = document.getElementById('toggle-map');
    const mapContainer = document.getElementById('map-container');
    const locationInfo = document.getElementById('location-info');
    const coordinatesSpan = document.getElementById('coordinates');
    const locationStatus = document.getElementById('location-status');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const addressInput = document.getElementById('address-input');
    const provinceInput = document.getElementById('province-input');
    const cityInput = document.getElementById('city-input');
    
    // Values to restore after form submission with errors
    const selectedProvince = '<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>';
    const selectedCity = '<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>';
    const selectedAddress = '<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>';
    const selectedLat = '<?php echo isset($_POST['latitude']) ? $_POST['latitude'] : ''; ?>';
    const selectedLon = '<?php echo isset($_POST['longitude']) ? $_POST['longitude'] : ''; ?>';
    
    let map = null;
    let marker = null;
    let isMapVisible = false;
    
    // Restore values if form was submitted with errors
    if (selectedLat && selectedLon && selectedAddress) {
        restoreFormValues();
    }
    
    // Toggle map visibility
    toggleMapBtn.addEventListener('click', function() {
        isMapVisible = !isMapVisible;
        if (isMapVisible) {
            mapContainer.classList.remove('hidden');
            toggleMapBtn.textContent = 'Sembunyikan Peta';
            initializeMap();
        } else {
            mapContainer.classList.add('hidden');
            toggleMapBtn.textContent = 'Pilih di Peta';
        }
    });
    
    // Use current location
    useLocationBtn.addEventListener('click', function() {
        if (navigator.geolocation) {
            useLocationBtn.disabled = true;
            useLocationBtn.innerHTML = `
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Mendapatkan Lokasi...
            `;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    setLocationFromCoordinates(lat, lon);
                    
                    useLocationBtn.disabled = false;
                    useLocationBtn.innerHTML = `
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        Gunakan Lokasi Saya
                    `;
                },
                function(error) {
                    useLocationBtn.disabled = false;
                    useLocationBtn.innerHTML = `
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
        } else {
            alert('Geolocation tidak didukung oleh browser ini.');
        }
    });
    
    function initializeMap() {
        if (map) return; // Map already initialized
        
        // Initialize map centered on Indonesia
        map = L.map('map').setView([-2.5, 118], 5);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);
        
        // Add click event to map
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lon = e.latlng.lng;
            setLocationFromCoordinates(lat, lon);
        });
        
        // If we have coordinates from form errors, show them on map
        if (selectedLat && selectedLon) {
            const lat = parseFloat(selectedLat);
            const lon = parseFloat(selectedLon);
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lon]).addTo(map);
            map.setView([lat, lon], 13);
        }
    }
    
    function setLocationFromCoordinates(lat, lon) {
        // Update coordinates display
        coordinatesSpan.textContent = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
        latInput.value = lat;
        lonInput.value = lon;
        
        // Show location info
        locationInfo.classList.remove('hidden');
        locationStatus.textContent = 'Memuat informasi lokasi...';
        
        // Clear form fields while loading
        addressInput.value = 'Memuat alamat...';
        provinceInput.value = 'Memuat...';
        cityInput.value = 'Memuat...';
        
        // Add/update marker on map if visible
        if (map) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lon]).addTo(map);
            map.setView([lat, lon], 13);
        }
        
        // Get address and administrative data
        fetch(`/handler/reverse_geocode?lat=${lat}&lon=${lon}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Fill form fields
                    addressInput.value = data.data.address;
                    provinceInput.value = data.data.province.name;
                    cityInput.value = data.data.regency.name;
                    
                    locationStatus.textContent = 'Lokasi berhasil dipilih';
                } else {
                    locationStatus.textContent = 'Gagal memuat informasi: ' + data.message;
                    addressInput.value = '';
                    provinceInput.value = '';
                    cityInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                locationStatus.textContent = 'Terjadi kesalahan saat memuat informasi lokasi';
                addressInput.value = '';
                provinceInput.value = '';
                cityInput.value = '';
            });
    }
    
    function restoreFormValues() {
        // Restore form values from PHP (after form submission with errors)
        coordinatesSpan.textContent = `${selectedLat}, ${selectedLon}`;
        latInput.value = selectedLat;
        lonInput.value = selectedLon;
        addressInput.value = selectedAddress;
        provinceInput.value = selectedProvince;
        cityInput.value = selectedCity;
        locationInfo.classList.remove('hidden');
        locationStatus.textContent = 'Lokasi tersimpan dari form sebelumnya';
    }
});
</script>

<?php include 'layout/footer.php'; ?>