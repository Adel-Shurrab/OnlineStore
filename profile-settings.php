<?php
ob_start();
session_start();
$pageTitle = 'Profile';
include 'init.php';
$do = isset($_GET['do']) ? $_GET['do'] : 'Manage';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (checkUserStatus($_SESSION['email']) == 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
    header("Location: index.php");
    exit;
}

$getUser = $con->prepare("SELECT * FROM users WHERE user_id= ?");
$getUser->execute([$_SESSION['user_id']]);
$info = $getUser->fetch();
$user_id = $info['user_id'];

$form_errors = [];
$alert_message = '';

if ($do === 'Manage') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_info'])) {
            $userid = $_SESSION['user_id'];
            $fname = $_POST['fname'];
            $lname = $_POST['lname'];
            $phone = $_POST['phone'];
            $location = $_POST['location'];
            $gender = $_POST['gender'];
            $birthday = $_POST['birthday'];

            // Validate inputs and collect errors
            $form_errors['fname_err'] = validateRequired(lang($langArray['first_name']), $fname) ?: ValidateName($fname);
            $form_errors['lname_err'] = validateRequired(lang($langArray['last_name']), $lname) ?: ValidateName($lname);

            if (!empty(array_filter($form_errors))) {
                return;
            }

            $stmt = $con->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, location = ?, gender = ?, birthDate = ? WHERE user_id = ?");
            $stmt->execute([$fname, $lname, $phone, $location, $gender, $birthday, $userid]);

            $_SESSION['alert_message'] = $stmt->rowCount() > 0
                ? ['type' => 'success', 'message' => lang($langArray['success_update'])]
                : ['type' => 'warning', 'message' => lang($langArray['warning_no_changes'])];

            header("Location: profile-settings.php");
            exit;
        }

        if (isset($_POST['update_img'])) {
            $file = $_FILES['profile_pic'];
            $uploadPath = $uploads . 'user_avatar/';
            $result = validateAndUploadImage($file, ['jpg', 'jpeg', 'png'], 5144576, $uploadPath, $langArray);
            if (isset($result['error'])) {
                setErrorAndRedirect($result['error'], $langArray, 'profile-settings.php');
            } else {
                $newFilePath = $result['path'];

                // Fetch the current avatar path and delete old image if it exists
                $stmt = $con->prepare("SELECT user_avatar FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $currentAvatar = $stmt->fetchColumn();

                if ($currentAvatar && file_exists($currentAvatar) && $oldImage != 'layout/images/default-item.png') {
                    unlink($currentAvatar);
                }

                $stmt = $con->prepare("UPDATE users SET user_avatar = ? WHERE user_id = ?");
                $stmt->execute([$newFilePath, $user_id]);
                $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['success_uploads'])];

                header("Location: profile-settings.php");
                exit;
            }
        }
    }

