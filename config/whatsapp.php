<?php
require_once __DIR__ . '/envloader.php';

/**
 * Send WhatsApp message using Fonnte API
 * 
 * @param string|array $target Phone number(s) to send message to
 * @param string $message Message content
 * @param string $delay Delay between messages (default: '2')
 * @param string $countryCode Country code (default: '62' for Indonesia)
 * @return array Response from Fonnte API
 */
function sendWhatsAppMessage($target, $message, $delay = '2', $countryCode = '62') {
    // If target is an array, convert to comma-separated string
    if (is_array($target)) {
        $target = implode(',', $target);
    }
    
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $message,
            'delay' => $delay,
            'countryCode' => $countryCode,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $_ENV['FONNTE_API_TOKEN']
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    // Log the response for debugging
    error_log("WhatsApp API Response: " . $response);
    
    if ($error) {
        error_log("WhatsApp API Error: " . $error);
        return ['status' => 'error', 'message' => $error];
    }
    
    return json_decode($response, true) ?: ['status' => 'error', 'message' => 'Invalid response'];
}
?>
