<?php
session_start();
require_once('../includes/users_store.php');

$step  = 'request';
$error = null;
$token = $_GET['token'] ?? null;

// ── POST: submit email ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request') {
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    try {
        $user = findUserByEmail($email);
        if ($user) {
            $raw = createResetToken($email);
            $_SESSION['demo_reset_link']  = 'http://localhost/queue-system/public/forgotpass.php?token=' . urlencode($raw);
            $_SESSION['reset_email_hint'] = $email;
        }
    } catch (PDOException $e) { error_log($e->getMessage()); }
    $_SESSION['reset_sent'] = true;
    header('Location: forgotpass.php?step=sent'); exit();
}

// ── GET: show "sent" page ───────────────────────────────────────
if (isset($_GET['step']) && $_GET['step'] === 'sent') { $step = 'sent'; }

// ── GET: token in URL → show reset form ────────────────────────
if ($token && !isset($_POST['action'])) {
    $email = verifyResetToken($token);
    $step  = $email ? 'reset' : 'expired';
    if (!$email) $error = 'This reset link is invalid or has expired. Please request a new one.';
}

// ── POST: submit new password ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $token   = $_POST['token']            ?? '';
    $newPass = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $email   = verifyResetToken($token);
    if (!$email) {
        $error = 'This reset link has expired. Please request a new one.'; $step = 'expired';
    } elseif (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters.'; $step = 'reset';
    } elseif ($newPass !== $confirm) {
        $error = 'Passwords do not match.'; $step = 'reset';
    } else {
        try {
            updatePassword($email, $newPass);
            consumeResetToken($token);
            $_SESSION['success'] = 'Password changed successfully! Please sign in with your new password.';
            header('Location: login.php'); exit();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Could not update password. Please try again.'; $step = 'reset';
        }
    }
}

