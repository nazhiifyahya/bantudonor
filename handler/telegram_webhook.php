<?php
require_once __DIR__ . '/envloader.php';
require_once __DIR__ . '../modelds/User.php';
/**
 * Telegram Webhook Handler
 * BantuDonor Application
 */

use Telegram\Bot\Api;

$token = $_GET['token'] ?? null;

if (!$token || $token !== $_ENV['TELEGRAM_BOT_TOKEN']) {
    http_response_code(400);
    echo "Access denied.:";
    exit;
} else {
    $telegram = new Api($_ENV['TELEGRAM_BOT_TOKEN']);

    $update = $telegram->getWebhookUpdates();

    if ($update->getMessage()) {
        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $text = trim($message->getText());

        // Tangani command /start dengan token optional
        if (strpos($text, '/start') === 0) {
            $parts = explode(' ', $text, 2);
            $token = $parts[1] ?? null;

            if ($token) {
                // Validasi token di database
                $userModel = new User();
                $volunteer = $userModel->getUserByUniqueToken($token);

                if ($volunteer) {

                    // Simpan telegram_chat_id di database untuk user terkait
                    $userModel->updateProfile([
                        'id' => $volunteer['id'],
                        'telegram_chat_id' => $chatId
                    ]);

                    // Kirim balasan sukses
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Terima kasih! Akun Telegram Anda telah berhasil terhubung. Gunakan perintah /stop untuk berhenti berlangganan."
                    ]);
                } else {
                    // Token tidak valid
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Token validasi tidak dikenali. Pastikan Anda mengklik link yang benar."
                    ]);
                }
            } else {
                // /start tanpa token
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Selamat datang! Silakan gunakan link khusus untuk menghubungkan akun Anda."
                ]);
            }
        } elseif (strpos($text, '/stop') === 0) {
            // Tangani command /stop
            $userModel = new User();
            $user = $userModel->getUserByTelegramChatId($chatId);

            if ($user) {
                // Hapus telegram_chat_id dari user
                $userModel->updateProfile([
                    'id' => $user['id'],
                    'telegram_chat_id' => null
                ]);

                // Kirim balasan sukses
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Akun Telegram Anda telah berhasil dihapus dari sistem."
                ]);
            } else {
                // User tidak ditemukan
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Anda belum menghubungkan akun Telegram Anda."
                ]);
            }
        }
    }
}

?>