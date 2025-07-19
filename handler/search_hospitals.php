<?php
/**
 * Hospital Search API using OpenStreetMap geoapify
 * Searches for hospitals based on query and returns location data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/envloader.php';

// Check if query parameter is provided
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Query parameter required'
    ]);
    exit;
}

$query = trim($_GET['q']);
$apiKey = $_ENV['GEOAPIFY_API_KEY'];

// Add "hospital" keyword if not present
if (stripos($query, 'hospital') === false && 
    stripos($query, 'rumah sakit') === false && 
    stripos($query, 'rs ') === false &&
    stripos($query, 'klinik') === false) {
    $query .= ' hospital';
}

// Indonesia bounding box for better results
$boundingBox = 'rect:95,-11,141,6'; // West, South, East, North

// Build geoapify API URL
$geoapifyUrl = 'https://api.geoapify.com/v1/geocode/search?' . http_build_query([
    'apiKey' => $apiKey,
    'text' => $query,
    'format' => 'json',
    'limit' => 10,
    'filter' => $boundingBox,
    'lang' => 'id',
    'type' => 'amenity',
]);

try {
      // Make request to geoapify
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15
        ]
    ]);
    
    $geoapifyResponse = file_get_contents($geoapifyUrl, false, $context);
    
    if ($geoapifyResponse === false) {
        throw new Exception('Unable to connect to geocoding service');
    }
    
    $geoapifyData = json_decode($geoapifyResponse, true);
    
    if (!$geoapifyData) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No results found'
        ]);
        exit;
    }
    
    // Process and filter results
    $hospitals = [];
    
    foreach ($geoapifyData['results'] as $result) {
        // Filter for healthcare facilities
        $isHealthcare = false;
        
        // Check if there are category tags
        $category = $result["category"] ?? '';
        
        // Check if category contains healthcare keywords
        if (stripos('category', 'healthcare') !== false){
            $isHealthcare = true;
        }
    
        // Also check display name for hospital keywords
        if (!$isHealthcare) {
            $displayName = strtolower($result["name"]);
            if (stripos($displayName, 'hospital') !== false ||
                stripos($displayName, 'rumah sakit') !== false ||
                stripos($displayName, 'rs ') !== false ||
                stripos($displayName, 'klinik') !== false) {
                $isHealthcare = true;
            }
        }
        
        if ($isHealthcare) {
            // Extract address components
            $address = $result["address_line2"] ?? [];
            
            $province = $result["state"] ?? '';
            
            $city = $result["county"] ?? '';
            
            $hospitals[] = [
                'name' => $result["name"],
                'address' => $address,
                'latitude' => (float)$result["lat"],
                'longitude' => (float)$result["lon"],
                'city' => $city,
                'province' => $province,
            ];
        }
    }
    echo json_encode([
        'status' => 'success',
        'data' => $hospitals
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>