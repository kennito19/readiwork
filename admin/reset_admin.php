<?php
require "../config.php";

$hash = password_hash("Admin@12345", PASSWORD_DEFAULT);

$conn->query("
    UPDATE admins
    SET password_hash='$hash'
    WHERE username='admin'
");

echo "Admin password reset OK";
