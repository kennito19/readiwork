<?php
require_once 'config.php';

/* ==============================
   REDIRECT IF ALREADY LOGGED IN
================================ */
if (admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

/* ==============================
   VARIABLES
================================ */
$error = '';
$email = '';

/* ==============================
   LOGIN ATTEMPT TRACKING
================================ */
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['last_attempt']   = $_SESSION['last_attempt'] ?? 0;

/* ==============================
   CSRF TOKEN
================================ */
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

/* ==============================
   HANDLE LOGIN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF CHECK */
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['login_csrf'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid session. Please refresh and try again.';
    } else {

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        /* LOCKOUT CHECK */
        if (
            $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS &&
            (time() - $_SESSION['last_attempt']) < LOGIN_LOCK_TIME
        ) {
            $wait = LOGIN_LOCK_TIME - (time() - $_SESSION['last_attempt']);
            $error = "Too many failed attempts. Try again in {$wait}s.";
        }
        elseif ($email === '' || $password === '') {
            $error = 'Email and password are required.';
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        }
        else {
            try {
                $stmt = $pdo->prepare(
                    "SELECT id, email, password, role 
                     FROM admins 
                     WHERE email = ? 
                     LIMIT 1"
                );
                $stmt->execute([$email]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $error = 'Invalid login credentials.';
                }
                elseif (!password_verify($password, $admin['password'])) {
                    $error = 'Invalid login credentials.';
                }
                else {
                    /* ✅ LOGIN SUCCESS */

                    session_regenerate_id(true);

                    $_SESSION[ADMIN_SESSION_NAME] = [
                        'id'    => $admin['id'],
                        'email' => $admin['email'],
                        'role'  => $admin['role']
                    ];

                    $_SESSION['last_activity']   = time();
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['last_attempt']   = 0;

                    /* Update last login */
                    $pdo->prepare(
                        "UPDATE admins SET last_login = NOW() WHERE id = ?"
                    )->execute([$admin['id']]);

                    header('Location: dashboard.php');
                    exit;
                }

                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt'] = time();

            } catch (Throwable $e) {
                error_log('[LOGIN ERROR] ' . $e->getMessage());
                $error = 'System error. Try again later.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Login — Readiwork</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root{
  --brand:#6366f1;
  --brand-soft:#818cf8;
  --accent:#22c55e;
  --bg:#020617;
  --card:#111827;
  --text:#f1f5f9;
  --muted:#9ca3af;
  --border:#1f2937;
}
body{
  font-family:Inter,system-ui,sans-serif;
  background:
    radial-gradient(50% 40% at top, rgba(99,102,241,.25), transparent 60%),
    var(--bg);
  color:var(--text);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
}
.login-card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:26px;
  padding:50px;
  max-width:420px;
  width:100%;
  box-shadow:0 40px 80px rgba(99,102,241,.25);
}
.brand{
  font-weight:900;
  letter-spacing:.5px;
  font-size:1.75rem;
}
.brand span{color:var(--brand-soft);}
.form-control{
  background:#020617;
  border:1px solid var(--border);
  color:#fff;
  border-radius:14px;
  padding:14px 18px;
}
.form-control:focus{
  border-color:var(--brand);
  box-shadow:0 0 0 3px rgba(99,102,241,.25);
}
.btn-primary{
  background:linear-gradient(135deg,var(--brand),var(--brand-soft));
  border:none;
  padding:14px;
  border-radius:999px;
  font-weight:600;
}
.error-msg{
  background:rgba(255,0,0,.1);
  border:1px solid rgba(255,0,0,.25);
  border-radius:14px;
  padding:12px;
  margin-bottom:20px;
  color:#ff6b6b;
  text-align:center;
}
.admin-note{
  background:rgba(34,197,94,.12);
  border:1px solid rgba(34,197,94,.25);
  color:#bbf7d0;
  border-radius:14px;
  padding:12px;
  font-size:.9rem;
  text-align:center;
}
.small-link{
  color:var(--muted);
  text-decoration:none;
}
.small-link:hover{color:var(--accent);}
</style>
</head>

<body>
<div class="login-card">

<div class="text-center mb-4">
  <div class="brand">READI<span>WORK</span></div>
  <p class="text-muted mt-2">Admin Control Panel</p>
</div>

<div class="admin-note mb-4">
  <i class="fa-solid fa-shield-halved me-1"></i>
  Restricted access — authorized personnel only
</div>

<?php if ($error): ?>
  <div class="error-msg"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
  <input type="hidden" name="csrf_token" value="<?= $_SESSION['login_csrf'] ?>">

  <div class="mb-3">
    <label class="form-label small text-muted">Admin Email</label>
    <input type="email" name="email" class="form-control"
           value="<?= htmlspecialchars($email) ?>" required autofocus>
  </div>

  <div class="mb-4">
    <label class="form-label small text-muted">Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>

  <button class="btn btn-primary w-100">
    <i class="fa-solid fa-lock me-1"></i> Secure Login
  </button>
</form>

<div class="text-center mt-4">
  <a href="/" class="small-link">← Back to Readiwork</a>
</div>

</div>
</body>
</html>