?>

    <div class="container-xl px-4 mt-4">
        <nav class="nav nav-borders info-nav">
            <a class="nav-link active ms-0" href="#"><?php echo lang($langArray['profile']); ?></a>
            <a class="nav-link" href="profile-settings.php?do=orders"><?php echo lang($langArray['orders']); ?></a>
            <a class="nav-link" href="profile-settings.php?do=wishlist"><?php echo lang($langArray['wishlist']); ?></a>
            <a class="nav-link" href="profile-settings.php?do=info"><?php echo lang($langArray['personal_info']); ?></a>
        </nav>
        <hr class="mt-0 mb-4">

        <?php
        if (isset($_SESSION['alert_message'])) {
            echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
            echo $_SESSION['alert_message']['message'];
            echo '</div>';
            unset($_SESSION['alert_message']);
        }
        ?>

        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header"><?php echo lang($langArray['profile_picture']); ?></div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle mb-2" src="<?php echo $info['user_avatar'] ?>" alt="">
                        <div class="small font-italic text-muted mb-4"><?php echo lang($langArray['picture_info']); ?></div>
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" id="inputGroupFile02" name="profile_pic" accept=".jpg, .jpeg, .png" required>
                                <label class="input-group-text" for="inputGroupFile02"><?php echo lang($langArray['upload']); ?></label>
                            </div>
                            <button type="submit" name="update_img" class="btn btn-primary">Change you'r prifile picture</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><?php echo lang($langArray['account_details']); ?></div>
                    <div class="card-body">
                        <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="small mb-1" for="inputFirstName"><?php echo lang($langArray['first_name']); ?></label>
                                <input class="form-control" id="inputFirstName" type="text" name="fname" placeholder="<?php echo lang($langArray['enter_first_name']); ?>" value="<?php echo $info['first_name'] ?>">
                                <p class="text-danger"><?php echo $form_errors['fname_err'] ?? ''; ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small mb-1" for="inputFullName"><?php echo lang($langArray['last_name']); ?></label>
                                <input class="form-control" id="inputFullName" type="text" name="lname" placeholder="<?php echo lang($langArray['enter_last_name']); ?>" value="<?php echo $info['last_name'] ?>">
                                <p class="text-danger"><?php echo $form_errors['lname_err'] ?? ''; ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small mb-1" for="inputLocation"><?php echo lang($langArray['location']); ?></label>
                                <input class="form-control" id="inputLocation" type="text" name="location" placeholder="<?php echo lang($langArray['enter_location']); ?>" value="<?php echo $info['location'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="small mb-1" for="inputGender"><?php echo lang($langArray['gender']); ?></label>
                                <select class="form-control" id="inputGender" name="gender">
                                    <option value="0" <?= !isset($info['gender']) ? 'selected' : ''; ?>><?php echo lang($langArray['select_gender']); ?></option>
                                    <option value="1" <?= $info['gender'] == '1' ? 'selected' : ''; ?>><?php echo lang($langArray['male']); ?></option>
                                    <option value="2" <?= $info['gender'] == '2' ? 'selected' : ''; ?>><?php echo lang($langArray['female']); ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small mb-1" for="inputEmailAddress"><?php echo lang($langArray['email_address']); ?></label>
                                <input class="form-control" id="inputEmailAddress" type="email" name="email" placeholder="<?php echo lang($langArray['enter_email']); ?>" value="<?php echo $info['email'] ?>" disabled>
                            </div>
                            <div class="row gx-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small mb-1" for="inputPhone"><?php echo lang($langArray['phone']); ?></label>
                                    <input class="form-control" id="inputPhone" type="tel" name="phone" placeholder="<?php echo lang($langArray['enter_phone']); ?>" value="<?php echo $info['phone'] ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="small mb-1" for="inputBirthday"><?php echo lang($langArray['birthday']); ?></label>
                                    <input class="form-control" id="inputBirthday" type="date" name="birthday" placeholder="<?php echo lang($langArray['enter_birthday']); ?>" value="<?php echo $info['birthDate'] ?>">
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit" name="update_info"><?php echo lang($langArray['save_changes']); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
} elseif ($do === 'info') {
?>
    <div class="container-xl px-4 mt-4">
        <nav class="nav nav-borders info-nav">
            <a class="nav-link" href="profile-settings.php"><?php echo lang($langArray['Profile']); ?></a>
            <a class="nav-link" href="profile-settings.php?do=orders"><?php echo lang($langArray['Orders']); ?></a>
            <a class="nav-link" href="profile-settings.php?do=wishlist"><?php echo lang($langArray['Wishlist']); ?></a>
            <a class="nav-link active ms-0" href="profile-settings.php?do=info"><?php echo lang($langArray['Personal Info']); ?></a>
        </nav>
        <hr class="mt-0 mb-4">

        <?php
        if (isset($_SESSION['alert_message'])) {
            echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
            echo $_SESSION['alert_message']['message'];
            echo '</div>';
            unset($_SESSION['alert_message']);
        }
        ?>

        <div class="container">
            <div class="d-flex justify-content-center">
                <div class="col-sm-7 info-cont">
                    <h1><?php echo lang($langArray['My Profile']); ?></h1>
                    <div class="accordion" id="myAccordion">

                        <!-- My Ads Section -->
                        <?php
                        $result = getItems('user_id', $user_id, NULL);
                        $items = $result['items'];
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAds">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAds" aria-expanded="true" aria-controls="collapseAds">
                                    <?php echo lang($langArray['My Ads']); ?>
                                    <span class="expand-icon-wrap ms-auto"><i class="fa fa-minus"></i></span>
                                </button>
                            </h2>
                            <div id="collapseAds" class="accordion-collapse collapse show" aria-labelledby="headingAds" data-bs-parent="#myAccordion">
                                <div class="accordion-body">
                                    <a href="newAd.php?do=Add" class="newAd-link ms-2"><?php echo lang($langArray['New Ad?']); ?></a>
                                    <div class="scrollable-row">
                                        <?php if ($result['count'] <= 0): ?>
                                            <div class="alert alert-warning text-center w-100" role="alert">
                                                <?php echo lang($langArray['No Ads to Show. Create']); ?>
                                                <a href="newAd.php?do=Add"><?php echo lang($langArray['New Ad']); ?></a>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($items as $item): ?>
                                                <div class="item-container d-inline-block me-3">
                                                    <div class="card item-box">
                                                        <div class="<?php echo $item['approve'] ? 'approved' : 'approve'; ?>">
                                                            <?php echo lang($item['approve'] ? 'Approved' : 'Waiting Approval'); ?>
                                                        </div>
                                                        <div class="card_up">
                                                            <span class="price-tag text-primary fw-bold">$<?php echo $item['price']; ?></span>
                                                            <div class="btns">
                                                                <a href="newAd.php?do=Edit&item_id=<?php echo $item['item_id']; ?>" class=  "edit-btn" title="<?php echo lang($langArray['edit-item']) ?>"><i class='fa fa-edit'></i></a>
                                                                <a href="newAd.php?do=Deletet&item_id=<?php echo $item['item_id']; ?>" class="delete-btn" title="<?php echo lang($langArray['delete-item']) ?>" onclick="return confirm('<?php echo lang($langArray['Are you sure to delete?']) ?>')"><i class='fa fa-trash'></i></a>
                                                            </div>
                                                        </div>
                                                        <img class="card-img-top img-fluid img-thumbnail" src="<?php echo displayImage($item['item_id'], 0) ?>" alt="<?php echo $item['item_name']; ?>">
                                                        <?php
                                                        $item_desc = $item['description'];
                                                        $item_name = $item['item_name'];
                                                        $maxLength = 29;

                                                        // Check if the description length exceeds the limit
                                                        $trimmedDescription = strlen($item_desc) > $maxLength ? substr($item_desc, 0, $maxLength - 3) . '...' : $item_desc;
                                                        $trimmedname = strlen($item_name) > $maxLength ? substr($item_name, 0, $maxLength - 3) . '...' : $item_name;
                                                        ?>
                                                        <div class="card-body card-body-info">
                                                            <a class="card-title card-title-info" href="items.php?itemid=<?php echo $item['item_id']; ?>"><?php echo $trimmedname; ?></a>
                                                            <p class="card-text text-muted"><?php echo $trimmedDescription; ?></p>
                                                        </div>
                                                        <span class="date text-muted"><?php echo $item['add_date']; ?></span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- My Latest Comments Section -->
                        <?php
                        $stmt = $con->prepare("SELECT comments.*, items.item_name AS item_name FROM comments INNER JOIN items ON items.item_id = comments.item_id WHERE comments.user_id = ?");
                        $stmt->execute([$user_id]);
                        $user_comments = $stmt->fetchAll();
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingComments">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComments" aria-expanded="true" aria-controls="collapseComments">
                                    <?php echo lang($langArray['My Latest Comments']); ?>
                                    <span class="expand-icon-wrap ms-auto"><i class="fa fa-minus"></i></span>
                                </button>
                            </h2>
                            <div id="collapseComments" class="accordion-collapse collapse show" aria-labelledby="headingComments" data-bs-parent="#myAccordion">
                                <div class="accordion-body">
                                    <div class="scrollable-comments">
                                        <div class="row">
                                            <?php if (empty($user_comments)): ?>
                                                <div class="alert alert-warning text-center" role="alert"><?php echo lang($langArray['No comments to show.']); ?></div>
                                            <?php else: ?>
                                                <?php foreach ($user_comments as $comment): ?>
                                                    <div class="col-md-10">
                                                        <div class="card comment-card shadow-sm mb-4">
                                                            <div class="card-body">
                                                                <p class="member-comment mb-2"><?php echo $comment['comment']; ?></p>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted"><?php echo lang($langArray['Commented on:']); ?> <?php echo $comment['comment_date']; ?></span>
                                                                    <div class="<?php echo $comment['status'] == 1 ? 'statusyes' : 'statusno'; ?>">
                                                                        <?php echo lang($comment['status'] == 1 ? 'Approved' : 'Waiting Approval'); ?>
                                                                    </div>
                                                                    <span class="text-primary"><?php echo lang($langArray['Item:']); ?> <?php echo $comment['item_name']; ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
} elseif ($do == 'orders') {

    // Set default sort option if not set
    $sortOption = isset($_GET['sortOptions']) ? $_GET['sortOptions'] : 'all';

    // Prepare the query based on the selected filter
    if ($sortOption == "approved") {
        $stmt = $con->prepare("SELECT * FROM orders WHERE user_id = ? AND payment_status = 'completed'");
    } elseif ($sortOption == "pending") {
        $stmt = $con->prepare("SELECT * FROM orders WHERE user_id = ? AND payment_status = 'Pending'");
    } elseif ($sortOption == "rejected") {
        $stmt = $con->prepare("SELECT * FROM orders WHERE user_id = ? AND payment_status = 'Failed'");
    } else {
        $stmt = $con->prepare("SELECT * FROM orders WHERE user_id = ?");
    }

    $stmt->execute(array($_SESSION['user_id']));
    $user_orders = $stmt->fetchAll();
?>
    <!-- Start outputting HTML -->
    <div class="container-content">
        <div class="container-xl px-4 mt-4">
            <nav class="nav nav-borders info-nav">
                <a class="nav-link" href="profile-settings.php"><?php echo lang($langArray['Profile']); ?></a>
                <a class="nav-link active ms-0" href="profile-settings.php?do=orders"><?php echo lang($langArray['Orders']); ?></a>
                <a class="nav-link" href="profile-settings.php?do=wishlist"><?php echo lang($langArray['Wishlist']); ?></a>
                <a class="nav-link" href="profile-settings.php?do=info"><?php echo lang($langArray['Personal Info']); ?></a>
            </nav>
            <hr class="mt-0 mb-4">
            <div class="container-xl mt-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?php echo lang($langArray['Order history']); ?></strong>
                        <!-- Filter Form -->
                        <form method="GET" action="profile-settings.php" class="ms-auto">
                            <!-- <input type="hidden" name="do" value="orders"> -->
                            <label for="sortOptions" class="filter-label me-2"><i class="fa fa-filter"></i> <?php echo lang($langArray['Filter history']); ?></label>
                            <select class="form-select" id="sortOptions" name="sortOptions" onchange="this.form.submit()">
                                <option value="all" <?php if ($sortOption == 'all') echo 'selected'; ?>><?php echo lang($langArray['All orders']); ?></option>
                                <option value="approved" <?php if ($sortOption == 'approved') echo 'selected'; ?>><?php echo lang($langArray['Completed orders']); ?></option>
                                <option value="pending" <?php if ($sortOption == 'pending') echo 'selected'; ?>><?php echo lang($langArray['Pending orders']); ?></option>
                                <option value="rejected" <?php if ($sortOption == 'rejected') echo 'selected'; ?>><?php echo lang($langArray['Rejected orders']); ?></option>
                            </select>
                        </form>
                    </div>

                    <div class="card-body">
                        <?php if ($stmt->rowCount() > 0): ?>
                            <?php foreach ($user_orders as $order): ?>
                                <div class="row mb-3">
                                    <div class="order-content">
                                        <div class="order-details">
                                            <a href="#" title="<?php echo lang($langArray['View details']); ?>" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['order_id']; ?>">
                                                <i class='fa fa-eye'></i>
                                            </a>
                                            <span><strong><?php echo lang($langArray['Order']); ?> #<?php echo htmlspecialchars($order['order_id']); ?>: </strong></span>
                                            <div class="order-info">
                                                <div><?php echo lang($langArray['Cost']); ?>: $<?php echo htmlspecialchars($order['total_price']); ?> </div>
                                                <div class="text-muted order-date"><?php echo lang($langArray['Order made on']); ?>: <?php echo htmlspecialchars($order['order_date']); ?></div>
                                            </div>
                                        </div>
                                        <?php if ($order['payment_status'] == "Pending"): ?>
                                            <span class="badge bg-warning"><?php echo htmlspecialchars(lang($langArray['Pending'])); ?></span>
                                        <?php elseif ($order['payment_status'] == "Completed"): ?>
                                            <span class="badge bg-success"><?php echo htmlspecialchars(lang($langArray['Completed'])); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo htmlspecialchars(lang($langArray['Failed'])); ?></span>
                                        <?php endif ?>
                                    </div>
                                </div>

                                <!-- Modal for Order Items -->
                                <div class="modal fade" id="orderDetailsModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-labelledby="orderDetailsLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="orderDetailsLabel<?php echo $order['order_id']; ?>"><?php echo lang($langArray['Order Items']); ?> - <?php echo lang($langArray['Order']); ?> #<?php echo htmlspecialchars($order['order_id']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo lang($langArray['Close']); ?>"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php
                                                $stmt_items = $con->prepare("SELECT 
                                                                                order_items.* ,
                                                                                items.item_name,
                                                                                items.image
                                                                            FROM 
                                                                                order_items
                                                                            INNER JOIN
                                                                                items
                                                                            ON
                                                                                order_items.item_id = items.item_id
                                                                            WHERE
                                                                                order_id = ?
                                                                            ");
                                                $stmt_items->execute(array($order['order_id']));
                                                $order_items = $stmt_items->fetchAll();

                                                if ($order_items): ?>
                                                    <ul class="list-group">
                                                        <?php foreach ($order_items as $item): ?>
                                                            <li class="list-group-item">
                                                                <div class="order_item_details">
                                                                    <img src="<?php echo displayImage($item['item_id'], 0) ?>" alt="" width="95" height="95">
                                                                    <div class="order_item_content">
                                                                        <a href="items.php?itemid=<?php echo intval($item['item_id']) ?>"><?php echo htmlspecialchars($item['item_name']); ?></a>
                                                                        <span><?php echo lang($langArray['Quantity']); ?>: <span class="order_item_quantity"><?php echo htmlspecialchars($item['quantity']); ?></span></span>
                                                                        <span><?php echo lang($langArray['Price']); ?>: <span class="order_item_price">$<?php echo htmlspecialchars($item['price']); ?></span></span>
                                                                    </div>
                                                                    <?php if ($item['status'] == "Pending"): ?>
                                                                        <span class="badge bg-warning"><?php echo htmlspecialchars(lang($langArray['Pending'])); ?></span>
                                                                    <?php elseif ($item['status'] == "Approved"): ?>
                                                                        <span class="badge bg-success"><?php echo htmlspecialchars(lang($langArray['Approved'])); ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger"><?php echo htmlspecialchars(lang($langArray['Failed'])); ?></span>
                                                                    <?php endif ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div><?php echo lang($langArray['No items found for this order.']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo lang($langArray['Close']); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning text-center" role="alert"><?php echo lang($langArray['No Orders to show.']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
} elseif ($do == 'wishlist') {

    $errors = [];
    $successMessage = '';

    // Fetch wishlist items for the logged-in user
    $stmt = $con->prepare("SELECT 
                                wishlist.*,
                                items.item_name,
                                items.image,
                                items.price,
                                items.quantity
                            FROM 
                                wishlist
                            INNER JOIN
                                items
                            ON
                                wishlist.item_id = items.item_id
                            WHERE 
                                wishlist.user_id = ?");
    $stmt->execute(array($_SESSION['user_id']));
    $wishlistItems = $stmt->fetchAll();
    $count = $stmt->rowCount();

    // Handle item removal from wishlist
    if (isset($_POST['remove_from_list'])) {
        $itemid = $_POST['item_id'];
        $stmt = $con->prepare("DELETE FROM wishlist WHERE item_id = ? AND user_id = ?");
        if ($stmt->execute(array($itemid, $_SESSION['user_id']))) {
            $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray["Item removed from wishlist successfully."])];
            header("Location: profile-settings.php?do=wishlist");
            exit();
        } else {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray["Failed to remove item from wishlist."])];
        }
    }

    // Handle clearing the entire wishlist
    if (isset($_POST['clear_wishlist'])) {
        if ($count > 0) {
            $stmt = $con->prepare("DELETE FROM wishlist WHERE user_id = ?");
            if ($stmt->execute(array($_SESSION['user_id']))) {
                $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray["Wishlist cleared successfully."])];
                header("Location: profile-settings.php?do=wishlist");
                exit();
            } else {
                $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray["Failed to clear wishlist."])];
            }
        } else {
            $_SESSION['alert_message'] = ['type' => 'warning', 'message' => lang($langArray["clear_items"])];
        }
    }
