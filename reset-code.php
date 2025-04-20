<?php
ob_start(); // Start output buffering
session_start();
$pageTitle = 'code_verification';

if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

include 'init.php';

$form_errors = array();

// if user clicks check reset otp button
if (isset($_POST['check-reset-otp'])) {
    $_SESSION['info'] = "";
    $otp_code = implode('', $_POST['otp']);

    // Prepare the SQL statement
    $stmt = $con->prepare("SELECT * FROM users WHERE code = ? AND email = ?");
    $stmt->execute(array($otp_code, $_SESSION['email']));
    $fetch_data = $stmt->fetch();

    if ($stmt->rowCount() > 0) {
        $email = $fetch_data['email'];
        $_SESSION['email'] = $email;
        $info = lang($langArray['create_new_password_info']);
        $_SESSION['info'] = $info;
        header('Location: new-password.php');
        exit();
    } else {
        $form_errors['otp-error'] = lang($langArray['incorrect_code']);
    }
}

if (isset($_POST['resend'])) {
    // Generate a new OTP code
    $new_code = random_int(100000, 999999);
    $update_code_stmt = $con->prepare("UPDATE users SET code = ? WHERE email = ?");
    if ($update_code_stmt->execute(array($new_code, $_SESSION["email"]))) {
        // Prepare the email details
        $mail->addAddress($_SESSION["email"]); // Recipient email
        $mail->Subject = lang($langArray['email_verification_code']); // Subject
        $mail->Body = lang($langArray['new_code']) . ": <h1>$new_code</h1>"; // Email body
        $mail->isHTML(true); // Set email format to HTML

        try {
            $mail->send();
            $_SESSION['info'] = lang($langArray['new_verification_code_sent']);
        } catch (Exception $e) {
            $form_errors['otp-error'] = lang($langArray['failed_to_send_new_code']) . ": {$mail->ErrorInfo}";
        }
    } else {
        $form_errors['otp-error'] = lang($langArray['failed_to_update_code']);
    }
}
?>

<div class="container container-content">
    <section class="container-fluid bg-body-tertiary d-block">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-4" style="min-width: 500px;">
                <div class="card bg-white mb-5 mt-5 border-0" style="box-shadow: 0 12px 15px rgba(0, 0, 0, 0.02);">
                    <div class="card-body p-5 text-center">
                        <h4><?php echo lang($langArray['verify']); ?></h4>
                        <p><?php echo lang($langArray['code_sent']); ?></p>

                        <?php
                        if (isset($_SESSION['info'])) {
                        ?>
                            <div class="alert alert-success text-center" style="padding: 0.4rem 0.4rem">
                                <?php echo $_SESSION['info']; ?>
                            </div>
                        <?php
                        }
                        ?>
                        <?php
                        if (count($form_errors) > 0) {
                        ?>
                            <div class="alert alert-danger text-center">
                                <?php
                                foreach ($form_errors as $showerror) {
                                    echo $showerror;
                                }
                                ?>
                            </div>
                        <?php
                        }
                        ?>

                        <form action="reset-code.php" method="post">
                            <div class="otp-field mb-4">
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                            </div>

                            <button class="btn btn-primary mb-3" type="submit" name="check-reset-otp">
                                <?php echo lang($langArray['verify_button']); ?>
                            </button>
                        </form>
                        <form action="reset-code.php" method="POST">
                            <button class="btn btn-link text-muted" type="submit" name="resend">
                                <?php echo lang($langArray['did_not_receive_code']); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>