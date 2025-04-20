<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle language change
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = htmlspecialchars($_GET['lang']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $css ?>font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $css ?>bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $css ?>backend.css">
    <link rel="stylesheet" href="<?php echo $css ?>frontend.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <title><?php echo getTitle(); ?></title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a class="navbar-brand me-2" href="index.php">
                    <img src="layout/images/logo.png" class="logo" alt="Logo">
                </a>
                <span class="page-title">ClickCart</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#app-nav" aria-controls="app-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse all-nav" id="app-nav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-left">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" aria-expanded="false">
                            <?php echo lang('Categories'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-category" aria-labelledby="navbarDropdown">
                            <?php
                            foreach (getCat() as $cat) {
                                echo '<li><a class="dropdown-item dropdown-item-categories" href="items.php?pageid=' . $cat['cat_id'] . '&pagename=' . str_replace(' ', '-', $cat['cat_name']) . '&page-nr=1">' . lang($cat['cat_name']) . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="items.php?page-nr=1"><?php echo lang('Items'); ?></a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="profile-settings.php"><?php echo lang('Profile Settings'); ?></a>
                    </li>
                </ul>

                <?php if (isset($_SESSION["user_id"])): ?>
                    <?php
                    $getUser = $con->prepare("SELECT * FROM users WHERE user_id = ?");
                    $getUser->execute(array($_SESSION['user_id']));
                    $info = $getUser->fetch();
                    ?>

                    <div class="nav-right d-none d-lg-flex align-items-center">
                        <a href="cart.php" class="cart-icon">
                            <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                        </a>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <img src="<?php echo $info['user_avatar'] ?>" class="profile-pic" alt="Profile Picture" onclick="toggleDropdown('userDropdown')">
                            <div class="dropdown-content hide" id="userDropdown">
                                <div class="user-info">
                                    <!-- <img src="<?php //echo $info['user_avatar'] ?>" class="profile-pic" alt="Profile Picture"> -->
                                    <?php getUserAvatar($_SESSION['user_id']) ?>
                                    <div class="title">
                                        <h4><?php echo htmlspecialchars($info['first_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                                    </div>
                                </div>
                                <hr>
                                <a href="profile-settings.php" class="sub-menu-link">
                                    <span class="sub-menu-icon"><i class="fa fa-user" aria-hidden="true"></i></span>
                                    <p><?php echo lang('My Profile'); ?></p>
                                    <span class="arrow"><i class="fa fa-arrow-right" aria-hidden="true"></i></span>
                                </a>
                                <a href="new-password.php" class="sub-menu-link">
                                    <span class="sub-menu-icon"><i class="fa fa-cog" aria-hidden="true"></i></span>
                                    <p><?php echo lang('Reset password'); ?></p>
                                    <span class="arrow"><i class="fa fa-arrow-right" aria-hidden="true"></i></span>
                                </a>
                                <a href="logout.php" class="sub-menu-link">
                                    <span class="sub-menu-icon"><i class="fa fa-sign-out" aria-hidden="true"></i></span>
                                    <p><?php echo lang('Logout'); ?></p>
                                    <span class="arrow"><i class="fa fa-arrow-right" aria-hidden="true"></i></span>
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <ul class="navbar-nav mb-2 mb-lg-0 nav-right">
                        <li class="nav-link"><a href="login.php"><?php echo lang('Sign-in'); ?></a></li>
                        <li class="nav-link"><a href="register.php"><?php echo lang('Sign-up'); ?></a></li>
                    </ul>
                <?php endif; ?>

                <!-- Language Dropdown -->
                <div class="dropdown langs">
                    <img src="layout/images/lang.png" class="lang-img" alt="Language" onclick="toggleDropdown('langDropdown')">
                    <div class="dropdown-content hide" id="langDropdown">
                        <a class="dropdown-item" href="?lang=en">English</a>
                        <a class="dropdown-item" href="?lang=ar">العربية</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

</body>

</html>