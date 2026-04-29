<?php
session_start();
require_once('../includes/users_store.php');

if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['user_role'] ?? 'user';
    header('Location: ' . (in_array($r,['admin','developer']) ? '../admin/dashboard.php'
        : ($r==='service_admin' ? '../admin/manage-queue.php' : 'index.php')));
    exit();
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $user = verifyUser($email, $password);
    } catch (PDOException $e) {
        $error = 'Database error. Please try again later.';
        error_log($e->getMessage());
        $user = false;
    }

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_email']   = $email;
        $_SESSION['user_name']    = userDisplayName($user);
        $_SESSION['user_role']    = $user['role'];
        $_SESSION['logged_in_at'] = time();
        if ($user['role'] === 'service_admin') {
            $_SESSION['assigned_service'] = $user['assigned_service'] ?? null;
        }
        switch ($user['role']) {
            case 'developer': case 'admin':
                header('Location: ../admin/dashboard.php'); break;
            case 'service_admin':
                header('Location: ../admin/manage-queue.php'); break;
            default:
                header('Location: index.php');
        }
        exit();
    } elseif (!$error) {
        $error = 'Invalid email or password.';
    }
}
if (!$error && isset($_SESSION['error'])) { $error = $_SESSION['error']; unset($_SESSION['error']); }
$pageTitle = 'Login';
include('../includes/header.php');
?>
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <div class="flex justify-center mb-2">
        <div class="bg-[#A6E3E9] w-16 h-16 rounded-xl flex items-center justify-center">
          <i class="fas fa-sign-in-alt text-[#71C9CE] text-2xl"></i>
        </div>
      </div>
      <h2 class="mt-4 text-center text-3xl font-bold text-gray-900">Sign in to your account</h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Or <a href="register.php" class="font-semibold text-[#71C9CE] hover:text-[#5ab4b9]">create a new account</a>
      </p>
      <?php if ($error): ?>
      <div class="mt-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle flex-shrink-0"></i><?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['success'])): ?>
      <div class="mt-5 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
        <i class="fas fa-check-circle flex-shrink-0"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
      </div>
      <?php endif; ?>
      <div class="mt-7 space-y-3">
        <button type="button" onclick="alert('Google login coming soon!')" class="w-full flex justify-center items-center py-3 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all">
          <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          Continue with Google
        </button>
      </div>
      <div class="mt-6 relative">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center text-sm"><span class="px-4 bg-white text-gray-400">Or continue with email</span></div>
      </div>
      <form class="mt-6 space-y-5" method="POST" action="login.php">
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input id="email" name="email" type="email" required autocomplete="email"
              value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
              class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] focus:border-transparent transition"
              placeholder="Enter your email">
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <div class="relative">
              <input id="password" name="password" type="password" required autocomplete="current-password"
                class="appearance-none block w-full px-4 py-3 pr-10 border border-gray-300 rounded-xl placeholder-gray-400 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] focus:border-transparent transition"
                placeholder="Enter your password">
              <button type="button" onclick="togglePass()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#71C9CE]">
                <i class="fas fa-eye text-sm" id="pass-eye"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input name="remember-me" type="checkbox" class="h-4 w-4 text-[#71C9CE] border-gray-300 rounded">
            <span class="text-sm text-gray-600">Remember me</span>
          </label>
          <a href="forgotpass.php" class="text-sm font-medium text-[#71C9CE] hover:text-[#5ab4b9]">Forgot password?</a>
        </div>
        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 text-sm font-semibold rounded-xl text-white bg-[#71C9CE] hover:bg-[#5ab4b9] transition-all shadow-md hover:shadow-lg">
          <i class="fas fa-sign-in-alt mr-2"></i>Sign in
        </button>
        <div class="bg-[#f0fdfd] border border-[#A6E3E9] rounded-xl p-4 text-xs text-gray-600">
          <div class="font-semibold text-[#3aabb1] mb-2"><i class="fas fa-info-circle mr-1"></i>Demo Credentials</div>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div><strong>CB Admin:</strong> cb@aqua.com  12345678</div>
          </div>
          <button type="button" onclick="fillDemo('user')"  class="mt-2 text-[#71C9CE] hover:underline font-medium mr-3">Fill User</button>
          <button type="button" onclick="fillDemo('admin')" class="text-[#71C9CE] hover:underline font-medium">Fill Admin</button>
        </div>
        <div class="text-center pt-1">
          <p class="text-sm text-gray-600">Just want to browse? <a href="index.php" class="font-semibold text-[#71C9CE] hover:text-[#5ab4b9]">Continue as guest</a></p>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function togglePass(){const i=document.getElementById('password'),e=document.getElementById('pass-eye');i.type=i.type==='password'?'text':'password';e.className=i.type==='text'?'fas fa-eye-slash text-sm':'fas fa-eye text-sm';}
function fillDemo(t){const c={user:['user@test.com','password123'],admin:['admin@test.com','admin123']};document.getElementById('email').value=c[t][0];document.getElementById('password').value=c[t][1];}
</script>
<?php include('../includes/footer.php'); ?>
