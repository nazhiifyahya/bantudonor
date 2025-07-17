<?php
session_start();
require_once 'models/User.php';

// Set page variables for header template
$pageTitle = 'Login - BantuDonor';
$currentPage = 'login';

$error = '';

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

    <!-- Login Form -->
    <section class="bg-slate-50">
        <div class="w-full h-[622px] px-20 py-16 flex flex-col justify-center items-center gap-10 max-w-[1280px] relative mx-auto">
        <div class="w-[560px] px-10 py-8 bg-white rounded-lg flex flex-col justify-start items-center gap-5">
            <div class="w-full flex flex-col justify-start items-center gap-2">
                <h2 class="text-gray-900 text-3xl font-bold">Login</h2>
                <p class="text-center text-slate-600 text-base font-normal">
                    Masuk ke akun Anda untuk mulai membantu sesama
                </p>
            </div>
            
            <div class="w-full h-0 border-t border-slate-200"></div>
            
            <?php if ($error): ?>
                <div class="w-full p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error); ?>
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
                           class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-red-500 focus:outline-none">
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-8 py-3 bg-red-500 rounded-full text-slate-50 text-base font-semibold hover:bg-red-600 transition-colors">
                    Login
                </button>
            </form>
            
            <div class="text-center">
                <span class="text-slate-600 text-base font-normal">Belum punya akun? </span>
                <a href="register.php" class="text-red-500 text-base font-semibold underline">Daftar Sekarang</a>
            </div>
        </div>
        </div>
    </section>

<?php include 'layout/footer.php'; ?>