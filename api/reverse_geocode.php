<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $lat = $_GET['lat'] ?? '';
    $lon = $_GET['lon'] ?? '';
    
    if (empty($lat) || empty($lon)) {
        throw new Exception('Latitude dan longitude diperlukan');
    }
    
    // Validate coordinates
    if (!is_numeric($lat) || !is_numeric($lon)) {
        throw new Exception('Koordinat tidak valid');
    }
    
    // Check if coordinates are within Indonesia bounds (approximate)
    if ($lat < -11 || $lat > 6 || $lon < 95 || $lon > 141) {
        throw new Exception('Koordinat berada di luar wilayah Indonesia');
    }
    
    // Get address and administrative data from Nominatim (OpenStreetMap)
    $nominatimUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=10&addressdetails=1&accept-language=id,en";
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: BantuDonor-App/1.0\r\n",
            'timeout' => 15,
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);
    
    $nominatimResponse = @file_get_contents($nominatimUrl, false, $context);
    
    // Check for HTTP errors
    if ($nominatimResponse === false) {
        $error = error_get_last();
        throw new Exception('Tidak dapat mengakses layanan geocoding: ' . ($error['message'] ?? 'Koneksi bermasalah'));
    }
    
    // Check if we got a valid response
    if (empty($nominatimResponse)) {
        throw new Exception('Layanan geocoding mengembalikan respon kosong. Silakan coba lagi.');
    }
    
    $nominatimData = json_decode($nominatimResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($nominatimData['display_name'])) {
        throw new Exception('Gagal memproses data dari layanan geocoding');
    }
    
    // Extract address components
    $address = $nominatimData['display_name'];
    $addressDetails = $nominatimData['address'] ?? [];
    
    // Extract province and regency from address details
    $province = '';
    $regency = '';
    
    // Try to get province (state) - prioritize more specific fields
    if (isset($addressDetails['state'])) {
        $province = $addressDetails['state'];
    } elseif (isset($addressDetails['province'])) {
        $province = $addressDetails['province'];
    } elseif (isset($addressDetails['region'])) {
        $province = $addressDetails['region'];
    } elseif (isset($addressDetails['administrative_area_level_1'])) {
        $province = $addressDetails['administrative_area_level_1'];
    }
    
    // Try to get regency/city - prioritize administrative divisions over residential areas
    if (isset($addressDetails['city'])) {
        $regency = $addressDetails['city'];
    } elseif (isset($addressDetails['county'])) {
        $regency = $addressDetails['county'];
    } elseif (isset($addressDetails['town'])) {
        $regency = $addressDetails['town'];
    }
    
    // Normalize Indonesian province names (handle common variations)
    $province = normalizeProvinceName($province);
    $regency = normalizeRegencyName($regency);
    
    // Validate that we got the required information
    if (empty($province) || $province === 'Provinsi tidak dapat ditentukan') {
        // Try to extract from display_name as fallback
        $addressParts = array_map('trim', explode(',', $address));
        if (count($addressParts) >= 2) {
            $possibleProvince = end($addressParts);
            $province = normalizeProvinceName($possibleProvince);
        }
        
        if (empty($province) || $province === 'Provinsi tidak dapat ditentukan') {
            throw new Exception('Tidak dapat menentukan provinsi untuk lokasi ini. Pastikan lokasi berada di wilayah Indonesia.');
        }
    }
    
    if (empty($regency) || $regency === 'Kota/Kabupaten tidak dapat ditentukan') {
        // Try to extract from display_name as fallback
        $addressParts = array_map('trim', explode(',', $address));
        if (count($addressParts) >= 3) {
            $possibleRegency = $addressParts[count($addressParts) - 2];
            $regency = normalizeRegencyName($possibleRegency);
        }
        
        if (empty($regency) || $regency === 'Kota/Kabupaten tidak dapat ditentukan') {
            throw new Exception('Tidak dapat menentukan kota/kabupaten untuk lokasi ini. Pastikan lokasi berada di wilayah Indonesia.');
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'latitude' => (float)$lat,
            'longitude' => (float)$lon,
            'province' => [
                'name' => $province
            ],
            'regency' => [
                'name' => $regency
            ],
            'address' => $address
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Normalize Indonesian province names
 */
function normalizeProvinceName($province) {
    if (empty($province)) return '';
    
    // Clean and standardize the input
    $province = trim($province);
    $originalProvince = $province;
    $province = strtoupper($province);
    
    // Remove common prefixes that might appear in OSM data
    $province = preg_replace('/^(PROVINSI|PROVINCE|PROV\.?)\s+/i', '', $province);
    $province = preg_replace('/^(DAERAH KHUSUS|SPECIAL REGION|SPECIAL CAPITAL REGION)\s+/i', '', $province);
    
    // Handle common variations and mappings
    $provinceMap = [
        'JAKARTA' => 'DKI JAKARTA',
        'JAKARTA RAYA' => 'DKI JAKARTA',
        'DKI' => 'DKI JAKARTA',
        'JAKARTA SPECIAL CAPITAL REGION' => 'DKI JAKARTA',
        'SPECIAL CAPITAL REGION OF JAKARTA' => 'DKI JAKARTA',
        'CAPITAL REGION OF JAKARTA' => 'DKI JAKARTA',
        
        'YOGYAKARTA' => 'DI YOGYAKARTA',
        'YOGYA' => 'DI YOGYAKARTA',
        'SPECIAL REGION OF YOGYAKARTA' => 'DI YOGYAKARTA',
        'DAERAH ISTIMEWA YOGYAKARTA' => 'DI YOGYAKARTA',
        
        'BANGKA BELITUNG' => 'KEPULAUAN BANGKA BELITUNG',
        'BABEL' => 'KEPULAUAN BANGKA BELITUNG',
        'BANGKA BELITUNG ISLANDS' => 'KEPULAUAN BANGKA BELITUNG',
        
        'KEPRI' => 'KEPULAUAN RIAU',
        'RIAU ISLANDS' => 'KEPULAUAN RIAU',
        'RIAU ARCHIPELAGO' => 'KEPULAUAN RIAU',
        
        'WEST JAVA' => 'JAWA BARAT',
        'JAWA BARAT' => 'JAWA BARAT',
        'JABAR' => 'JAWA BARAT',
        
        'CENTRAL JAVA' => 'JAWA TENGAH',
        'JAWA TENGAH' => 'JAWA TENGAH',
        'JATENG' => 'JAWA TENGAH',
        
        'EAST JAVA' => 'JAWA TIMUR',
        'JAWA TIMUR' => 'JAWA TIMUR',
        'JATIM' => 'JAWA TIMUR',
        
        'NORTH SUMATRA' => 'SUMATERA UTARA',
        'SUMATERA UTARA' => 'SUMATERA UTARA',
        'SUMUT' => 'SUMATERA UTARA',
        
        'WEST SUMATRA' => 'SUMATERA BARAT',
        'SUMATERA BARAT' => 'SUMATERA BARAT',
        'SUMBAR' => 'SUMATERA BARAT',
        
        'SOUTH SUMATRA' => 'SUMATERA SELATAN',
        'SUMATERA SELATAN' => 'SUMATERA SELATAN',
        'SUMSEL' => 'SUMATERA SELATAN',
        
        'RIAU' => 'RIAU',
        
        'JAMBI' => 'JAMBI',
        
        'BENGKULU' => 'BENGKULU',
        
        'LAMPUNG' => 'LAMPUNG',
        
        'WEST KALIMANTAN' => 'KALIMANTAN BARAT',
        'KALIMANTAN BARAT' => 'KALIMANTAN BARAT',
        'KALBAR' => 'KALIMANTAN BARAT',
        
        'CENTRAL KALIMANTAN' => 'KALIMANTAN TENGAH',
        'KALIMANTAN TENGAH' => 'KALIMANTAN TENGAH',
        'KALTENG' => 'KALIMANTAN TENGAH',
        
        'SOUTH KALIMANTAN' => 'KALIMANTAN SELATAN',
        'KALIMANTAN SELATAN' => 'KALIMANTAN SELATAN',
        'KALSEL' => 'KALIMANTAN SELATAN',
        
        'EAST KALIMANTAN' => 'KALIMANTAN TIMUR',
        'KALIMANTAN TIMUR' => 'KALIMANTAN TIMUR',
        'KALTIM' => 'KALIMANTAN TIMUR',
        
        'NORTH KALIMANTAN' => 'KALIMANTAN UTARA',
        'KALIMANTAN UTARA' => 'KALIMANTAN UTARA',
        'KALUT' => 'KALIMANTAN UTARA',
        
        'NORTH SULAWESI' => 'SULAWESI UTARA',
        'SULAWESI UTARA' => 'SULAWESI UTARA',
        'SULUT' => 'SULAWESI UTARA',
        
        'CENTRAL SULAWESI' => 'SULAWESI TENGAH',
        'SULAWESI TENGAH' => 'SULAWESI TENGAH',
        'SULTENG' => 'SULAWESI TENGAH',
        
        'SOUTH SULAWESI' => 'SULAWESI SELATAN',
        'SULAWESI SELATAN' => 'SULAWESI SELATAN',
        'SULSEL' => 'SULAWESI SELATAN',
        
        'SOUTHEAST SULAWESI' => 'SULAWESI TENGGARA',
        'SULAWESI TENGGARA' => 'SULAWESI TENGGARA',
        'SULTRA' => 'SULAWESI TENGGARA',
        
        'WEST SULAWESI' => 'SULAWESI BARAT',
        'SULAWESI BARAT' => 'SULAWESI BARAT',
        'SULBAR' => 'SULAWESI BARAT',
        
        'GORONTALO' => 'GORONTALO',
        
        'WEST NUSA TENGGARA' => 'NUSA TENGGARA BARAT',
        'NUSA TENGGARA BARAT' => 'NUSA TENGGARA BARAT',
        'NTB' => 'NUSA TENGGARA BARAT',
        
        'EAST NUSA TENGGARA' => 'NUSA TENGGARA TIMUR',
        'NUSA TENGGARA TIMUR' => 'NUSA TENGGARA TIMUR',
        'NTT' => 'NUSA TENGGARA TIMUR',
        
        'BALI' => 'BALI',
        
        'MALUKU' => 'MALUKU',
        
        'NORTH MALUKU' => 'MALUKU UTARA',
        'MALUKU UTARA' => 'MALUKU UTARA',
        'MALUT' => 'MALUKU UTARA',
        
        'WEST PAPUA' => 'PAPUA BARAT',
        'PAPUA BARAT' => 'PAPUA BARAT',
        'PAPBAR' => 'PAPUA BARAT',
        
        'PAPUA' => 'PAPUA',
        
        'ACEH' => 'ACEH'
    ];
    
    // Try exact match first
    if (isset($provinceMap[$province])) {
        return $provinceMap[$province];
    }
    
    // Try partial match for cases where OSM might have different formatting
    foreach ($provinceMap as $key => $value) {
        if (strpos($province, $key) !== false || strpos($key, $province) !== false) {
            return $value;
        }
    }
    
    // If no match found, return the cleaned version (remove prefixes but keep original case structure)
    $cleaned = preg_replace('/^(Provinsi|Province|Prov\.?|Daerah Khusus|Special Region|Special Capital Region)\s+/i', '', $originalProvince);
    return trim($cleaned);
}

/**
 * Normalize Indonesian regency/city names
 */
function normalizeRegencyName($regency) {
    if (empty($regency)) return '';
    
    $originalRegency = trim($regency);
    $regency = strtoupper($originalRegency);
    
    // Remove common prefixes if they exist
    $cleanRegency = preg_replace('/^(KOTA|KABUPATEN|KAB\.?|CITY|REGENCY)\s+/i', '', $regency);
    
    // List of major cities in Indonesia (these should get KOTA prefix)
    $cities = [
        'JAKARTA', 'SURABAYA', 'BANDUNG', 'MEDAN', 'SEMARANG', 'MAKASSAR', 
        'PALEMBANG', 'TANGERANG', 'DEPOK', 'BEKASI', 'PADANG', 'DENPASAR',
        'MALANG', 'SAMARINDA', 'BANJARMASIN', 'TASIKMALAYA', 'PONTIANAK',
        'CIMAHI', 'BALIKPAPAN', 'JAMBI', 'SURAKARTA', 'MANADO', 'YOGYAKARTA',
        'PEKANBARU', 'BANDAR LAMPUNG', 'KEDIRI', 'AMBON', 'BLITAR', 'MADIUN',
        'PROBOLINGGO', 'MOJOKERTO', 'MAGELANG', 'BUKITTINGGI', 'BENGKULU',
        'GORONTALO', 'KENDARI', 'PALU', 'MATARAM', 'KUPANG', 'SORONG',
        'JAYAPURA', 'DUMAI', 'TERNATE', 'SABANG', 'LANGSA', 'LHOKSEUMAWE',
        'SUBULUSSALAM', 'TEBING TINGGI', 'PEMATANGSIANTAR', 'SIBOLGA',
        'TANJUNGBALAI', 'BINJAI', 'PADANGSIDIMPUAN', 'GUNUNGSITOLI',
        'BATAM', 'TANJUNG PINANG', 'PANGKAL PINANG', 'CILEGON', 'SERANG',
        'TANGERANG SELATAN', 'BOGOR', 'SUKABUMI', 'CIREBON', 'TEGAL',
        'PEKALONGAN', 'SALATIGA', 'BATU', 'PASURUAN', 'BONTANG', 'TARAKAN',
        'TOMOHON', 'KOTAMOBAGU', 'BITUNG', 'PAREPARE', 'PALOPO', 'BAUBAU',
        'SOLOK', 'SAWAH LUNTO', 'PADANG PANJANG', 'PAYAKUMBUH', 'LUBUKLINGGAU',
        'PAGAR ALAM', 'PRABUMULIH', 'BANDAR LAMPUNG', 'METRO', 'SINGKAWANG',
        'TANJUNG SELOR', 'TIDORE KEPULAUAN', 'SOFIFI'
    ];
    
    // Special mappings for common variations
    $regencyMappings = [
        'JAKARTA PUSAT' => 'JAKARTA PUSAT',
        'JAKARTA UTARA' => 'JAKARTA UTARA', 
        'JAKARTA BARAT' => 'JAKARTA BARAT',
        'JAKARTA SELATAN' => 'JAKARTA SELATAN',
        'JAKARTA TIMUR' => 'JAKARTA TIMUR',
        'THOUSAND ISLANDS' => 'KEPULAUAN SERIBU',
        'KEPULAUAN SERIBU' => 'KEPULAUAN SERIBU',
        'YOGYA' => 'YOGYAKARTA',
        'SOLO' => 'SURAKARTA',
        'UJUNG PANDANG' => 'MAKASSAR'
    ];
    
    // Check for special mappings first
    if (isset($regencyMappings[$cleanRegency])) {
        $cleanRegency = $regencyMappings[$cleanRegency];
    }
    
    // Determine if it should be a city (KOTA) or regency (KABUPATEN)
    $isCity = false;
    
    // Check if it's in the list of known cities
    if (in_array($cleanRegency, $cities)) {
        $isCity = true;
    }
    
    // Check for partial matches with known cities
    if (!$isCity) {
        foreach ($cities as $city) {
            if (strpos($cleanRegency, $city) !== false || strpos($city, $cleanRegency) !== false) {
                $isCity = true;
                $cleanRegency = $city; // Use the standardized city name
                break;
            }
        }
    }
    
    // Check if original already had KOTA prefix
    if (!$isCity && preg_match('/^(KOTA|CITY)\s+/i', $originalRegency)) {
        $isCity = true;
    }
    
    // Special handling for Jakarta sub-districts
    if (strpos($cleanRegency, 'JAKARTA') !== false) {
        return $cleanRegency; // Jakarta sub-districts don't need KOTA prefix
    }
    
    // Apply appropriate prefix
    if ($isCity) {
        return 'KOTA ' . $cleanRegency;
    } else {
        return 'KABUPATEN ' . $cleanRegency;
    }
}
?>