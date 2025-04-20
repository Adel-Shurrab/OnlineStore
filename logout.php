<?php
session_start();

session_unset();
session_destroy();

// Clear all cookies set during login, with Secure and HttpOnly flags
$cookies_to_clear = ['email', 'user_id', 'token', 'sess', 'id'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/', '', true, true); // Expire the cookie
    }
}

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect to the login page
header("Location: login.php");
exit();
