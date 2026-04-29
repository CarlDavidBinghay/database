<?php
session_start();
require_once('../includes/users_store.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName       = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $lastName        = trim(filter_input(INPUT_POST, 'last_name',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $email           = trim(filter_input(INPUT_POST, 'email',      FILTER_SANITIZE_EMAIL) ?? '');
    $phone           = trim(filter_input(INPUT_POST, 'phone',      FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_POST['terms'])) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        try {
            if (emailExists($email)) {
                $error = 'An account with that email already exists. <a href="login.php" class="underline font-medium">Login instead?</a>';
            } else {
                $newId = registerUser($firstName, $lastName, $email, $phone, $password);
                session_regenerate_id(true);
                $_SESSION['user_id']      = $newId;
                $_SESSION['user_email']   = $email;
                $_SESSION['user_name']    = $firstName . ' ' . $lastName;
                $_SESSION['user_role']    = 'user';
                $_SESSION['logged_in_at'] = time();
                $_SESSION['success']      = 'Welcome to AquaQueue, ' . htmlspecialchars($firstName) . '!';
                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log('[AquaQueue] register: ' . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
include('../includes/header.php');
?>
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <div class="flex justify-center mb-2">
        <div class="bg-[#A6E3E9] w-16 h-16 rounded-xl flex items-center justify-center">
          <i class="fas fa-user-plus text-[#71C9CE] text-2xl"></i>
        </div>
      </div>
      <h2 class="mt-4 text-center text-3xl font-bold text-gray-900">Create your account</h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        Or <a href="login.php" class="font-semibold text-[#71C9CE] hover:text-[#5ab4b9]">sign in to existing account</a>
      </p>
      <?php if ($error): ?>
      <div class="mt-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle flex-shrink-0"></i><?php echo $error; ?>
      </div>
      <?php endif; ?>
      <form class="mt-7 space-y-5" method="POST" action="register.php">
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name</label>
              <input name="first_name" type="text" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
                placeholder="First Name">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
              <input name="last_name" type="text" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
                placeholder="Last Name">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
              class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
              placeholder="Enter your email">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
            <input name="phone" type="tel" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
              class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
              placeholder="+63 (917) 000-0000">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
            <div class="relative">
              <input id="password" name="password" type="password" required autocomplete="new-password"
                class="appearance-none block w-full px-4 py-3 pr-10 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
                placeholder="Create a password" oninput="checkStrength()">
              <button type="button" onclick="togglePass('password','eye-1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#71C9CE]"><i class="fas fa-eye text-sm" id="eye-1"></i></button>
            </div>
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden"><div id="strength-bar" class="h-full rounded-full transition-all" style="width:0%"></div></div>
            <p id="strength-label" class="text-xs mt-1 text-gray-400">Must be at least 8 characters</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
            <div class="relative">
              <input id="confirm-password" name="confirm_password" type="password" required autocomplete="new-password"
                class="appearance-none block w-full px-4 py-3 pr-10 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
                placeholder="Re-enter your password" oninput="checkMatch()">
              <button type="button" onclick="togglePass('confirm-password','eye-2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#71C9CE]"><i class="fas fa-eye text-sm" id="eye-2"></i></button>
            </div>
            <p id="match-msg" class="text-xs mt-1 hidden"></p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 mt-0.5 text-[#71C9CE] border-gray-300 rounded">
          <label for="terms" class="text-sm text-gray-600">I agree to the <a href="Terms.php" class="font-medium text-[#71C9CE]">Terms of Service</a> and <a href="Privacy.php" class="font-medium text-[#71C9CE]">Privacy Policy</a></label>
        </div>
        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 text-sm font-semibold rounded-xl text-white bg-[#71C9CE] hover:bg-[#5ab4b9] transition-all shadow-md">
          <i class="fas fa-user-plus mr-2"></i>Create Account
        </button>
        <div class="text-center"><p class="text-sm text-gray-600">Already have an account? <a href="login.php" class="font-semibold text-[#71C9CE]">Sign in</a></p></div>
      </form>
    </div>
  </div>
</div>
<script>
function togglePass(id,eid){const i=document.getElementById(id),e=document.getElementById(eid);i.type=i.type==='password'?'text':'password';e.className=i.type==='text'?'fas fa-eye-slash text-sm':'fas fa-eye text-sm';}
function checkStrength(){const pw=document.getElementById('password').value,bar=document.getElementById('strength-bar'),lbl=document.getElementById('strength-label');let s=0;if(pw.length>=8)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;const l=[{w:'0%',c:'#e5e7eb',t:'Min 8 chars',tc:'#9ca3af'},{w:'25%',c:'#ef4444',t:'Weak',tc:'#ef4444'},{w:'50%',c:'#f59e0b',t:'Fair',tc:'#f59e0b'},{w:'75%',c:'#3b82f6',t:'Good',tc:'#3b82f6'},{w:'100%',c:'#22c55e',t:'Strong ✓',tc:'#22c55e'}][pw.length===0?0:s];bar.style.width=l.w;bar.style.background=l.c;lbl.textContent=l.t;lbl.style.color=l.tc;checkMatch();}
function checkMatch(){const pw=document.getElementById('password').value,cp=document.getElementById('confirm-password').value,msg=document.getElementById('match-msg');if(!cp){msg.classList.add('hidden');return;}msg.textContent=pw===cp?'✓ Passwords match':'✗ Passwords do not match';msg.style.color=pw===cp?'#22c55e':'#ef4444';msg.classList.remove('hidden');}
</script>
<?php include('../includes/footer.php'); ?>
