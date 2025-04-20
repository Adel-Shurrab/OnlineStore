<?php
ob_start();
session_start();
$pageTitle = 'Login';

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

include 'init.php';

$form_errors = array(
    'email_err'   => '',
    'password_err' => ''
);

$u_email = ''; // To retain email input in case of error

// Check if User Coming from HTTP Post Request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u_email = trim($_POST['email']);
    $u_pass = $_POST['pass'];

    // Validate email
    if (empty($u_email)) {
        $form_errors['email_err'] = "email_error_empty";
    } elseif (!filter_var($u_email, FILTER_VALIDATE_EMAIL)) {
        $form_errors['email_err'] = "email_error_invalid";
    }

    // Validate password
    if (empty($u_pass)) {
        $form_errors['password_err'] = "password_error_empty";
    }

    if (empty(array_filter($form_errors))) {
        // Check if User exists in DB
        $stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($u_email));
        $row = $stmt->fetch();
        $count = $stmt->rowCount();

        if ($count == 0) {
            // Handle invalid login attempt
            $form_errors['email_err'] = "no_account_with_email";
        } elseif (!password_verify($u_pass, $row['password'])) {
            // If password does not match
            $form_errors['password_err'] = "incorrect_password";
        } else {
            // Login successful, set session and cookies
            $_SESSION['email'] = $u_email;
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['group_id'] = $row['group_id'];

            // Set cookies for "Remember Me" if checked
            if (isset($_POST['remember'])) {
                // setcookie("user_token", $u_token, time() + 86400, "/", "", true, true);
                setcookie("email", $u_email, time() + 86400, "/", "", true, true);
                setcookie("user_id", $row['user_id'], time() + 86400, "/", "", true, true);
            }

            // Redirect based on user role
            if ($row['group_id'] == 0) { // Regular user
                header('Location: index.php');
            } else { // Admin
                header('Location: admin/dashboard.php');
            }
            exit();
        }
    }
}


// Google Login
if (isset($_GET['code'])) {
    $token = $gClient->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token["error"])) {
        // Get data from Google
        $oAuth = new Google\Service\Oauth2($gClient);
        $userData = $oAuth->userinfo_v2_me->get();

        // Check if user exists in the database
        $checkUser = $con->prepare("SELECT * FROM users WHERE email = ?");
        $checkUser->execute(array($userData['email']));
        $info = $checkUser->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            // If user doesn't exist, register them
            $gender = ($userData["gender"] == 'male') ? 1 : (($userData["gender"] == 'female') ? 0 : NULL);
            $session = generateCode(10);
            $securePassword = password_hash(generateCode(10), PASSWORD_DEFAULT);

            $insertNewUser = $con->prepare("INSERT INTO users (email, password, first_name, last_name, gender, user_avatar, reg_stat, session) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
            $insertNewUser->execute(array($userData["email"], $securePassword, $userData["givenName"], $userData["familyName"], $gender, $userData["picture"], $session));

            // Retrieve the new user's data to set session and cookies
            $userId = $con->lastInsertId();
            $getUser = $con->prepare("SELECT * FROM users WHERE user_id = ?");
            $getUser->execute(array($userId));
            $newUser = $getUser->fetch(PDO::FETCH_ASSOC);
            $info = $newUser; // Use $info for redirect logic below
        }

        // Set session and cookies for the user (new or existing)
        $_SESSION['email'] = $info['email'];
        $_SESSION['user_id'] = $info['user_id'];
        $_SESSION['group_id'] = $info['group_id'];
        setcookie("id", $info['user_id'], time() + 86400, "/");
        setcookie("sess", $info["session"], time() + 86400, "/");

        // Redirect based on group_id
        if ($info['group_id'] == 0) { // Regular user
            header('Location: index.php');
        } else { // Admin
            header('Location: admin/dashboard.php');
        }
        exit();
    } else {
        // If Google authentication fails, redirect to login page
        header('Location: login.php');
        exit();
    }
}


?>
<!-- HTML Login Form -->
<div class="container container-content">
    <form class="login" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <h2 class="text-center"><?php echo lang('welcome_back'); ?></h2>
        <h4 class="text-center"><?php echo lang('login'); ?></h4>

        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-success success-message" role="alert">
                <?php echo $_SESSION['info']; ?>
            </div>
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

        <div class="login-input">
            <input class="form-control" type="text" name="email" placeholder="<?php echo lang('email_placeholder'); ?>" value="<?php echo htmlspecialchars($u_email); ?>" autocomplete="off">
            <p class="text-danger"><?php echo !empty($form_errors['email_err']) ? lang($form_errors['email_err']) : ''; ?></p>
        </div>

        <div class="pass">
            <div class="login-input">
                <i class="fa fa-solid fa-eye" id="togglePassword" style="display: none;"></i>
                <input class="form-control" type="password" name="pass" id="inputPassword" placeholder="<?php echo lang('password_placeholder'); ?>" autocomplete="off">
            </div>
            <p class="text-danger"><?php echo !empty($form_errors['password_err']) ? lang($form_errors['password_err']) : ''; ?></p>
        </div>

        <div class="remember">
            <input class="form-check-input" type="checkbox" name="remember" id="flexCheckDefault">
            <label class="form-check-label" for="flexCheckDefault"><?php echo lang('remember_me'); ?></label>
        </div>

        <div class="d-grid">
            <input class="btn btn-lg btn-primary" type="submit" value="<?php echo lang('login_button'); ?>">
            <a href="<?php echo $login_url; ?>" class="btn btn-outline-secondary google rounded-pill">
                <img src="layout/images/google.png" alt="" class="google-icon">
                <?php echo lang('google_login'); ?>
            </a>
        </div>

        <div class="form-check">
            <div class="forgetPass-btn">
                <a href="forgetPass.php"><?php echo lang('forgot_password'); ?></a>
            </div>
            <p><?php echo lang('no_account'); ?> <a href="register.php" class="text-primary pull-right"><?php echo lang('sign_up'); ?></a></p>
        </div>
    </form>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>