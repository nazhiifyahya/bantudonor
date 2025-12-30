<?php
    require_once '../config/envloader.php';
    require_once '../models/User.php';

    $update = extract($_POST);
    if ($update) {
        $phone = $update['phone'];
        $message = trim($update['message']);

        $userToken = $message;

        if ($userToken) {
            // Validasi token di database
            $userModel = new User();
            $volunteer = $userModel->getUserByUniqueToken($userToken);

            if ($volunteer) {

                // Simpan phone di database untuk user terkait
                $userModel->updateProfile([
                    'id' => $volunteer['id'],
                    'phone' => $phone
                ]);
                
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
                'target' => $phone,
                'message' => 'Berhasil menghubungkan akun Anda. Ubah pengaturan akun anda untuk berhenti berlangganan', 
                'countryCode' => '62', //optional
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $_ENV['FONNTE_API_TOKEN']  
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                echo $response;
                
            } else {
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
                'target' => $phone,
                'message' => 'Halo, gunakan pengaturan akun anda untuk menghubungkan akun', 
                'countryCode' => '62', //optional
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $_ENV['FONNTE_API_TOKEN']  
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                echo $response;
            }
        } else {
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
            'target' => $phone,
            'message' => 'Halo, gunakan pengaturan akun anda untuk menghubungkan akun', 
            'countryCode' => '62', //optional
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $_ENV['FONNTE_API_TOKEN']  
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        }
    }
?>