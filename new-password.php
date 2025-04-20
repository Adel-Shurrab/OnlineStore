<?php
ob_start(); // Start output buffering
session_start();
$pageTitle = 'reset_password';

include 'init.php';

if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
} elseif (isset($_SESSION["user_id"]) && checkUserStatus($_SESSION['email']) == 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
    header("Location: index.php");
    exit;
}



$form_errors = array();

if (isset($_POST['change-password'])) {

    $_SESSION['info'] = "";
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    $pass_error = ValidatePassword($password); // Validate password
    if (empty($password)) {
        $form_errors['password'] = lang($langArray['cannot_be_empty']);
    } elseif ($pass_error) {
        $form_errors['password'] = $pass_error;
    } elseif ($password !== $cpassword) { // Check if passwords match
        $form_errors['password'] = lang($langArray['passwords_not_matching']);
    } else {

        $code = 0; // Resetting the code
        $encpass = password_hash($password, PASSWORD_BCRYPT);

        if (isset($_SESSION['user_id'])) {
            $stmt = $con->prepare("UPDATE users SET code = ?, password = ? WHERE user_id = ?");
            $stmt->execute(array($code, $encpass, $_SESSION['user_id']));

            if ($stmt->rowCount() > 0) {
                $info = lang($langArray['password_changed']);
                $_SESSION['info'] = $info;
                header('Location: new-password.php');
                exit();
            } else {
                $form_errors['db-error'] = lang($langArray['db_error']);
            }
        } else {
            $email = $_SESSION['email'];
            $stmt = $con->prepare("UPDATE users SET code = ?, password = ? WHERE email = ?");
            $stmt->execute(array($code, $encpass, $email));

            if ($stmt->rowCount() > 0) {
                $info = lang($langArray['new_password_info']);
                $_SESSION['info'] = $info;
                header('Location: login.php');
                exit();
            } else {
                $form_errors['db-error'] = lang($langArray['db_error']);
            }
        }
    }
}
?>

<div class="container container-content">
    <div class="col-md-4 form">
        <form action="new-password.php" method="POST" autocomplete="off">
            <h2 class="text-center"><?php echo lang($langArray['reset_password_header']); ?></h2>
            <p class="text-center"><?php echo lang($langArray['strong_password_info']); ?></p>

            <?php if (isset($_SESSION['info'])) { ?>
                <div class="alert alert-success success-message" role="alert">
                    <?php echo $_SESSION['info']; ?>
                </div>
            <?php } ?>

            <?php if (count($form_errors) > 0) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($form_errors as $showerror) {
                        echo $showerror;
                    } ?>
                </div>
            <?php } ?>

            <div class="form-group">
                <i class="fa fa-solid fa-eye toggle-password" id="togglePassword" style="display: none;"></i>
                <input class="form-control" type="password" id="inputPassword" name="password" placeholder="<?php echo lang($langArray['password_placeholder']); ?>" required>
            </div>
            <div class="form-group">
                <i class="fa fa-solid fa-eye toggle-password" id="togglePassword" style="display: none;"></i>
                <input class="form-control" type="password" id="inputPassword" name="cpassword" placeholder="<?php echo lang($langArray['confirm_password_placeholder']); ?>" required>
            </div>
            <div class="form-group">
                <input class="form-control button" type="submit" name="change-password" value="<?php echo lang($langArray['reset_password_button']); ?>">
            </div>
        </form>
    </div>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>