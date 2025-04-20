<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a class="navbar-brand me-2" href="../index.php">ClickCart</a>
            <a class="nav-link text-white" href="dashboard.php"><?php echo lang('Home') ?></a>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#app-nav"
            aria-controls="app-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="app-nav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <!-- Your other menu items -->
                <li class="nav-item">
                    <a class="nav-link" href="categories.php?page-nr=1"><?php echo lang('CATEGORIES') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="items.php?page-nr=1"><?php echo lang('ITEMS') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="members.php?page-nr=1"><?php echo lang('MEMBERS') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php?page-nr=1"><?php echo lang('ORDERS') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="comments.php"><?php echo lang('COMMENTS') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="promo_codes.php?page-nr=1"><?php echo lang('PromoCodes') ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><?php echo lang('LOGS') ?></a>
                </li>
            </ul>

            <?php 
            if (isset($_SESSION['email'])) {
                $getUser = $con->prepare("SELECT * FROM users WHERE user_id= ?");
                $getUser->execute(array($_SESSION['user_id']));
                $info = $getUser->fetch();
            }
            ?>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle back-dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $info["first_name"]; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end back-dropdown" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="../index.php">Visit Shop</a></li>
                        <li><a class="dropdown-item" href="members.php?do=Edit&userid=<?php echo $info["user_id"]; ?>"><?php echo lang('EDIT_PROFILE') ?></a></li>
                        <li><a class="dropdown-item" href="#"><?php echo lang('SETTINGS') ?></a></li>
                        <li><a class="dropdown-item last" href="../logout.php"><?php echo lang('LOGOUT') ?></a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>