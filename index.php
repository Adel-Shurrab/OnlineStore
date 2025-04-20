<?php
ob_start();
session_start();
$pageTitle = 'Home';
include 'init.php';

// Check if the user is signed in
$isSignedIn = isset($_SESSION['user_id']);

// Handle cart addition request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['item_idd'])) {
    if (!$isSignedIn) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'You must be signed in to add items to the cart.'
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        if (checkUserStatus($_SESSION['email']) == 1) {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $item_id = $_GET['item_idd'];
            $user_id = $_SESSION['user_id'];

            // Check if the item already exists in the cart
            $stmt = $con->prepare("SELECT * FROM cart WHERE item_id = ? AND user_id = ?");
            $stmt->execute([$item_id, $user_id]);
            $existingItem = $stmt->fetch();

            if (!$existingItem) {
                $stmt = $con->prepare("SELECT * FROM items WHERE item_id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch();

                if ($item['quantity'] < 1) {
                    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['no-quantity'])];
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $final_price_item = $item['price'];
                    $total_price_item = $item['price'];
                    $stmt = $con->prepare("INSERT INTO cart (item_id, user_id, addCartDate, final_price_item, total_price_item) VALUES (?, ?, NOW(), ?, ?)");
                    $stmt->execute([$item_id, $user_id, $final_price_item, $total_price_item]);

                    $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['item_added_cart'])];
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['item_already_cart'])];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
}

// Get promo code
$result = getPromoCode(10, NULL, NULL);
$getPromoCode = $result['code'];
?>

<!-- First Section: Landing Page with Slider -->
<div id="landingPageSlider" class="carousel slide" data-bs-ride="carousel">
    <?php
    // Display any alert messages
    if (isset($_SESSION['alert_message'])) {
        echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
        echo $_SESSION['alert_message']['message'];
        echo '</div>';
        unset($_SESSION['alert_message']);
    }
    ?>
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#landingPageSlider" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#landingPageSlider" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#landingPageSlider" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <!-- Info about the website -->
        <div class="carousel-item active welcome-sec">
            <img src="layout/images/landpage1.jpg" class="d-block w-100 welcome-brand" alt="Info about the store" loading="lazy">
            <div class="carousel-caption d-none d-md-block">
                <h1><?php echo $langArray['Welcome to ClickCart']; ?></h1>
                <p><?php echo $langArray['Your one-stop shop for the best items online. Enjoy our wide selection and fast shipping!']; ?></p>
                <a href="items.php"><?php echo $langArray['Shop now']; ?></a>
            </div>
        </div>
        <!-- Trending items -->
        <div class="carousel-item welcome-sec">
            <img src="layout/images/landpage-2.jpeg" class="d-block w-100 welcome-brand" alt="Trending Items" loading="lazy">
            <div class="carousel-caption d-none d-md-block">
                <h1><?php echo $langArray['Trending Items']; ?></h1>
                <p><?php echo $langArray['Check out our trending items: PS5, iPhone 16, S24 Ultra, and more!']; ?></p>
            </div>
        </div>

        <?php if ($result['count'] > 0): ?>
            <!-- Promo code for events -->
            <div class="carousel-item gamesSale-banner">
                <img src="layout/images/gamessales.jpg" class="d-block w-100 welcome-brand" alt="Promo Codes" loading="lazy">
                <div class="carousel-caption d-none d-md-block">
                    <h2><?php echo $langArray['Play more<br>today and get']; ?></h2>
                    <div class="discount-container">
                        <span class="discount-percent"><?php echo intval($getPromoCode['discount_value']); ?></span>
                        <div class="discount-off-content">
                            <span class="discount-percent">%</span>
                            <span class="discount-off"><?php echo $langArray['OFF']; ?></span>
                        </div>
                    </div>
                    <p><?php echo sprintf($langArray['Use code %s to get %s off on Video Games category now'], '<strong>' . $getPromoCode['code'] . '</strong>', '<strong>' . intval($getPromoCode['discount_value']) . '%</strong>'); ?></p>
                    <a href="items.php?pageid=10&pagename=Video-Games&page-nr=1" class="join-btn"><?php echo $langArray['Shop now']; ?></a>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#landingPageSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo $langArray['Previous']; ?></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#landingPageSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo $langArray['Next']; ?></span>
    </button>
</div>

<?php
$stmt = $con->prepare("SELECT items.*, ROUND(AVG(ratings.rating_value), 1) as avg_rating 
                        FROM items 
                        LEFT JOIN ratings ON items.item_id = ratings.item_id 
                        WHERE items.approve = 1 
                        GROUP BY items.item_id 
                        ORDER BY avg_rating DESC
                        LIMIT 5
                    ");
$stmt->execute();
$bestSellers = $stmt->fetchAll();
?>
<!-- Second Section: Best Seller Items -->
<div class="container mt-5 home">
    <h2 class="text-center mb-4"><?php echo $langArray['Best Sellers']; ?></h2>
    <div class="row home-best">
        <?php foreach ($bestSellers as $item): ?>
            <div class="col-md-4 mb-4 item-list-card">
                <div class="card card-item-list">
                    <div class="pro-img-box">
                        <img src="<?php echo displayImage($item['item_id'], 0) ?>" class="card-img-top" alt="<?php echo $item['item_name']; ?>" loading="lazy">
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
                            <p class="price">$<?php echo $item['price']; ?></p>

                            <form action="" method="get">
                                <input type="hidden" name="item_idd" value="<?php echo $item['item_id']; ?>">
                                <button type="submit" class="adtocart"><i class="fa fa-cart-plus"></i></button>
                            </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$stmt = $con->prepare("SELECT * FROM items WHERE approve = 1 ORDER BY views DESC LIMIT 5");
$stmt->execute();
$mostViewed = $stmt->fetchAll();
?>
<!-- Third Section: Most Viewed Items -->
<div class="container mt-5 home container-content">
    <h2 class="text-center mb-4"><?php echo $langArray['Most Viewed Items']; ?></h2>
    <div class="row home-best">
        <?php foreach ($mostViewed as $item): ?>
            <div class="col-md-4 mb-4 item-list-card">
                <div class="card card-item-list">
                    <div class="pro-img-box">
                        <img src="<?php echo displayImage($item['item_id'], 0) ?>" class="card-img-top" alt="<?php echo $item['item_name']; ?>" loading="lazy">
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
                            <p class="price">$<?php echo $item['price']; ?></p>

                            <form action="" method="get">
                                <input type="hidden" name="item_idd" value="<?php echo $item['item_id']; ?>">
                                <button type="submit" class="adtocart"><i class="fa fa-cart-plus"></i></button>
                            </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>