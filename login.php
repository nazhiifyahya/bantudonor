<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

// Set page variables for header template
$pageTitle = 'Login - BantuDonor';
$currentPage = 'login';

$error = '';

if (!empty($_SESSION['user_id'])){
        // User is already logged in, redirect to dashboard
        header('Location: dashboard.php');
        exit;
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userModel = new User();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $user = $userModel->verifyPassword($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

// Include header template
include 'layout/header.php';
?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Masuk ke Akun Anda
        </h2>

        <?php if ($error): ?>
                <div class="w-full p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm -space-y-px">
                <div class="mb-4">
                    <label for="email" class="sr-only">Email address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm"
                        placeholder="Email address">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input name="password" type="password" required
                        class="appearance-none rounded relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-red-500 focus:border-red-500 focus:z-10 sm:text-sm"
                        placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-full text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Login
                </button>
            </div>
        </form>
        <p class="mt-2 text-center text-sm text-gray-600">
            Belum punya akun?
            <a href="register.php" class="font-medium text-red-600 hover:text-red-500">Daftar sekarang</a>
        </p>
    </div>
</div>

<?php include 'layout/footer.php'; ?>