$pageTitle = 'Forgot Password';
include('../includes/header.php');
?>
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4">
  <div class="max-w-md w-full">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

      <?php if ($step === 'request'): ?>
      <div class="flex justify-center mb-2"><div class="bg-[#A6E3E9] w-16 h-16 rounded-xl flex items-center justify-center"><i class="fas fa-lock text-[#71C9CE] text-2xl"></i></div></div>
      <h2 class="mt-4 text-center text-3xl font-bold text-gray-900">Forgot your password?</h2>
      <p class="mt-2 text-center text-sm text-gray-600">Enter your email and we'll send you a reset link.</p>
      <?php if ($error): ?><div class="mt-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form class="mt-7 space-y-5" method="POST" action="forgotpass.php">
        <input type="hidden" name="action" value="request">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
          <input name="email" type="email" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition" placeholder="Enter your account email">
        </div>
        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 text-sm font-semibold rounded-xl text-white bg-[#71C9CE] hover:bg-[#5ab4b9] transition-all shadow-md">
          <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
        </button>
        <div class="text-center"><a href="login.php" class="text-sm text-[#71C9CE] font-medium"><i class="fas fa-arrow-left mr-1"></i>Back to login</a></div>
      </form>

      <?php elseif ($step === 'sent'): ?>
      <div class="text-center">
        <div class="flex justify-center mb-4"><div class="bg-green-100 w-16 h-16 rounded-xl flex items-center justify-center"><i class="fas fa-envelope-open-text text-green-500 text-2xl"></i></div></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Check your email</h2>
        <p class="text-gray-500 text-sm mb-6">If <strong><?php echo htmlspecialchars($_SESSION['reset_email_hint'] ?? 'that address'); ?></strong> is registered, a reset link has been sent.</p>
        <?php if (isset($_SESSION['demo_reset_link'])): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-left text-xs mb-6">
          <div class="font-bold text-amber-700 mb-1"><i class="fas fa-flask mr-1"></i>Demo Mode — no email server</div>
          <div class="text-amber-600 mb-2">Click this link to reset your password:</div>
          <a href="<?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?>" class="text-[#71C9CE] underline break-all font-medium"><?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?></a>
        </div>
        <?php unset($_SESSION['demo_reset_link'], $_SESSION['reset_email_hint']); ?>
        <?php endif; ?>
        <a href="login.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] text-sm transition-all"><i class="fas fa-arrow-left"></i>Back to Login</a>
      </div>

      <?php elseif ($step === 'reset'): ?>
      <div class="flex justify-center mb-2"><div class="bg-[#A6E3E9] w-16 h-16 rounded-xl flex items-center justify-center"><i class="fas fa-key text-[#71C9CE] text-2xl"></i></div></div>
      <h2 class="mt-4 text-center text-3xl font-bold text-gray-900">Set new password</h2>
      <p class="mt-2 text-center text-sm text-gray-600">Choose a strong password (min. 8 characters).</p>
      <?php if ($error): ?><div class="mt-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form class="mt-7 space-y-5" method="POST" action="forgotpass.php">
        <input type="hidden" name="action" value="reset">
        <input type="hidden" name="token"  value="<?php echo htmlspecialchars($token ?? ''); ?>">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
          <div class="relative">
            <input id="new_password" name="new_password" type="password" required autocomplete="new-password"
              class="appearance-none block w-full px-4 py-3 pr-10 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
              placeholder="New password" oninput="checkStrength()">
            <button type="button" onclick="togglePass('new_password','e1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-eye text-sm" id="e1"></i></button>
          </div>
          <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden"><div id="strength-bar" class="h-full rounded-full transition-all" style="width:0%"></div></div>
          <p id="strength-label" class="text-xs mt-1 text-gray-400">Min 8 characters</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
          <div class="relative">
            <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password"
              class="appearance-none block w-full px-4 py-3 pr-10 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#71C9CE] transition"
              placeholder="Re-enter password" oninput="checkMatch()">
            <button type="button" onclick="togglePass('confirm_password','e2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-eye text-sm" id="e2"></i></button>
          </div>
          <p id="match-msg" class="text-xs mt-1 hidden"></p>
        </div>
        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 text-sm font-semibold rounded-xl text-white bg-[#71C9CE] hover:bg-[#5ab4b9] transition-all shadow-md">
          <i class="fas fa-check-circle mr-2"></i>Change Password
        </button>
      </form>
      <script>
      function togglePass(id,eid){const i=document.getElementById(id),e=document.getElementById(eid);i.type=i.type==='password'?'text':'password';e.className=i.type==='text'?'fas fa-eye-slash text-sm':'fas fa-eye text-sm';}
      function checkStrength(){const pw=document.getElementById('new_password').value,bar=document.getElementById('strength-bar'),lbl=document.getElementById('strength-label');let s=0;if(pw.length>=8)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;const l=[{w:'0%',c:'#e5e7eb',t:'Min 8 chars',tc:'#9ca3af'},{w:'25%',c:'#ef4444',t:'Weak',tc:'#ef4444'},{w:'50%',c:'#f59e0b',t:'Fair',tc:'#f59e0b'},{w:'75%',c:'#3b82f6',t:'Good',tc:'#3b82f6'},{w:'100%',c:'#22c55e',t:'Strong ✓',tc:'#22c55e'}][pw.length===0?0:s];bar.style.width=l.w;bar.style.background=l.c;lbl.textContent=l.t;lbl.style.color=l.tc;checkMatch();}
      function checkMatch(){const pw=document.getElementById('new_password').value,cp=document.getElementById('confirm_password').value,msg=document.getElementById('match-msg');if(!cp){msg.classList.add('hidden');return;}msg.textContent=pw===cp?'✓ Passwords match':'✗ Passwords do not match';msg.style.color=pw===cp?'#22c55e':'#ef4444';msg.classList.remove('hidden');}
      </script>

      <?php else: ?>
      <div class="text-center">
        <div class="flex justify-center mb-4"><div class="bg-red-100 w-16 h-16 rounded-xl flex items-center justify-center"><i class="fas fa-clock text-red-500 text-2xl"></i></div></div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Link expired</h2>
        <p class="text-gray-500 text-sm mb-6"><?php echo htmlspecialchars($error ?? 'This link is no longer valid.'); ?></p>
        <a href="forgotpass.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#71C9CE] text-white font-semibold rounded-xl text-sm"><i class="fas fa-redo"></i>Request New Link</a>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