?>

    <div class="container-content">
        <div class="container-xl px-4 mt-4">
            <nav class="nav nav-borders info-nav">
                <a class="nav-link" href="profile-settings.php"><?php echo lang($langArray['Profile']); ?></a>
                <a class="nav-link ms-0" href="profile-settings.php?do=orders"><?php echo lang($langArray['Orders']); ?></a>
                <a class="nav-link active" href="profile-settings.php?do=wishlist"><?php echo lang($langArray['Wishlist']); ?></a>
                <a class="nav-link" href="profile-settings.php?do=info"><?php echo lang($langArray['Personal Info']); ?></a>
            </nav>
            <hr class="mt-0 mb-4">

            <?php
            if (isset($_SESSION['alert_message'])) {
                echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
                echo $_SESSION['alert_message']['message'];
                echo '</div>';
                unset($_SESSION['alert_message']);
            }
            ?>

            <div class="table-responsive wishlist-table margin-bottom-none">
                <form action="profile-settings.php?do=wishlist" method="post">
                    <table class="table wishlist-table">
                        <thead>
                            <tr>
                                <th><?php echo lang($langArray['Product Name']); ?></th>
                                <th class="text-center">
                                    <button type="submit" name="clear_wishlist" class="btn btn-sm btn-outline-danger"><?php echo lang($langArray['Clear']); ?></button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($count > 0): ?>
                                <?php foreach ($wishlistItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-item">
                                                <a class="product-thumb" href=""><img src="<?php echo displayImage($item['item_id'], 0) ?>" alt="<?php echo lang($langArray['Product']); ?>"></a>
                                                <div class="product-info">
                                                    <h4 class="product-title"><a href="items.php?itemid=<?php echo $item['item_id'] ?>"><?php echo $item['item_name'] ?></a></h4>
                                                    <div class="text-lg text-medium text-muted">$<?php echo $item['price'] ?></div>
                                                    <div><?php echo lang($langArray['Availability']); ?>:
                                                        <span <?php echo ($item['quantity'] == 0) ? 'class="text-danger">' . lang($langArray['Out of Stock']) : 'class="text-success">' . lang($langArray['In Stock']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <form action="profile-settings.php?do=wishlist" method="post" style="display:inline;">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?>">
                                                <button type="submit" class="btn btn-danger" name="remove_from_list">
                                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <div class="alert alert-warning text-center" role="alert"><?php echo lang($langArray['No items to show.']); ?></div>
                                    </td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
<?php
}

include $tmp . 'footer.php';
ob_end_flush();
?>