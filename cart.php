<?php
ob_start();
session_start();
$pageTitle = 'cart';
include 'init.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
} elseif (isset($_SESSION["user_id"]) && checkUserStatus($_SESSION['email']) == 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
    header("Location: index.php");
    exit;
}

$form_errors = [];
$alert_message = '';

// Fetch individual items from the cart
$stmt = $con->prepare("SELECT cart.*, items.item_name, items.price, items.cat_id
                        FROM cart
                        INNER JOIN items ON cart.item_id = items.item_id
                        WHERE cart.user_id = ?
                        ORDER BY addCartDate DESC");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();
$cartItemsCont = $stmt->rowCount();

// Recalculate total prices for each cart item
$stmt = $con->prepare("UPDATE cart 
                        INNER JOIN items ON cart.item_id = items.item_id
                        SET cart.total_price_item = items.price * cart.quantity
                        WHERE cart.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Calculate total price for the cart
$totalPrice = getSubTotal($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];

    // Update cart item quantities
    if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $itemId => $quantity) {
            $itemId = intval($itemId);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

            // Validate quantity
            if ($quantity === false || $quantity < 1) {
                $form_errors[] = lang($langArray['quantity_positive_integer']);
            } else {
                // Fetch item data
                $stmt = $con->prepare("SELECT price, quantity AS available_quantity FROM items WHERE item_id = ?");
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();

                if ($item) {
                    // Validate quantity against available stock
                    if ($quantity > $item['available_quantity']) {
                        $form_errors[] = lang($langArray['invalid_quantity']) . $item['item_name'] . '. ' . lang($langArray['available'] . ': ' . $item['available_quantity']);
                    } else {
                        // Update cart with new quantity and price
                        $finalPriceItem = $item['price'] * $quantity;
                        $stmt = $con->prepare("UPDATE cart SET quantity = ?, final_price_item = ? WHERE item_id = ? AND user_id = ?");
                        $stmt->execute([$quantity, $finalPriceItem, $itemId, $userId]);
                    }
                } else {
                    $form_errors[] = lang($langArray['item_not_found']) . $itemId;
                }
            }
        }

        if (empty($form_errors)) {
            $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['cart_updated'])];
        } else {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => implode(' ', $form_errors)];
        }
        header("Location: cart.php");
        exit;
    }

    // Remove item from cart
    if (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
        $itemId = intval($_POST['item_id']);
        $stmt = $con->prepare("DELETE FROM cart WHERE item_id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);
        $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['item_removed'])];
        header("Location: cart.php");
        exit;
    }

    // Apply promo code
    if (isset($_POST['promo_code'])) {
        if (getCartCount($userId) > 0) {
            $promoCode = trim($_POST['promo_code']);
            $_SESSION['promo_code'] = $promoCode;

            // Check promo code validity
            $stmt = $con->prepare("SELECT * FROM promo_codes WHERE code = ? AND NOW() BETWEEN start_date AND end_date AND usage_limit > 0");
            $stmt->execute([$promoCode]);
            $promoInfo = $stmt->fetch();

            if (!$promoInfo) {
                $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['invalid_promo_code'])];
                header("Location: cart.php");
                exit;
            } else {
                // Apply promo code to eligible items
                $discountValue = $promoInfo['discount_value'];
                $itemId = $promoInfo['item_id'];
                $catId = $promoInfo['cat_id'];
                $event = $promoInfo['event'];

                foreach ($cartItems as $cartItem) {
                    $finalPrice = $cartItem['total_price_item'];

                    // Check if the promo applies
                    if (($itemId && $itemId == $cartItem['item_id']) ||
                        ($catId && $catId == $cartItem['cat_id']) ||
                        $event
                    ) {
                        $finalPrice = $cartItem['total_price_item'] - ($cartItem['total_price_item'] * $discountValue / 100);

                        // Update final price
                        $stmt = $con->prepare("UPDATE cart SET final_price_item = ? WHERE user_id = ? AND item_id = ?");
                        $stmt->execute([$finalPrice, $userId, $cartItem['item_id']]);
                    }
                }
                $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['promo_code_applied'])];
                header("Location: cart.php");
                exit;
            }
        } else {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['empty_cart'])];
            header("Location: cart.php");
            exit;
        }
    }

    if (isset($_POST['checkout'])) {
        if (getCartCount($userId) > 0) {
            header("Location: process_checkout.php");
            exit;
        } else {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['empty_cart'])];
            header("Location: cart.php");
            exit;
        }
    }
}
?>
<div class="wrapper">
    <div class="container">
        <div class="row">
            <div class="col-xl-9 col-md-8">
                <h2 class="h6 d-flex flex-wrap justify-content-between align-items-center px-4 py-3 bg-secondary">
                    <span><?php echo lang($langArray['products']); ?></span>
                    <a class="font-size-sm" href="items.php?page-nr=1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="width: 1rem; height: 1rem;">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg><?php echo lang($langArray['continue_shopping']); ?>
                    </a>
                </h2>
                <?php
                // Display any alert messages
                if (isset($_SESSION['alert_message'])) {
                    echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
                    echo $_SESSION['alert_message']['message'];
                    echo '</div>';
                    unset($_SESSION['alert_message']);
                }
                ?>

                <?php if ($cartItemsCont > 0): ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-sm-flex justify-content-between my-4 pb-4 border-bottom">
                            <div class="media d-block d-sm-flex text-center text-sm-left">
                                <a class="cart-item-thumb mx-auto mr-sm-4" href="#">
                                    <img src="<?php echo displayImage($item['item_id'], 0) ?>" alt="Product" width="140" height="140">
                                </a>
                                <div class="media-body pt-3">
                                    <h3 class="product-card-title font-weight-semibold border-0 pb-0">
                                        <a href="items.php?itemid=<?php echo $item['item_id'] ?>"><?php echo $item['item_name'] ?></a>
                                    </h3>
                                    <div class="font-size-sm">
                                        <span class="text-muted mr-2"><?php echo lang($langArray['size']); ?>:</span>8.5
                                    </div>
                                    <div class="font-size-sm">
                                        <span class="text-muted mr-2"><?php echo lang($langArray['color']); ?>:</span>Black
                                    </div>
                                    <div class="font-size-lg text-primary pt-2">
                                        <?php
                                        $originalTotalPrice = $item['price'] * $item['quantity'];

                                        if ($item['final_price_item'] < $originalTotalPrice): ?>
                                            <span class="text-danger text-decoration-line-through">$<?php echo number_format($originalTotalPrice, 2); ?></span>
                                            <span class="text-success">$<?php echo number_format($item['final_price_item'], 2); ?></span> <br>
                                            <span class="text-primary"> (<?php echo lang($langArray['you_save']); ?> $<?php echo number_format($originalTotalPrice - $item['final_price_item'], 2); ?>)</span>
                                        <?php else: ?>
                                            <span>$<?php echo number_format($originalTotalPrice, 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-2 pt-sm-0 pl-sm-3 mx-auto mx-sm-0 text-center text-sm-left" style="max-width: 10rem;">
                                <form action="" method="post">
                                    <div class="form-group mb-2">
                                        <label for="quantity-<?php echo $item['item_id']; ?>"><?php echo lang($langArray['quantity']); ?></label>
                                        <input class="form-control form-control-sm" type="number" name="quantity[<?php echo $item['item_id']; ?>]" id="quantity-<?php echo $item['item_id']; ?>" value="<?php echo $item['quantity'] ?>" min="1">
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm btn-block mb-2" type="submit" name="update_cart">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-cw mr-1">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <polyline points="1 20 1 14 7 14"></polyline>
                                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                        </svg><?php echo lang($langArray['update_cart']); ?>
                                    </button>

                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">

                                    <button class="btn btn-outline-danger btn-sm btn-block mb-2" type="submit" name="remove_item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 mr-1">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg><?php echo lang($langArray['remove']); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning text-center w-100" role="alert"><?php echo lang($langArray['no-items']); ?></div>
                <?php endif ?>


            </div>

            <!-- Sidebar -->
            <div class="col-xl-3 col-md-4 pt-3 pt-md-0">
                <h2 class="h6 px-4 py-3 bg-secondary text-center"><?php echo lang($langArray['subtotal']); ?></h2>

                <div class="h3 font-weight-semibold text-center py-3">
                    <?php
                    // Calculate the original total price without discounts
                    $totalFinalPrice = getDiscountSubTotal($_SESSION['user_id']);

                    // Compare the original total price with the final total price after discount
                    if ($totalFinalPrice < $totalPrice): ?>
                        <span class="text-danger text-decoration-line-through">$<?php echo number_format($totalPrice, 2); ?></span>
                        <span class="text-success">$<?php echo number_format($totalFinalPrice, 2); ?></span><br>
                        <span class="text-primary"> (<?php echo lang($langArray['you_save']); ?> $<?php echo number_format($totalPrice - $totalFinalPrice, 2); ?>)</span>
                    <?php else: ?>
                        <span>$<?php echo number_format($totalPrice, 2); ?></span>
                    <?php endif; ?>
                </div>
                <hr>
                <form action="cart.php" method="post">
                    <button class="btn btn-primary btn-block" type="submit" name="checkout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="feather feather-credit-card mr-2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg> <?php echo lang($langArray['proceed_to_checkout']); ?>
                    </button>
                </form>

                <!-- Accordion -->
                <div class="pt-4">
                    <div class="accordion" id="cart-accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#promocode" aria-expanded="true" aria-controls="promocode">
                                    <?php echo lang($langArray['apply_promo_code']); ?>
                                </button>
                            </h2>
                            <div id="promocode" class="accordion-collapse collapse show" data-bs-parent="#cart-accordion">
                                <div class="accordion-body">
                                    <form method="post" class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <input class="form-control" type="text" id="cart-promocode" name="promo_code"
                                                placeholder="<?php echo lang($langArray['promo_code_placeholder']); ?>" required>
                                            <div class="invalid-feedback"><?php echo lang($langArray['provide_valid_promo_code']); ?></div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-block" type="submit"><?php echo lang($langArray['apply_promo_code']); ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#shipping" aria-expanded="false" aria-controls="shipping">
                                    <?php echo lang($langArray['shipping_estimates']); ?>
                                </button>
                            </h2>
                            <div id="shipping" class="accordion-collapse collapse" data-bs-parent="#cart-accordion">
                                <div class="accordion-body">
                                    <form class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <select class="form-select" required>
                                                <option value=""><?php echo lang($langArray['choose_your_country']); ?></option>
                                                <option value="Australia">Australia</option>
                                                <option value="Belgium">Belgium</option>
                                                <option value="Canada">Canada</option>
                                                <option value="Finland">Finland</option>
                                                <option value="Mexico">Mexico</option>
                                                <option value="New Zealand">New Zealand</option>
                                                <option value="Switzerland">Switzerland</option>
                                                <option value="United States">United States</option>
                                            </select>
                                            <div class="invalid-feedback"><?php echo lang($langArray['choose_country']); ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <select class="form-select" required>
                                                <option value=""><?php echo lang($langArray['choose_your_city']); ?></option>
                                                <option value="Bern">Bern</option>
                                                <option value="Brussels">Brussels</option>
                                                <option value="Canberra">Canberra</option>
                                                <option value="Helsinki">Helsinki</option>
                                                <option value="Mexico City">Mexico City</option>
                                                <option value="Ottawa">Ottawa</option>
                                                <option value="Washington D.C.">Washington D.C.</option>
                                                <option value="Wellington">Wellington</option>
                                            </select>
                                            <div class="invalid-feedback"><?php echo lang($langArray['choose_city']); ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <input class="form-control" type="text" placeholder="<?php echo lang($langArray['zip_postal_code']); ?>"
                                                required>
                                            <div class="invalid-feedback"><?php echo lang($langArray['provide_valid_zip']); ?></div>
                                        </div>
                                        <button class="btn btn-outline-primary btn-block" type="submit"><?php echo lang($langArray['calculate_shipping']); ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include $tmp . 'footer.php';
    ob_end_flush();
    ?>