    <?php
    include 'admin/connect.php';

    // Directories
    $tmp = 'includes/tmps/'; // Template dir
    $css = 'layout/css/'; // CSS dir
    $js = 'layout/js/'; // JS dir
    $lang = 'includes/langs/'; // Language dir
    $func = 'includes/func/'; // Function dir
    $uploads = 'data/uploads/'; //Uploads dir

    // Include functions and language files
    include $func . 'functions.php';
    include $func . 'validationFunc.php';
    
    include_once $lang . 'en.php';
    include_once $lang . 'ar.php';
    include_once $lang . 'language.php';

    // include $uploads . 'uploads.php';

    // Load Google Client Library
    require_once("vendor/autoload.php");

    // Initialize Google Client
    $gClient = new Google_Client();
    $gClient->setClientId("940016025239-hmgca63ss32s6948u4gloffpk2m2ghn4.apps.googleusercontent.com");
    $gClient->setClientSecret("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");
    $gClient->setRedirectUri("http://localhost/online-store/login.php");
    $gClient->addScope("email");
    $gClient->addScope("profile");

    // Force account selection every time
    $gClient->setPrompt("select_account");

    // Create login URL
    $login_url = $gClient->createAuthUrl();


    // PHPMailer
    use PHPMailer\PHPMailer\PHPMailer;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = 'smtp.gmail.com'; // Use Gmail SMTP server
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS
    $mail->Port = 587; // TCP port for TLS
    $mail->Username = 'eshop20032024@gmail.com';
    $mail->Password = 'lpeg kkkl nhrg dfms';
    $mail->setFrom('eshop20032024@gmail.com', 'eShop');

    // Include header
    include $tmp . 'header.php';
