<?php
ob_start();
session_start();
$pageTitle = 'password_reset';

include 'init.php';

$form_errors = array();

if (isset($_POST['check-email'])) {
    $email = $_POST['email'];

    // Prepare the SELECT query to check if the email exists
    $check_email_stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
    $check_email_stmt->execute(array($email));

    if ($check_email_stmt->rowCount() > 0) {
        // Generate a random code for password reset
        $code = random_int(100000, 999999);

        // Prepare the UPDATE query to store the reset code
        $insert_code_stmt = $con->prepare("UPDATE users SET code = ? WHERE email = ?");
        $update_res = $insert_code_stmt->execute([$code, $email]);

        if ($update_res) {
            // Prepare email details
            $mail->addAddress($email);
            $mail->Subject = lang($langArray['password_reset_code']);
            $mail->Body = lang($langArray['password_reset_body']) . "<h1>$code</h1>";
            $mail->isHTML(true);

            try {
                $mail->send();
                $info = lang($langArray['otp_sent']) . " - $email";
                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                header('Location: reset-code.php');
                exit();
            } catch (Exception $e) {
                $form_errors['otp-error'] = lang($langArray['mail_error']) . ": {$mail->ErrorInfo}";
            }
        } else {
            $form_errors['db-error'] = lang($langArray['something_went_wrong']);
        }
    } else {
        $form_errors['email'] = lang($langArray['email_not_exist']);
    }
}
?>

<div class="container container-content">
    <div class="forgetPass">
        <div class="card">
            <div class="card-body">
                <h3 class="text-center"><?php echo lang($langArray['password_reset']); ?></h3>
                <p class="text-center"><?php echo lang($langArray['password_reset_instructions']); ?></p>
                <?php if (count($form_errors) > 0) { ?>
                    <div class="alert alert-danger text-center">
                        <?php foreach ($form_errors as $form_error) {
                            echo $form_error;
                        } ?>
                    </div>
                <?php } ?>
                <form method="post" action="forgetPass.php">
                    <div class="mb-3">
                        <input id="email" name="email" placeholder="<?php echo lang($langArray['email']); ?>" class="form-control" type="email" required>
                    </div>
                    <div class="mb-3">
                        <input name="check-email" class="btn btn-primary btn-block" value="<?php echo lang($langArray['reset_password_button']); ?>" type="submit">
                    </div>
                </form>
                <div class="text-center">
                    <a href="login.php"><?php echo lang($langArray['login']); ?></a> | <a href="register.php"><?php echo lang($langArray['register']); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>
