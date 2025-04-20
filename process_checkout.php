<?php
ob_start();
session_start();
$pageTitle = 'Check-Out';
include 'init.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
} elseif (isset($_SESSION["user_id"]) && checkUserStatus($_SESSION['email']) == 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
    header("Location: index.php");
    exit;
}

// Fetch the promo code from the session
$promoCode = isset($_SESSION['promo_code']) ? $_SESSION['promo_code'] : null;

// Get the discount and subtotal
$subtotal = getSubTotal($_SESSION['user_id']);
$discountedSubtotal = getDiscountSubTotal($_SESSION['user_id']);
$discountAmount = $subtotal - $discountedSubtotal;

$alert_message = '';

$form_errors = array(
    'card_number_err' => '',
    'card_name_err' => '',
    'expiry_date_err' => '',
    'cvv_err' => '',
    'paypal_email_err' => ''
);

$results = getCart($_SESSION['user_id']);
if ($results['count'] < 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['empty_cart'])];
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['paypal_email']) && isset($_POST['card_number'])) {
        $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['just_one'])];
        header("Location: process_checkout.php");
        exit;
    }

    if (isset($_POST['paypal_email'])) {
        $paypal_email = $_POST['paypal_email'];

        if (empty($paypal_email)) {
            $form_errors['paypal_email_err'] = lang($langArray["cannot_be_empty."]);
        }

        // Check if PayPal email is valid (basic validation)
        if (!filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
            $form_errors['paypal_email_err'] = lang($langArray["Please enter a valid PayPal email address."]);
        }
    } else {
        $card_number = $_POST['card_number'];
        $card_name = $_POST['card_name'];
        $expiry_date = $_POST['expiry_date'];
        $cvv = $_POST['cvv'];

        // Check if the credit card number is a valid 16-digit number
        if (empty($card_number)) {
            $form_errors['card_number_err'] = lang($langArray["cannot_be_empty"]);
        } elseif (!preg_match('/^\d{16}$/', $card_number)) {
            $form_errors['card_number_err'] = lang($langArray["Please enter a valid 16-digit card number"]);
        } elseif (!checkLuhnCreditCard($card_number)) {
            $form_errors['card_number_err'] = lang($langArray["Invalid credit card number"]);
        }

        // Check if the card name contains only letters and spaces
        if (empty($card_name)) {
            $form_errors['card_name_err'] = lang($langArray["cannot_be_empty"]);
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $card_name)) {
            $form_errors['card_name_err'] = lang($langArray["Please enter a valid cardholder name"]);
        }

        // Check if the expiry date is in the format MM/YY
        if (empty($expiry_date)) {
            $form_errors['expiry_date_err'] = lang($langArray["cannot_be_empty"]);
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry_date)) {
            $form_errors['expiry_date_err'] = lang($langArray["Please enter a valid expiry date (MM/YY)"]);
        } else {
            $current_date = new DateTime();
            $expiry_month_year = DateTime::createFromFormat('m/y', $expiry_date);

            if ($expiry_month_year < $current_date) {
                $form_errors['expiry_date_err'] = lang($langArray["The expiry date has already passed."]);
            }
        }

        // Check if CVV is valid (typically 3 digits for Visa/MasterCard)
        if (empty($cvv)) {
            $form_errors['cvv_err'] = lang($langArray["cannot_be_empty"]);
        } elseif (!preg_match('/^\d{3}$/', $cvv)) {
            $form_errors['cvv_err'] = lang($langArray["Please enter a valid CVV (3 digits)."]);
        }
    }

    // If no errors at All
    if (empty(array_filter($form_errors))) {
        try {
            $con->beginTransaction();
            $stmt_1 = $con->prepare("INSERT INTO orders (user_id, order_date, total_price, shipping_address, payment_status) VALUES (?, NOW(), ?, ?, ?)");
            $stmt_1->execute(array($_SESSION['user_id'], $discountedSubtotal, lang($langArray["another time"]), 'Pending'));

            $order_id = $con->lastInsertId();
            $cart = $con->prepare("SELECT * FROM cart WHERE user_id = ?");
            $cart->execute(array($_SESSION['user_id']));
            $cart_items = $cart->fetchAll();

            foreach ($cart_items as $cart_item) {
                $stmt_2 = $con->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt_2->execute(array($order_id, $cart_item['item_id'], $cart_item['quantity'], $cart_item['final_price_item']));

                $results = getItems('item_id', $cart_item['item_id'], '1');
                $items = $results['items'];
                foreach ($items as $item) {
                    $new_quantity = $item['quantity'] - $cart_item['quantity'];
                    $stmt_3 = $con->prepare("UPDATE items SET quantity = ? WHERE item_id = ?");
                    $stmt_3->execute(array($new_quantity, $cart_item['item_id']));
                }
            }

            $stmt = $con->prepare("UPDATE promo_codes SET usage_limit = usage_limit - 1 WHERE code = ?");
            $stmt->execute([$promoCode]);

            $clearCart = $con->prepare("DELETE FROM cart WHERE user_id = ?");
            $clearCart->execute(array($_SESSION['user_id']));

            $con->commit();

            $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray["Order placed successfully!"])];
            header("Location: cart.php");
            exit;
        } catch (Exception $e) {
            $con->rollBack();
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['Failed to place order!'])];
            header("Location: process_checkout.php");
            exit;
        }
    } else {
        $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['Failed to place order!'])];
        header("Location: process_checkout.php");
        exit;
    }
}
?>
<div class="container">
    <h1 class="h3 mb-5"><?php echo lang($langArray["Payment"]); ?></h1>
    <?php
    // Display any alert messages
    if (isset($_SESSION['alert_message'])) {
        echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
        echo $_SESSION['alert_message']['message'];
        echo '</div>';
        unset($_SESSION['alert_message']);
    }
    ?>
    <form method="POST" action="">
        <div class="row">
            <!-- Left -->
            <div class="col-lg-9">
                <div class="accordion" id="accordionPayment">
                    <!-- Credit card -->
                    <div class="accordion-item mb-3">
                        <h2 class="h5 px-4 py-3 accordion-header d-flex justify-content-between align-items-center">
                            <div class="w-100" onclick="document.getElementById('payment1').click();" data-bs-toggle="collapse" data-bs-target="#collapseCC" aria-expanded="true">
                                <input class="form-check-input me-2" type="radio" name="payment" checked id="payment1" onchange="togglePaymentMethod('credit')" required>
                                <label class="form-check-label pt-1" for="payment1">
                                    <?php echo lang($langArray["Credit Card"]); ?>
                                </label>
                            </div>
                        </h2>
                        <div id="collapseCC" class="accordion-collapse collapse show" data-bs-parent="#accordionPayment">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo lang($langArray["Card Number"]); ?></label>
                                    <input type="text" class="form-control" name="card_number" id="card_number" required>
                                    <p class="text-danger"><?php echo $form_errors['card_number_err'] ?></p>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo lang($langArray["Name on card"]); ?></label>
                                            <input type="text" class="form-control" name="card_name" id="card_name" pattern="[a-zA-Z\s]+" title="Cardholder's name should only contain letters" required>
                                            <p class="text-danger"><?php echo $form_errors['card_name_err'] ?></p>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo lang($langArray["Expiry date"]); ?></label>
                                            <input type="text" class="form-control" name="expiry_date" id="expiry_date" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" title="Please enter a valid expiry date (MM/YY)" placeholder="MM/YY" required>
                                            <p class="text-danger"><?php echo $form_errors['expiry_date_err'] ?></p>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo lang($langArray["CVV Code"]); ?></label>
                                            <input type="text" class="form-control" name="cvv" id="cvv" pattern="\d{3}" title="CVV code should be a 3-digit number" required>
                                            <p class="text-danger"><?php echo $form_errors['cvv_err'] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- PayPal -->
                    <div class="accordion-item mb-3 border">
                        <h2 class="h5 px-4 py-3 accordion-header d-flex justify-content-between align-items-center">
                            <div class="w-100" onclick="document.getElementById('payment2').click();" data-bs-toggle="collapse" data-bs-target="#collapsePP" aria-expanded="false">
                                <input class="form-check-input me-2" type="radio" name="payment" id="payment2" onchange="togglePaymentMethod('paypal')">
                                <label class="form-check-label pt-1" for="payment2">
                                    <?php echo lang($langArray["PayPal"]); ?>
                                </label>
                            </div>
                        </h2>
                        <div id="collapsePP" class="accordion-collapse collapse" data-bs-parent="#accordionPayment">
                            <div class="accordion-body">
                                <div class="px-2 col-lg-6 mb-3">
                                    <label class="form-label"><?php echo lang($langArray["Email address"]); ?></label>
                                    <input type="email" class="form-control" id="paypal_email" name="paypal_email" disabled required>
                                    <p class="text-danger"><?php echo $form_errors['paypal_email_err'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Right -->
            <div class="col-lg-3">
                <div class="card position-sticky top-0">
                    <div class="p-3 bg-light bg-opacity-10">
                        <h6 class="card-title mb-3"><?php echo lang($langArray["Order Summary"]); ?></h6>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span><?php echo lang($langArray["Subtotal"]); ?></span> <span>$<?php echo $subtotal ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span><?php echo lang($langArray["Shipping"]); ?></span> <span>$20.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span><?php echo lang($langArray["Coupon"]); ?> (<?php echo lang($langArray["Code"]); ?>: <?php echo $promoCode ?>)</span> <span class="coupon">-$<?php echo $discountAmount ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4 small">
                            <span><?php echo lang($langArray["TOTAL"]); ?></span> <strong class="text-dark">$<?php echo $discountedSubtotal ?></strong>
                        </div>
                        <div class="form-check mb-1 small">
                            <input class="form-check-input" type="checkbox" value="" id="tnc" required>
                            <label class="form-check-label" for="tnc">
                                <?php echo lang($langArray["I agree to the"]); ?> <a href="#"><?php echo lang($langArray["terms and conditions"]); ?></a>
                            </label>
                        </div>
                        <div class="form-check mb-3 small">
                            <input class="form-check-input" type="checkbox" value="" id="subscribe" required>
                            <label class="form-check-label" for="subscribe">
                                <?php echo lang($langArray["Get emails about product updates and events."]); ?> <?php echo lang($langArray["If you change your mind, you can unsubscribe at any time."]); ?> <a href="#"><?php echo lang($langArray["See our policy"]); ?></a>.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo lang($langArray["Continue to Checkout"]); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
include $tmp . 'footer.php';
ob_end_flush();
?>