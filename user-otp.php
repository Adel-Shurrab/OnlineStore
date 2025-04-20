<?php
ob_start(); // Start output buffering
session_start();
$pageTitle = 'pageTitle';

if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

include 'init.php';
if (checkUserStatus($_SESSION['email']) == 0) {
    header("Location: index.php?active-msg");
    exit();
}

$email = $_SESSION['email'];
$errors = [];

if (isset($_POST['check'])) {
    $_SESSION['info'] = "";
    $otp_code = implode('', $_POST['otp']); // Combine the OTP input into a single string

    $check_code_stmt = $con->prepare("SELECT * FROM users WHERE code = ? AND email = ?");
    $check_code_stmt->execute(array($otp_code, $email));
    $fetch_data = $check_code_stmt->fetch();

    if ($check_code_stmt->rowCount() > 0) {
        $fetch_code = $fetch_data['code'];
        $email = $fetch_data['email'];
        $code = 0;
        $status = 1;
        $update_otp_stmt = $con->prepare("UPDATE users SET code = ?, reg_stat = ? WHERE code = ? AND email = ?");
        $update_res = $update_otp_stmt->execute(array($code, $status, $fetch_code, $email));

        if ($update_res) {
            $_SESSION['email'] = $email;
            $_SESSION['info'] = lang($langArray['otpUpdatedSuccess']);
            header('location: login.php');
            exit();
        } else {
            $errors['otp-error'] = lang($langArray['otpErrorUpdate']);
        }
    } else {
        $errors['otp-error'] = lang($langArray['otpIncorrect']);
    }
}

if (isset($_POST['resend']) || isset($_GET['resend'])) {
    $new_code = random_int(100000, 999999);
    $update_code_stmt = $con->prepare("UPDATE users SET code = ? WHERE email = ?");
    if ($update_code_stmt->execute(array($new_code, $email))) {
        $mail->addAddress($email);
        $mail->Subject = lang($langArray['pageTitle']);
        $mail->Body = lang($langArray['otpInstructions']) . ": <h1>$new_code</h1>";
        $mail->isHTML(true);

        try {
            $mail->send();
            $_SESSION['info'] = lang($langArray['otpResent']);
        } catch (Exception $e) {
            $errors['otp-error'] = lang($langArray['otpErrorResend']) . ": {$mail->ErrorInfo}";
        }
    } else {
        $errors['otp-error'] = lang($langArray['otpErrorUpdate']);
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
                        <p><?php echo lang($langArray['verifyPrompt']); ?></p>

                        <?php if (isset($_SESSION['info'])): ?>
                            <div class="alert alert-success text-center">
                                <?php echo $_SESSION['info']; ?>
                            </div>
                            <?php unset($_SESSION['info']); ?>
                        <?php endif; ?>

                        <?php if (count($errors) > 0): ?>
                            <div class="alert alert-danger text-center">
                                <?php foreach ($errors as $showerror): ?>
                                    <p><?php echo $showerror; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" autocomplete="off">
                            <div class="otp-field mb-4">
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                                <input type="text" name="otp[]" maxlength="1" class="otp-input" required />
                            </div>

                            <button class="btn btn-primary mb-3" type="submit" name="check">
                                <?php echo lang($langArray['verifyButton']); ?>
                            </button>
                        </form>

                        <form action="user-otp.php" method="POST">
                            <button class="btn btn-link text-muted" type="submit" name="resend">
                                <?php echo lang($langArray['resend']); ?>
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