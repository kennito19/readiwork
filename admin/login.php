<?php
require "../config.php";
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if ($user === "" || $pass === "") {
        $error = "Please enter username and password";
    } else {

        $stmt = $conn->prepare("
            SELECT id, password_hash
            FROM admins
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $admin = $res->fetch_assoc();

            if (password_verify($pass, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: dashboard.php");
                exit;
            }
        }

        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh">

<div class="card p-4 shadow-sm" style="width:350px">
    <h4 class="mb-3 text-center">Admin Login</h4>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input class="form-control mb-2" name="username" placeholder="Username" required>
        <input class="form-control mb-3" name="password" type="password" placeholder="Password" required>
        <button class="btn btn-primary w-100">Login</button>
    </form>
</div>

</body>
</html>
