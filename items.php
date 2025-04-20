    <?php
    ob_start();
    session_start();
    $pageTitle = 'Items';
    include 'init.php';

    if (isset($_GET['itemid'])) {

        $itemid = intval($_GET['itemid']);

        // Fetch item details
        $stmt = $con->prepare("SELECT
                                    items.*,
                                    users.email AS email,
                                    users.first_name AS f_name,
                                    users.last_name AS l_name
                                FROM
                                    items
                                INNER JOIN
                                    users
                                ON
                                    users.user_id = items.user_id
                                WHERE
                                    item_id = ?");
        $stmt->execute(array($itemid));
        $item = $stmt->fetch();

        if ($item['approve'] == 0 || !is_numeric($_GET['itemid'])) {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-approved'])];
            header('Location: profile-settings.php?do=info');
            exit();
        }

        // If item not found
        if (!$item) {
            echo "Item not found!";
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Handle the review submission
            if (isset($_POST['comment'])) {
                if (!isset($_SESSION['user_id'])) {
                    // If the user is not signed in, show the sign-in alert
                    echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['not_signed_in-comment']) . '</div>';
                } else {
                    // Check if the user is Purchase Verified to rate or comment
                    $verify = $con->prepare("SELECT COUNT(*) as order_count
                                            FROM orders 
                                            INNER JOIN order_items ON orders.order_id = order_items.order_id 
                                            WHERE orders.user_id = ? 
                                            AND order_items.item_id = ? 
                                            AND orders.payment_status = 'completed'");
                    $verify->execute(array($_SESSION['user_id'], $itemid));
                    $order_count = $verify->fetchColumn();

                    if ($order_count > 0) {
                        $user_id = $_SESSION['user_id'];
                        $comment = filter_var($_POST['comment'], FILTER_SANITIZE_STRING);

                        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;

                        if (!empty($comment) && $rating !== null) {
                            // Check if a comment already exists from this user for the item
                            $stmtCheck = $con->prepare("SELECT * FROM comments WHERE item_id = ? AND user_id = ?");
                            $stmtCheck->execute(array($itemid, $user_id));
                            $existingComment = $stmtCheck->fetch();

                            if ($existingComment) {
                                // Update the existing comment with the new content and rating
                                $stmtUpdate = $con->prepare("UPDATE comments SET comment = ?, comment_date = NOW() WHERE item_id = ? AND user_id = ?");
                                $stmtUpdate->execute(array($comment, $itemid, $user_id));
                            } else {
                                // Insert a new comment
                                $stmtInsert = $con->prepare("INSERT INTO comments(comment, item_id, user_id, comment_date) VALUES (?, ?, ?, NOW())");
                                $stmtInsert->execute(array($comment, $itemid, $user_id));
                            }

                            // Insert or update the rating
                            $stmtRateCheck = $con->prepare("SELECT * FROM ratings WHERE item_id = ? AND user_id = ?");
                            $stmtRateCheck->execute(array($itemid, $user_id));
                            $existingRate = $stmtRateCheck->fetch();

                            if ($existingRate) {
                                // Update existing rating
                                $stmtRateUpdate = $con->prepare("UPDATE ratings SET rating_value = ? WHERE item_id = ? AND user_id = ?");
                                $stmtRateUpdate->execute(array($rating, $itemid, $user_id));
                            } else {
                                // Insert new rating
                                $stmtRateInsert = $con->prepare("INSERT INTO ratings(item_id, user_id, rating_value) VALUES (?, ?, ?)");
                                $stmtRateInsert->execute(array($itemid, $user_id, $rating));
                            }

                            // Redirect with success
                            header("Location: items.php?itemid=$itemid&success=1");
                            exit;
                        } else {
                            header("Location: items.php?itemid=$itemid&success=2");
                            exit;
                        }
                    } else {
                        header("Location: items.php?itemid=$itemid&success=5");
                        exit;
                    }
                }
            }

            // Handle rating submission
            if (isset($_POST['rating'])) {
                $rating = intval($_POST['rating']);
                if (!isset($_SESSION['user_id'])) {
                    echo '<div class="alert alert-danger">You must sign in to submit a rating!</div>';
                } else {
                    // Check if user is purchase verified
                    $verify = $con->prepare("SELECT order_id FROM orders WHERE user_id=? AND item_id=? AND order_status = 'completed'");
                    $verify->execute(array($_SESSION['user_id'], $itemid));
                    $order_count = $verify->rowCount();
                    if ($order_count > 0) {
                        $user_id = $_SESSION['user_id'];
                        // Check if user already rated
                        $stmtCheck = $con->prepare("SELECT * FROM ratings WHERE item_id = ? AND user_id = ?");
                        $stmtCheck->execute(array($itemid, $user_id));
                        $existingRating = $stmtCheck->fetch();
                        if ($existingRating) {
                            // Update rating
                            $stmtUpdate = $con->prepare("UPDATE ratings SET rating_value = ? WHERE item_id = ? AND user_id = ?");
                            $stmtUpdate->execute(array($rating, $itemid, $user_id));
                        } else {
                            // Insert rating
                            $stmtInsert = $con->prepare("INSERT INTO ratings(rating_value, item_id, user_id) VALUES (?, ?, ?)");
                            $stmtInsert->execute(array($rating, $itemid, $user_id));
                        }
                        header("Location: items.php?itemid=$itemid&success=6");
                        exit;
                    } else {
                        header("Location: items.php?itemid=$itemid&success=5");
                        exit;
                    }
                }
            }

            if (isset($_POST['addListSubmit'])) {
                if (!isset($_SESSION['user_id'])) {
                    echo '<div class="alert alert-danger">You must sign in to Add this item to your Wishlist!</div>';
                } else {
                    $user_id = $_SESSION['user_id'];
                    // Check if item already in wishlist
                    $stmtCheck = $con->prepare("SELECT * FROM wishlist WHERE item_id = ? AND user_id = ?");
                    $stmtCheck->execute(array($itemid, $user_id));
                    $existingitem = $stmtCheck->fetch();

                    if ($existingitem) {
                        header("Location: items.php?itemid=$itemid&success=3");
                        exit;
                    } else {
                        // Insert rating
                        $stmtInsert = $con->prepare("INSERT INTO wishlist(item_id, user_id) VALUES (?, ?)");
                        $stmtInsert->execute(array($itemid, $user_id));
                    }
                    header("Location: items.php?itemid=$itemid&success=4");
                    exit;
                }
            }
        }

        // Check if the user is logged in
        if (isset($_SESSION['user_id']) || (isset($_SESSION['user_id']) && $_GET['sortOptions'])) {
            // Logged-in user
            $user_id = $_SESSION['user_id'];

            // Check if the logged-in user has already viewed this item
            $stmt = $con->prepare("SELECT COUNT(*) FROM item_views WHERE item_id = ? AND user_id = ?");
            $stmt->execute([$itemid, $user_id]);
            $hasViewed = $stmt->fetchColumn();

            if (!$hasViewed) {
                // Record the view
                $stmt = $con->prepare("INSERT INTO item_views (item_id, user_id) VALUES (?, ?)");
                $stmt->execute([$itemid, $user_id]);

                // Increment the item's view count
                $stmt = $con->prepare("UPDATE items SET views = views + 1 WHERE item_id = ?");
                $stmt->execute([$itemid]);
            }
        } else {
            // Anonymous user (guest)
            // Use a combination of IP and User-Agent as a fallback for guest users
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $unique_user = md5($user_ip . $user_agent); // Generate unique identifier

            // Use a cookie to avoid recounting the view from the same guest within a session
            if (!isset($_COOKIE['viewed_' . $itemid])) {
                // Set a cookie to last 1 day
                setcookie('viewed_' . $itemid, true, time() + 86400); // 1 day

                // Check if the guest user has already viewed this item
                $stmt = $con->prepare("SELECT COUNT(*) FROM item_views WHERE item_id = ? AND unique_user = ?");
                $stmt->execute([$itemid, $unique_user]);
                $hasViewed = $stmt->fetchColumn();

                if (!$hasViewed) {
                    // Record the view
                    $stmt = $con->prepare("INSERT INTO item_views (item_id, unique_user) VALUES (?, ?)");
                    $stmt->execute([$itemid, $unique_user]);

                    // Increment the item's view count
                    $stmt = $con->prepare("UPDATE items SET views = views + 1 WHERE item_id = ?");
                    $stmt->execute([$itemid]);
                }
            }
        }

        // Display item details
    ?>
        <div class="container">
            <!-- product -->
            <div class="product-content product-wrap clearfix product-deatil">
                <?php
                if (isset($_GET['success']) && $_GET['success'] == 1) {
                    echo '<div class="alert alert-success success-message" role="alert">' . lang($langArray['comment_submitted']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 2) {
                    echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['comment_empty']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 3) {
                    echo '<div class="alert alert-warning success-message" role="alert">' . lang($langArray['item_in_wishlist']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 4) {
                    echo '<div class="alert alert-success success-message" role="alert">' . lang($langArray['item_added_wishlist']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 5) {
                    echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['verified_buyer']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 8) {
                    echo '<div class="alert alert-success success-message" role="alert">' . lang($langArray['item_added_cart']) . '</div>';
                } elseif (isset($_GET['success']) && $_GET['success'] == 9) {
                    echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['delete_item_failed']) . '</div>';
                }

                ?>
                <div class="row row-show-item">
                    <!-- Product Images Carousel -->
                    <div class="col-md-5 col-sm-12 col-xs-12">
                        <div class="product-image">
                            <div id="myCarousel-2" class="carousel slide" data-bs-ride="carousel">
                                <!-- Indicators -->
                                <ol class="carousel-indicators">
                                    <li data-bs-target="#myCarousel-2" data-bs-slide-to="0" class=""></li>
                                    <li data-bs-target="#myCarousel-2" data-bs-slide-to="1" class="active"></li>
                                    <li data-bs-target="#myCarousel-2" data-bs-slide-to="2" class=""></li>
                                </ol>
                                <!-- Carousel Items -->
                                <div class="carousel-inner">
                                    <!-- Slide 1 -->
                                    <div class="carousel-item active">
                                        <div class="image_hold">
                                            <img src="<?php echo displayImage($item['item_id'], 0) ?>" class="d-block w-100 img-responsive" alt="" />
                                        </div>
                                    </div>
                                    <!-- Slide 2 -->
                                    <div class="carousel-item">
                                        <div class="image_hold">
                                            <img src="<?php echo displayImage($item['item_id'], 1) ?>" class="d-block w-100 img-responsive" alt="" />
                                        </div>
                                    </div>
                                    <!-- Slide 3 -->
                                    <div class="carousel-item">
                                        <div class="image_hold">
                                            <img src="<?php echo displayImage($item['item_id'], 2) ?>" class="d-block w-100 img-responsive" alt="" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Controls -->
                                <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel-2" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?php echo lang($langArray['previous']) ?></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#myCarousel-2" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden"><?php echo lang($langArray['next']) ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Product Details -->
                    <div class="col-md-6 col-md-offset-1 col-sm-12 col-xs-12">
                        <h2 class="name">
                            <?php echo $item['item_name'] ?>
                            <small><?php echo lang($langArray['Product_by']) ?> <a href="javascript:void(0);"><?php echo $item['f_name'] . ' ' . $item['l_name'] ?></a></small>

                            <span class="stars" id="rating-stars" data-itemid="<?php echo $itemid; ?>">
                                <?php
                                $avgRating = getAvgRate($itemid);
                                $ratingCount = getRateCount($itemid);

                                // Calculate the whole and fractional parts of the average rating
                                $wholeRating = floor($avgRating); // Get the whole number part
                                $fractionalRating = $avgRating - $wholeRating; // Get the fractional part

                                // Display stars based on the average rating
                                for ($count_star = 1; $count_star <= 5; $count_star++) {
                                    if ($count_star <= $wholeRating) {
                                        // Full star for whole ratings
                                        echo '<i class="fa fa-star fa-2x star-rate" data-rating="' . $count_star . '"></i>';
                                    } elseif ($count_star == $wholeRating + 1 && $fractionalRating >= 0.5) {
                                        // Half star if there's a fractional rating of 0.5 or more
                                        echo '<i class="fa fa-star-half-o fa-2x star-rate" data-rating="' . ($wholeRating + 0.5) . '"></i>';
                                    } else {
                                        // Empty star for remaining
                                        echo '<i class="fa fa-star-o fa-2x star-rate" data-rating="' . $count_star . '"></i>';
                                    }
                                }
                                ?>
                            </span>
                            <!-- Number of votes and customer reviews -->
                            <span class="fa fa-2x overall-rating">
                                <h5>(<?php echo lang($langArray['Average_Rate']) ?> <span id="avgrat"><?php echo round($avgRating, 1) ?></span> <?php echo lang($langArray['Based_on']) ?> <span id="totalrat"><?php echo $ratingCount ?> <?php echo lang($langArray['Ratings']) ?>)</span></h5>
                            </span>

                            <?php
                            // Fetch and display available comments from Purchase Verified users
                            $stmt4 = $con->prepare("SELECT DISTINCT 
                                                        comments.*,
                                                        users.email,
                                                        users.first_name AS f_name,
                                                        users.last_name AS l_name,
                                                        users.user_avatar AS profile_pic
                                                    FROM 
                                                        comments
                                                    INNER JOIN 
                                                        users ON users.user_id = comments.user_id
                                                    INNER JOIN 
                                                        order_items ON order_items.item_id = comments.item_id
                                                    INNER JOIN 
                                                        orders ON orders.user_id = comments.user_id
                                                    WHERE
                                                        comments.item_id = ?
                                                    AND
                                                        comments.status = 1
                                                    AND
                                                        orders.payment_status = 'completed'
                                                    GROUP BY 
                                                        comments.c_id
                                                ");
                            $stmt4->execute(array($itemid));
                            $comments = $stmt4->fetchAll();

                            ?>
                            <a href="javascript:void(0);" id="goToReviews"><?php echo $stmt4->rowCount();
                                                                            echo lang($langArray['customer_reviews']) ?></a>
                        </h2>
                        <div><?php echo lang($langArray['Availability']); ?>:
                            <span <?php echo ($item['quantity'] == 0) ? 'class="text-danger">' . lang($langArray['Out of Stock']) : 'class="text-success">' . lang($langArray['In Stock']); ?></span>
                        </div>
                        <hr />
                        <h3 class="price-container">
                            $<?php echo $item['price'] ?> <small><?php echo lang($langArray['*includes_tax']) ?></small>
                        </h3>
                        <hr />
                        <!-- Product Tabs -->
                        <div class="description description-tabs">
                            <ul id="myTab" class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link" id="description-tab" data-bs-toggle="tab" href="#more-information" role="tab"><?php echo lang($langArray['Product_Description']) ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="specifications-tab" data-bs-toggle="tab" href="#specifications" role="tab"><?php echo lang($langArray['Specifications']) ?></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="reviews-tab" data-bs-toggle="tab" href="#reviews" role="tab"><?php echo lang($langArray['Reviews']) ?></a>
                                </li>
                            </ul>
                            <!-- Tab Contents -->
                            <div id="myTabContent" class="tab-content">
                                <div class="tab-pane fade show active" id="more-information" role="tabpanel">
                                    <strong><?php echo lang($langArray['Description']) ?></strong>
                                    <p><?php echo $item['description'] ?></p>
                                </div>
                                <div class="tab-pane fade" id="specifications" role="tabpanel">
                                    <dl>
                                        <dt><?php echo lang($langArray['Specifications']) ?></dt>
                                        <dd>Product specifications content goes here.</dd>
                                    </dl>
                                </div>
                                <!-- Reviews Tab -->

                                <?php

                                ?>

                                <div class="tab-pane fade" id="reviews" role="tabpanel">
                                    <br />
                                    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?itemid=' . $itemid; ?>" class="well padding-bottom-10">
                                        <input type="hidden" name="itemid" value="<?php echo $itemid; ?>">
                                        <textarea rows="2" class="form-control" name="comment" placeholder="<?php echo lang($langArray['Write_a_review']) ?>" required></textarea>
                                        <div class="rate">
                                            <label for="rating">
                                                <?php echo lang($langArray['Rate_this_Item:']) ?>
                                            </label>
                                            <select name="rating" id="rating" class="form-select">
                                                <option value="1"><?php echo lang($langArray['1star']) ?></option>
                                                <option value="2"><?php echo lang($langArray['2star']) ?></option>
                                                <option value="3"><?php echo lang($langArray['3star']) ?></option>
                                                <option value="4"><?php echo lang($langArray['4star']) ?></option>
                                                <option value="5"><?php echo lang($langArray['5star']) ?></option>
                                            </select>
                                        </div>
                                        <div class="margin-top-10">
                                            <button type="submit" class="btn btn-sm btn-primary pull-right"><?php echo lang($langArray['Submit_Review']) ?></button>
                                        </div>
                                    </form>
                                    <div class="chat-body no-padding profile-message">
                                        <?php if (!empty($comments)): ?>
                                            <?php foreach ($comments as $comment):
                                                // Fetch user rating for the current comment's user
                                                $stmt6 = $con->prepare("SELECT * FROM ratings WHERE item_id = ? AND user_id = ?");
                                                $stmt6->execute(array($itemid, $comment['user_id']));
                                                $user_rate = $stmt6->fetch();
                                            ?>
                                                <ul>
                                                    <img src="<?php echo $comment['profile_pic'] ?>" class="online" />
                                                    <li class="message">
                                                        <span class="message-text">
                                                            <a href="javascript:void(0);" class="username"><?php echo $comment['f_name'] . ' ' . $comment['l_name'] ?> <span class="badge"> Purchase Verified</span>
                                                                <?php
                                                                // Check if the user has rated
                                                                if ($user_rate): // Only show stars if the user has rated
                                                                ?>
                                                                    <span class="user-rating-stars pull-right">
                                                                        <?php
                                                                        // Display full stars based on the user rating
                                                                        for ($count_star = 1; $count_star <= $user_rate['rating_value']; $count_star++): ?>
                                                                            <i class="fa fa-star fa-2x star-rate" data-rating="' . $count_star . '"></i>
                                                                        <?php endfor; ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </a>
                                                            <br />
                                                            <p class="comment">
                                                                <?php echo $comment['comment']; ?>
                                                            </p>
                                                            <span class="text-muted pull-right ultra-light"><?php echo lang($langArray['posted']) ?>
                                                                <?php echo get_time_ago(strtotime($comment['comment_date'])) ?>
                                                            </span>
                                                        </span>
                                                    </li>
                                                </ul>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="no_commentds"><?php echo lang($langArray['No_comments_available']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row row-btns">
                            <div class="col-sm-12 col-md-6 col-lg-6">
                                <form action="" method="get">
                                    <input type="hidden" name="item_idd" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" name="add-cart-detail" class="btn btn-primary btn-lg add-cart-btn"><?php echo lang($langArray['Add_to_cart']) ?> ($<?php echo $item['price'] ?>)</button>
                                </form>
                            </div>
                            <div class="col-sm-12 col-md-6 col-lg-6">
                                <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?itemid=' . $itemid; ?>" class="well padding-bottom-10">
                                    <div class="btn-group pull-right">
                                        <button type="submit" class="btn btn-white btn-default wishlist-sub" name="addListSubmit"><i class="fa fa-star"></i> <?php echo lang($langArray['Add_to_wishlist']) ?></button>
                                        <button class="btn btn-white btn-default contact-btn"><i class="fa fa-envelope"></i> <?php echo lang($langArray['Contact_Seller']) ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end product -->
        </div>


    <?php
    } elseif (!isset($_GET['itemid'])) { //Show All Items

    ?>
        <div class="container mt-4">
            <div class="container mt-4">
                <!-- Start Filter -->
                <form action="" method="get" class="filter-nav">
                    <div class="right-filter">
                        <div class="nr-result pull-right">
                            <?php
                            if (isset($_GET['pageid'])) {
                                $results = getItems('cat_id', $_GET['pageid'], '1');
                                $count = $results['count'];
                            } else {
                                $results = getItems(NULL, NULL, '1');
                                $count = $results['count'];
                            }
                            ?>
                            <span class="count-results"><?php echo $count ?></span>
                            <span class="results-txt"><?php echo $langArray['results']; ?></span>
                        </div>

                        <!-- Sort options -->
                        <div class="filter-item ms-auto">
                            <label for="sortOptions" class="filter-label me-2"><?php echo $langArray['sort_by']; ?></label>
                            <select class="form-select" id="sortOptions" name="sortOptions" onchange="this.form.submit()">
                                <option value="featured" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'featured') echo 'selected'; ?>><?php echo $langArray['featured']; ?></option>
                                <option value="price_low_high" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'price_low_high') echo 'selected'; ?>><?php echo lang($langArray['price_low_high']) ?></option>
                                <option value="price_high_low" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'price_high_low') echo 'selected'; ?>><?php echo lang($langArray['price_high_low']) ?></option>
                                <option value="newest_arrivals" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'newest_arrivals') echo 'selected'; ?>><?php echo lang($langArray['newest_arrivals']) ?></option>
                                <option value="most_viewed" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'most_viewed') echo 'selected'; ?>><?php echo lang($langArray['most_viewed']) ?></option>
                                <option value="best_sellers" <?php if (isset($_GET['sortOptions']) && $_GET['sortOptions'] == 'best_sellers') echo 'selected'; ?>><?php echo lang($langArray['best_sellers']) ?></option>
                            </select>
                            <!-- Include pageid in the query string -->
                            <?php if (isset($_GET['pageid'])): ?>
                                <input type="hidden" name="pageid" value="<?php echo $_GET['pageid']; ?>">
                                <input type="hidden" name="pagename" value="<?php echo $_GET['pagename']; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <!-- End Filter -->

            </div>

            <?php
            $rows_per_page = 10;
            if (isset($_GET['pageid'])) {
                $pagination = setPagination('items', $rows_per_page, 'cat_id', $_GET['pageid']);
            } else {
                $pagination = setPagination('items', $rows_per_page, NULL, NULL);
            }
            $pagination_strat = $pagination['start'];
            ?>

            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-cat numbring">
                    <li class="page-item">
                        <a class="page-link" href="<?php
                                                    $baseUrl = isset($_GET['pageid']) ? "?pageid=" . $_GET['pageid'] . "&pagename=" . $_GET['pagename'] : "?";
                                                    $baseUrl .= isset($_GET['sortOptions']) ? "&sortOptions=" . $_GET['sortOptions'] : ""; // Add sortOptions to the base URL
                                                    echo $baseUrl . "&page-nr=1";
                                                    ?>"><?php echo lang($langArray['first']) ?></a>
                    </li>

                    <?php
                    if (isset($_GET['page-nr']) && $_GET['page-nr'] > 1) {
                        $prev_page = $_GET['page-nr'] - 1;
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $baseUrl . "&page-nr=" . $prev_page; ?>"><?php echo lang($langArray['previous']) ?></a>
                        </li>
                    <?php
                    } else {
                    ?>
                        <li class="page-item disabled"><a class="page-link"><?php echo lang($langArray['previous']) ?></a></li>
                    <?php } ?>

                    <?php
                    for ($counter = 1; $counter <= $pagination['pages']; $counter++) {
                        $active = (isset($_GET['page-nr']) && $_GET['page-nr'] == $counter) ? 'active' : '';
                    ?>
                        <li class="page-item <?php echo $active; ?>">
                            <a class="page-link" href="<?php echo $baseUrl . "&page-nr=$counter"; ?>">
                                <?php echo $counter; ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php
                    if (isset($_GET['page-nr']) && $_GET['page-nr'] < $pagination['pages']) {
                        $next_page = $_GET['page-nr'] + 1;
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $baseUrl . "&page-nr=" . $next_page; ?>"><?php echo lang($langArray['next']) ?></a>
                        </li>
                    <?php } else { ?>
                        <li class="page-item disabled"><a class="page-link"><?php echo lang($langArray['next']) ?></a></li>
                    <?php } ?>

                    <li class="page-item">
                        <a class="page-link" href="<?php echo $baseUrl . "&page-nr=" . $pagination['pages']; ?>"><?php echo lang($langArray['last']) ?></a>
                    </li>
                </ul>
            </nav>

            <?php
            // Handle the add to cart submission
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['item_idd'])) {
                if (!isset($_SESSION['user_id'])) {
                    // If the user is not signed in, show the sign-in alert
                    echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['not_signed_in']) . '</div>';
                } else {
                    $item_id = intval($_GET['item_idd']);
                    $user_id = $_SESSION['user_id'];

                    $stmt = $con->prepare("SELECT quantity FROM items WHERE item_id = ?");
                    $stmt->execute(array($item_id));
                    $item_quantity = $stmt->fetch();

                    if ($item_quantity['quantity'] < 1) {
                        echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['no-quantity']) . '</div>';
                    } else {
                        // Check if the item already exists in the cart
                        $stmt = $con->prepare("SELECT * FROM cart WHERE item_id = ? AND user_id = ?");
                        $stmt->execute([$item_id, $user_id]);
                        $existingItem = $stmt->fetch();

                        // Fetch the item's price from the items table
                        $itemStmt = $con->prepare("SELECT price FROM items WHERE item_id = ?");
                        $itemStmt->execute([$item_id]);
                        $itemData = $itemStmt->fetch();

                        if (!$itemData) {
                            // Handle case where the item doesn't exist in the `items` table
                            echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['item_not_found']) . '</div>';
                            exit;
                        }

                        $itemPrice = $itemData['price'];

                        // Check if the item exists in the cart
                        if (!$existingItem) {
                            // If item doesn't exist in the cart, insert it
                            $stmt = $con->prepare("INSERT INTO cart (item_id, user_id, addCartDate, final_price_item, total_price_item) VALUES (?, ?, NOW(), ?, ?)");
                            $stmt->execute([$item_id, $user_id, $itemPrice, $itemPrice]);

                            // Check if itemid is present in the URL for redirection
                            if (isset($_GET['itemid'])) {
                                header("Location: items.php?itemid=$item_id&success=8");
                                exit;
                            } else {
                                // Display a success message
                                echo '<div class="alert alert-success success-message" role="alert">' . lang($langArray['item_added_cart']) . '</div>';
                            }
                        } else {
                            // Handle the case where the item is already in the cart
                            if (isset($_GET['itemid'])) {
                                header("Location: items.php?itemid=$item_id&success=9");
                                exit;
                            } else {
                                echo '<div class="alert alert-danger success-message" role="alert">' . lang($langArray['item_already_cart']) . '</div>';
                            }
                        }
                    }
                }
            }


            ?>

            <!-- Product List -->
            <div class="row product-list">
                <!-- Show Items from specific Category -->
                <?php if (isset($_GET['pageid'])): ?>
                    <!-- Show all items WHERE cat_id=pageid AND approve=1 -->
                    <?php

                    $results = getItems('cat_id', $_GET['pageid'], '1');
                    $items = $results['items'];
                    $itemsCount = $results['count'];

                    // Get filter values from URL parameters
                    $sort = isset($_GET['sortOptions']) ? $_GET['sortOptions'] : 'featured';

                    // Base query to fetch items, filter by cat_id and approve = 1
                    $query = "SELECT * FROM items WHERE cat_id = ? AND approve = 1";

                    // Add sorting based on the selected option
                    switch ($sort) {
                        case 'price_low_high':
                            $query .= " ORDER BY price ASC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'price_high_low':
                            $query .= " ORDER BY price DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'newest_arrivals':
                            $query .= " ORDER BY add_date DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'most_viewed':
                            $query .= " ORDER BY views DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'best_sellers':
                            $query = "SELECT items.*, ROUND(AVG(ratings.rating_value), 1) as avg_rating 
                                  FROM items 
                                  LEFT JOIN ratings ON items.item_id = ratings.item_id 
                                  WHERE items.cat_id = ? AND items.approve = 1 
                                  GROUP BY items.item_id 
                                  ORDER BY avg_rating DESC
                                  LIMIT $pagination_strat , $rows_per_page";
                            break;
                        default:
                            $query .= " ORDER BY add_date DESC LIMIT $pagination_strat , $rows_per_page"; // Default to newest arrivals if no sort is selected
                            break;
                    }

                    // Execute the query with the category ID as a parameter
                    $sort_items = $con->prepare($query);
                    $sort_items->execute(array($_GET['pageid']));
                    $items = $sort_items->fetchAll(PDO::FETCH_ASSOC);

                    ?>
                    <h1 class="text-center mb-4"><?php echo str_replace('-', ' ', $_GET['pagename']); ?></h1>
                    <?php if ($itemsCount > 0): ?>
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-4 mb-4 item-list-card">
                                <div class="card card-item-list">
                                    <div class="pro-img-box">
                                        <img src="<?php echo displayImage($item['item_id'], 0) ?>" class="card-img-top" alt="Product 2">
                                    </div>
                                    <div class="card-body">
                                        <a href="items.php?itemid=<?php echo $item['item_id'] ?>" class="card-title pro-title"><?php echo $item['item_name'] ?></a>
                                        <span <?php echo ($item['quantity'] == 0) ? 'class="text-danger">' . lang($langArray['Out of Stock']) : 'class="text-success">' . lang($langArray['In Stock']); ?></span>
                                            <div class="star-item-list">
                                                <?php
                                                $avgRating = getAvgRate($item['item_id']);
                                                $ratingCount = getRateCount($item['item_id']);

                                                // Calculate the whole and fractional parts of the average rating
                                                $wholeRating = floor($avgRating); // Get the whole number part
                                                $fractionalRating = $avgRating - $wholeRating; // Get the fractional part

                                                // Display full stars
                                                for ($count_star = 1; $count_star <= $wholeRating; $count_star++) {
                                                    echo '<i class="fa fa-star fa-2x star-rate st" data-rating="' . $count_star . '"></i>';
                                                }

                                                // Display half star if the fractional part is 0.5 or more
                                                if ($fractionalRating >= 0.5) {
                                                    echo '<i class="fa fa-star-half-o fa-2x star-rate st" data-rating="' . ($wholeRating + 0.5) . '"></i>';
                                                }

                                                ?>

                                                <div class="info-content">
                                                    <span class="overall-rating text-muted">(<?php echo round($avgRating, 1) ?>)</span>
                                                    <span class="views text-muted"><?php echo $item['views'] ?> <?php echo $langArray['views']; ?></span>
                                                </div>
                                            </div>
                                            <?php
                                            $item_desc = $item['description'];
                                            $maxLength = 40;    // Max length allowed (before trimming last 3 characters)

                                            // Check if the description length exceeds the limit
                                            if (strlen($item_desc) > $maxLength) {
                                                // Truncate the string, removing the last 3 characters and appending '...'
                                                $trimmedDescription = substr($item_desc, 0, $maxLength - 3) . '...';
                                            } else {
                                                // Use the full description if it's within the limit
                                                $trimmedDescription = $item_desc;
                                            }
                                            ?>
                                            <p class="desc text-muted"><?php echo $trimmedDescription; ?></p>
                                            <p class="price">$<?php echo $item['price'] ?></p>
                                            <form action="" method="get">
                                                <input type="hidden" name="item_idd" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" class="adtocart"><i class="fa fa-cart-plus"></i></button>
                                            </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-warning text-center w-100" role="alert"><?php echo lang($langArray['no-items']); ?></div>
                    <?php endif ?>

                <?php else: ?>
                    <!-- Show All Items -->

                    <!-- Show all items where approve = 1 -->

                    <?php
                    $results = getItems(NULL, NULL, '1');
                    $items = $results['items'];
                    // Get filter values from URL parameters
                    $sort = isset($_GET['sortOptions']) ? $_GET['sortOptions'] : 'featured';

                    // Base query to fetch items, filter by cat_id and approve = 1
                    $query = "SELECT * FROM items WHERE approve = 1";

                    // Add sorting based on the selected option
                    switch ($sort) {
                        case 'price_low_high':
                            $query .= " ORDER BY price ASC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'price_high_low':
                            $query .= " ORDER BY price DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'newest_arrivals':
                            $query .= " ORDER BY add_date DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'most_viewed':
                            $query .= " ORDER BY views DESC LIMIT $pagination_strat , $rows_per_page";
                            break;
                        case 'best_sellers':
                            $query = "SELECT 
                                        items.*, round(AVG(ratings.rating_value),1) as avg_rating 
                                    FROM 
                                        items 
                                    LEFT JOIN 
                                        ratings 
                                    ON 
                                        items.item_id = ratings.item_id 
                                    WHERE 
                                        items.approve = 1 
                                    GROUP BY 
                                        items.item_id 
                                    ORDER BY 
                                        avg_rating 
                                    DESC
                                    LIMIT 
                                        $pagination_strat , $rows_per_page";
                            break;
                        default:
                            $query .= " ORDER BY add_date DESC LIMIT $pagination_strat , $rows_per_page"; // Default to newest arrivals if no sort is selected
                            break;
                    }

                    // Execute the query
                    $sort_items = $con->prepare($query);
                    $sort_items->execute();
                    $items = $sort_items->fetchAll(PDO::FETCH_ASSOC);
                    ?>



                    <?php
                    foreach ($items as $item): ?>
                        <div class="col-md-4 mb-4 item-list-card">
                            <div class="card card-item-list">
                                <div class="pro-img-box">
                                    <img src="<?php echo displayImage($item['item_id'], 0) ?>" class="card-img-top" alt="Product 2">
                                </div>
                                <div class="card-body">
                                    <a href="items.php?itemid=<?php echo $item['item_id']; ?>" class="card-title pro-title"><?php echo $item['item_name']; ?></a>
                                    <span <?php echo ($item['quantity'] == 0) ? 'class="text-danger">' . lang($langArray['Out of Stock']) : 'class="text-success">' . lang($langArray['In Stock']); ?></span>
                                        <div class="star-item-list">
                                            <?php
                                            $avgRating = getAvgRate($item['item_id']);
                                            $ratingCount = getRateCount($item['item_id']);

                                            // Calculate the whole and fractional parts of the average rating
                                            $wholeRating = floor($avgRating);
                                            $fractionalRating = $avgRating - $wholeRating;

                                            // Display full stars
                                            for ($count_star = 1; $count_star <= $wholeRating; $count_star++) {
                                                echo '<i class="fa fa-star fa-2x star-rate st" data-rating="' . $count_star . '"></i>';
                                            }

                                            // Display half star if the fractional part is 0.5 or more
                                            if ($fractionalRating >= 0.5) {
                                                echo '<i class="fa fa-star-half-o fa-2x star-rate st" data-rating="' . ($wholeRating + 0.5) . '"></i>';
                                            }
                                            ?>

                                            <div class="info-content">
                                                <span class="overall-rating text-muted">(<?php echo round($avgRating, 1); ?>)</span>
                                                <span class="views text-muted"><?php echo $item['views']; ?> <?php echo $langArray['views']; ?></span>
                                            </div>
                                        </div>
                                        <?php
                                        $item_desc = $item['description'];
                                        $maxLength = 40;

                                        // Check if the description length exceeds the limit
                                        $trimmedDescription = strlen($item_desc) > $maxLength ? substr($item_desc, 0, $maxLength - 3) . '...' : $item_desc;
                                        ?>
                                        <p class="desc text-muted"><?php echo $trimmedDescription; ?></p>
                                        <p class="price">$<?php echo $item['price']; ?></p>

                                        <form action="" method="get">
                                            <input type="hidden" name="item_idd" value="<?php echo $item['item_id']; ?>">
                                            <button type="submit" class="adtocart"><i class="fa fa-cart-plus"></i></button>
                                        </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- End Product List -->
        </div>

    <?php
    }
    ?>
    <?php
    include $tmp . 'footer.php';
    ob_end_flush();
    ?>