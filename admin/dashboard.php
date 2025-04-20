<?php
session_start();
if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) {
    $pageTitle = 'Dashboard';
    include 'init.php';

    $latestUsers = 4;
    $latest = getLatest("*", "users", "user_id", $latestUsers);

    $latestItems = 4;
    $latestItem = getLatest("*", "items", "item_id", $latestItems);

    $latestComments = 4;
    $latestComment = getLatest("*", "comments", "c_id", $latestComments);

    $latestCodes = 4;
    $latestCode = getLatest("*", "promo_codes", "id", $latestCodes);

    $id = $_SESSION['user_id'];
?>
    <!-- Start Dashboard Page -->
    <div class="container home-stats text-center">
        <h1>Dashboard</h1>
        <div class="row">
            <div class="col-md-3">
                <a href="members.php?page-nr=1" class="home-cards">
                    <div class="stat st-members">
                        <i class="fa fa-users fa-users-card" aria-hidden="true"></i>
                        <div class="content-card">
                            Total Members
                            <span><?php contItems($id, 'users'); ?></span>
                        </div>
                    </div>
                </a>

            </div>
            <div class="col-md-3">
                <a href="members.php?do=Manage&Page=Pending&page-nr=1" class="home-cards">
                    <div class="stat st-pending">
                        <i class="fa fa-user-plus fa-users-card" aria-hidden="true"></i>
                        <div class="content-card">
                            Pending Members
                            <span><?php echo checkItem('reg_stat', 'users', 0); ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="items.php?page-nr=1" class="home-cards">
                    <div class="stat st-items">
                        <i class="fa fa-tag fa-users-card" aria-hidden="true"></i>
                        <div class="content-card">
                            Total Items
                            <span><?php contItems($id, 'items'); ?></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="comments.php" class="home-cards">
                    <div class="stat st-comments">
                        <i class="fa fa-comments fa-users-card" aria-hidden="true"></i>
                        <div class="content-card">
                            Total Comments
                            <span><?php contItems($id, 'comments'); ?></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="container latest">
        <div class="row row-1">
            <div class="col-sm-6">
                <div class="card-1">
                    <div class="card-header card-header-plus">
                        <h6 class="card-title"><i class="fa fa-users"></i> Latest <?php echo $latestUsers . ' ' ?> Registered Users</h6>
                        <i class="fa fa-plus toggle-card fa-plus-home" aria-hidden="true" data-target=".card-body-users"></i>
                    </div>
                    <div class="card-body card-body-users" style="display: none;"> <!-- Hidden initially -->
                        <ul class="list-unsyled latest-users">
                            <?php
                            foreach ($latest as $user):
                                $id = $user['user_id'];
                            ?>
                                <li> <?php echo $user['first_name'] . ' ' . $user['last_name'] ?>
                                    <a href="members.php?do=Edit&userid=<?php echo $id ?>">
                                        <span class="edit-btn pull-right">
                                            <i class='fa fa-edit' title="edit member"></i>
                                            <?php if ($user['reg_stat'] == 0): ?>
                                                <a class='approve-btn pull-right' title='Activate member' href='members.php?do=Active&userid=<?php echo $id ?>'><i class='fa fa-check' aria-hidden='true'></i></a>
                                            <?php endif ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card-2">
                    <div class="card-header card-header-plus">
                        <h6 class="card-title"><i class="fa fa-tag" aria-hidden="true"></i> Latest <?php echo $latestItems . ' ' ?> Items</h6>
                        <i class="fa fa-plus toggle-card fa-plus-home" aria-hidden="true" data-target=".card-body-items"></i>
                    </div>
                    <div class="card-body card-body-items" style="display: none;"> <!-- Hidden initially -->
                        <ul class="list-unsyled latest-users">
                            <?php
                            foreach ($latestItem as $item):
                                $itemid = $item['item_id'];
                            ?>
                                <li><?php echo $item['item_name'] ?>
                                    <a href="items.php?do=Edit&itemid= <?php echo $itemid ?>">
                                        <span class="edit-btn pull-right">
                                            <i class='fa fa-edit' title="edit item"></i>
                                            <?php if ($item['approve'] == 0) : ?>
                                                <a class='approve-btn pull-right' title='Approve item' href='items.php?do=Approve&itemid=<?php echo $itemid ?>'><i class='fa fa-check' aria-hidden='true'></i></a>
                                            <?php endif ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-2">
            <div class="col-sm-6">
                <div class="card-3">
                    <div class="card-header card-header-plus">
                        <h6 class="card-title"><i class="fa fa-comments" aria-hidden="true"></i> Latest <?php echo $latestComments . ' ' ?> Comments</h6>
                        <i class="fa fa-plus toggle-card fa-plus-home" aria-hidden="true" data-target="#card-body-comments"></i>
                    </div>
                    <div class="card-body card-body-comments" id="card-body-comments" style="display: none;">
                        <ul class="list-unstyled latest-comments">
                            <?php
                            // Select all items
                            $stmt = $con->prepare("SELECT 
                                                comments.*, 
                                                users.first_name AS Member
                                            FROM 
                                                comments
                                            INNER JOIN 
                                                users 
                                            ON 
                                                users.user_id = comments.user_id
                                        ");
                            $stmt->execute();
                            $latestComment = $stmt->fetchAll(); // Fetch all the users
                            ?>
                            <?php foreach ($latestComment as $comment): ?>
                                <li>
                                    <div class="comment-box">
                                        <span class="member-name"> <?php echo $comment['Member'] ?> </span>
                                        <p class="member-comment"><?php echo $comment['comment'] ?> </p>
                                    </div>
                                    <div class="c-btn">
                                        <a href="comments.php?do=Edit&cid= <?php echo $comment['c_id'] ?>" class="edit-btn"><i class='fa fa-edit' title="edit item"></i></a>
                                        <?php if ($comment['status'] == 0): ?>
                                            <a href="comments.php?do=Approve&cid=<?php echo $comment['c_id'] ?>" class="approve-btn"><i class='fa fa-check' aria-hidden='true'></i></a>
                                        <?php endif ?>
                                    </div>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="card-2">
                    <div class="card-header card-header-plus">
                        <h6 class="card-title"><i class="fa fa-tag" aria-hidden="true"></i> Latest <?php echo $latestCodes . ' ' ?> Codes</h6>
                        <i class="fa fa-plus toggle-card fa-plus-home" aria-hidden="true" data-target=".card-body-codes"></i>
                    </div>
                    <div class="card-body card-body-codes" style="display: none;"> <!-- Hidden initially -->
                        <ul class="list-unsyled latest-users">
                            <?php
                            foreach ($latestCode as $code):
                                $codeid = $code['id'];
                            ?>
                                <li><?php echo $code['code'] ?>
                                    <a href="promo_codes.php?do=Edit&id= <?php echo $codeid ?>">
                                        <span class="edit-btn pull-right">
                                            <i class='fa fa-edit' title="edit code"></i>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Dashboard Page -->
<?php
    include $tmp . 'footer.php';
} else {
    header('Location: ../login.php');
    exit();
}
