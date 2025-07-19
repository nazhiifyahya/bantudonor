<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/envloader.php';

try {
    $lat = $_GET['lat'] ?? '';
    $lon = $_GET['lon'] ?? '';
    $apiKey = $_ENV['GEOAPIFY_API_KEY'];
    
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
    
    // Get address and administrative data from geoapify (OpenStreetMap)
    $geoapifyUrl = "https://api.geoapify.com/v1/geocode/reverse?lat={$lat}&lon={$lon}&format=json&lang=id&apiKey={$apiKey}";
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'method' => 'GET',
        ]
    ]);
    
    $geoapifyResponse = @file_get_contents($geoapifyUrl, false, $context);
    
    // Check for HTTP errors
    if ($geoapifyResponse === false) {
        $error = error_get_last();
        throw new Exception('Tidak dapat mengakses layanan geocoding: ' . ($error['message'] ?? 'Koneksi bermasalah'));
    }
    
    // Check if we got a valid response
    if (empty($geoapifyResponse)) {
        throw new Exception('Layanan geocoding mengembalikan respon kosong. Silakan coba lagi.');
    }
    
    $geoapifyData = json_decode($geoapifyResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($geoapifyData['results'])) {
        throw new Exception('Gagal memproses data dari layanan geocoding');
    }
    
    // Extract address components
    $address = $geoapifyData['results'][0]['address_line2'];
    
    // Extract province and regency from address details
    $province = $geoapifyData['results'][0]['state'];
    $regency = $geoapifyData['results'][0]['county'] ?? '';

    
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
?>