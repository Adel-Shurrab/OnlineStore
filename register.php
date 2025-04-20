<?php
ob_start(); // Start output buffering
session_start();
$pageTitle = 'signup';

include 'init.php';

$form_errors = array(
    'fname_err' => '',
    'lname_err' => '',
    'pass_err' => '',
    'email_err' => ''
);

// If form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Gather form input data
    $email = $_POST['email'];
    $password = $_POST['password'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];

    // Validate user inputs and store errors if any

    $count = checkItem('email', 'users', $email);
    if (empty($email)) {
        $form_errors['email_err'] = lang($langArray["cant_be_empty"]);
    } elseif ($count > 0) {
        $form_errors['email_err'] = lang($langArray["email_exists"]);
    }

    $pass_error = ValidatePassword($password); // Validate password
    if (empty($password)) {
        $form_errors['pass_err'] = lang($langArray["cant_be_empty"]);
    } elseif ($pass_error) {
        $form_errors['pass_err'] = lang($langArray["password_error"]);
    } else {
        // Hash password if validation passes
        $encpass = password_hash($password, PASSWORD_BCRYPT);
    }

    $fname_err = ValidateName($fname); // Validate first name
    if (empty($fname)) {
        $form_errors['fname_err'] = lang($langArray["cant_be_empty"]);
    } elseif ($fname_err) {
        $form_errors['fname_err'] = $fname_err;
    }

    $lname_err = ValidateName($lname); // Validate last name
    if (empty($lname)) {
        $form_errors['lname_err'] = lang($langArray["cant_be_empty"]);
    } elseif ($lname_err) {
        $form_errors['lname_err'] = $lname_err;
    }

    // If no validation errors, insert the new member into the database
    $session = generateCode(10);
    if (empty(array_filter($form_errors))) {
        $code = random_int(100000, 999999);

        $insertNewUser = $con->prepare("INSERT INTO users (email, password, first_name, last_name, session, code) VALUES (?, ?, ?, ?, ?, ?)");
        $insertNewUser->execute(array($email, $encpass, $fname, $lname, $session, $code));

        if ($insertNewUser) {
            // Create avatar after successfully inserting user
            $user_avatar = makeAvatar(strtoupper($fname[0]));
            $stmt = $con->prepare("UPDATE users SET user_avatar = ? WHERE user_id = ?");
            $stmt->execute([$user_avatar, $con->lastInsertId()]);
            
            // Prepare email details
            $mail->addAddress($email); // Recipient email
            $mail->Subject = lang($langArray['email_verification_code']); // Subject
            $mail->Body = lang($langArray['otp_sent']) . "<h1>$code</h1>"; // Email body
            $mail->isHTML(true); // Set email format to HTML

            try {
                $mail->send();
                $info = lang($langArray["otp_sent"]) . " - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email; // Save email to session
                $_SESSION['password'] = $password;
                header('Location: user-otp.php'); // Redirect to reset password page
                exit();
            } catch (Exception $e) {
                $form_errors['otp-error'] = lang($langArray["otp_error"]) . ": {$mail->ErrorInfo}";
            }
        } else {
            $form_errors['db-error'] = lang($langArray["db_error"]);
        }
        // Redirect to Add page with success message
        header("Location: login.php");
        exit();
    }
}

?>
<div class="container container-content">

    <form class="signup" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <h2 class="text-center"><?php echo lang($langArray['welcome']); ?></h2>
        <h4 class="text-center"><?php echo lang($langArray['signup']); ?></h4>

        <!-- First Name -->
        <div class="form-floating mb-4">
            <input type="text" class="form-control float-input" id="floatingfname" name="fname" placeholder="<?php echo lang($langArray['first_name']); ?>" autocomplete="off" />
            <label for="floatingfname"><?php echo lang($langArray['first_name']); ?></label>
            <span class="asterisk">*</span>
            <p class="text-danger"><?php echo $form_errors['fname_err'] ?></p>
        </div>

        <!-- Last Name -->
        <div class="form-floating mb-4">
            <input type="text" class="form-control float-input" id="floatinglname" name="lname" placeholder="<?php echo lang($langArray['last_name']); ?>" autocomplete="off" />
            <label for="floatinglname"><?php echo lang($langArray['last_name']); ?></label>
            <span class="asterisk">*</span>
            <p class="text-danger"><?php echo $form_errors['lname_err'] ?></p>
        </div>

        <!-- Password Field -->
        <div class="form-floating mb-4">
            <i class="fa fa-solid fa-eye" id="togglePasswordRegister" style="display: none;"></i>
            <input type="password" class="form-control float-input" id="inputPassword" name="password" placeholder="<?php echo lang($langArray['password']); ?>" autocomplete="off">
            <label for="floatingPassword"><?php echo lang($langArray['password']); ?></label>
            <span class="asterisk">*</span>
            <i class="fa fa-solid fa-eye" id="togglePassword" style="display: none;"></i>
            <p class="text-danger"><?php echo $form_errors['pass_err'] ?></p>
        </div>

        <!-- Email Field -->
        <div class="form-floating mb-4">
            <input type="email" class="form-control float-input" id="floatingEmail" name="email" placeholder="<?php echo lang($langArray['email']); ?>">
            <label for="floatingEmail"><?php echo lang($langArray['email']); ?></label>
            <span class="asterisk">*</span>
            <p class="text-danger"><?php echo $form_errors['email_err'] ?></p>
        </div>

        <p>
            <?php echo lang($langArray["have_account"]); ?>
            <a href="login.php" class="text-primary pull-right"><?php echo lang($langArray['login']); ?></a>
        </p>

        <!-- Register Button -->
        <div class="d-grid gap-2 col-6 mx-auto signup-btn">
            <button class="btn btn-primary btn-lg" type="submit"><?php echo lang($langArray['register_button']); ?></button>
        </div>
    </form>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>