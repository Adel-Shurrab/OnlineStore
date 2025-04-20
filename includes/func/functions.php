<?php

/**
 * Retrieves all categories from the database in descending order by ID.
 *
 * @return array The list of categories.
 */
function getCat()
{
    global $con;

    $getCats = $con->prepare("SELECT * FROM categories ORDER BY cat_id DESC");
    $getCats->execute();
    $cats = $getCats->fetchAll();
    return $cats;
}

/**
 * Retrieves the count of items in the user's cart.
 *
 * @param int $user_id The ID of the user.
 * @return int The number of items in the cart.
 */
function getCartCount($user_id)
{
    global $con;

    $getCart = $con->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $getCart->execute([$user_id]);
    $cart = $getCart->fetchColumn();
    return $cart;
}

/**
 * Retrieves items from the database with optional filtering by category or approval status.
 *
 * @param string|null $where Column to filter by (e.g., 'cat_id').
 * @param mixed|null $value Value of the filter column.
 * @param bool|null $approve If true, retrieves only approved items.
 * @return array Array containing 'items' (fetched items) and 'count' (number of items).
 */
function getItems($where = NULL, $value = NULL, $approve = NULL)
{
    global $con;

    // Start with the base query
    $query = 'SELECT * FROM items';

    // Array to store parameters for the query
    $params = array();

    // Check if filtering by a specific column (e.g., category ID)
    if ($where && $value) {
        $query .= ' WHERE ' . $where . ' = ?';
        $params[] = $value;
    }

    // Check if only approved items should be shown
    if ($approve) {
        if (strpos($query, 'WHERE') !== false) {
            $query .= ' AND approve = 1';
        } else {
            $query .= ' WHERE approve = 1';
        }
    }


    // Always order items by ID in descending order
    $query .= ' ORDER BY item_id DESC';

    // Prepare and execute the query
    $getItems = $con->prepare($query);
    $getItems->execute($params);

    // Fetch all items and get row count
    $items = $getItems->fetchAll(PDO::FETCH_ASSOC); // Fetch as an associative array
    $count = $getItems->rowCount(); // Get the number of rows returned

    return [
        'items' => $items,
        'count' => $count
    ];
}

/**
 * Retrieves comments based on a specified condition.
 *
 * @param string $where The column to filter by (e.g., 'item_id').
 * @param mixed $value The value to filter by.
 * @return array The list of comments matching the condition.
 */
function getComments($where, $value)
{
    global $con;

    $getItems = $con->prepare("SELECT * FROM comments WHERE $where=? ORDER BY c_id DESC");
    $getItems->execute(array($value));
    $items = $getItems->fetchAll();
    return $items;
}

function getCart($user_id) {
    global $con;
    $stmt = $con->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    $count = $stmt->rowCount(); 

    return [
        'cart' => $cart,
        'count' => $count
    ];
}

/**
 * Checks if a user is registered but not yet activated.
 *
 * @param string $email The email of the user.
 * @return int Returns 1 if the user exists and is not activated, otherwise 0.
 */
function checkUserStatus($email)
{
    global $con;

    $stmt = $con->prepare("SELECT email, reg_stat FROM users WHERE email = ? AND reg_stat= 0");
    $stmt->execute(array($email));
    $status = $stmt->rowCount();
    return $status;
}

/**
 * Displays the page title if set, or 'Default' if not.
 */
function getTitle()
{
    global $pageTitle;
    if (isset($pageTitle)) {
        echo $pageTitle;
    } else {
        echo 'Default';
    }
}

/**
 * Calculates the time difference from a given timestamp and returns a human-readable format.
 *
 * @param int $time The Unix timestamp to calculate from.
 * @return string The time difference as a human-readable string.
 */
function get_time_ago($time)
{
    // Ensure the input is a numeric value (Unix timestamp)
    if (!is_numeric($time)) {
        return 'Invalid time';
    }

    // Calculate the difference between the current time and the given timestamp
    $time_difference = time() - (int)$time; // Cast to int to avoid warnings

    // If the difference is less than 1 second, return a specific message
    if ($time_difference < 1) {
        return 'less than 1 second ago';
    }

    // Array mapping time intervals in seconds to their corresponding unit names
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'year',      // Seconds in a year
        30 * 24 * 60 * 60       => 'month',     // Seconds in a month
        24 * 60 * 60            => 'day',       // Seconds in a day
        60 * 60                 => 'hour',      // Seconds in an hour
        60                      => 'minute',     // Seconds in a minute
        1                       => 'second'      // Seconds in a second
    );

    // Iterate through each condition to find the largest applicable time unit
    foreach ($condition as $secs => $str) {
        // Calculate how many units of the current interval fit into the time difference
        $d = $time_difference / $secs;

        // If at least one unit fits, format the output
        if ($d >= 1) {
            // Round the value to the nearest whole number
            $t = round($d);
            // Return the formatted string, appending 's' for plural units if necessary
            return 'about ' . $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}

/**
 * Calculates the average rating for a specific item.
 *
 * @param int $item_id The ID of the item.
 * @return float The average rating, or 0 if no ratings exist.
 */
function getAvgRate($item_id)
{
    global $con;

    // Fetch average rating for the item
    $stmt = $con->prepare("SELECT AVG(rating_value) AS average_rating FROM ratings WHERE item_id = ?");
    $stmt->execute(array($item_id));
    $avgRating = $stmt->fetch(PDO::FETCH_ASSOC)['average_rating'];

    // If no ratings exist, set to 0
    if ($avgRating === null) {
        $avgRating = 0;
    }
    return $avgRating;
}

/**
 * Retrieves the count of ratings for a specific item.
 *
 * @param int $item_id The ID of the item.
 * @return int The number of ratings for the item.
 */
function getRateCount($item_id)
{
    global $con;

    // Fetch the number of ratings
    $stmt = $con->prepare("SELECT * FROM ratings WHERE item_id = ?");
    $stmt->execute(array($item_id));
    $ratingCount = $stmt->rowCount();

    // If no ratings exist, set to 0
    if ($ratingCount === null) {
        $ratingCount = 0;
    }

    return $ratingCount;
}

/**
 * Increments the view count for a specific item by 1.
 *
 * @param int $itemId The ID of the item.
 * @return bool True on success, false on failure.
 */
function incrementViewCount($itemId)
{
    global $conn; // Your database connection variable
    $stmt = $conn->prepare("UPDATE items SET views = views + 1 WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    return $stmt->execute();
}



/**
 * Checks if a specific item exists in a specified table.
 *
 * @param string $select The column to select.
 * @param string $from The table to check.
 * @param mixed $value The value to search for.
 * @return int The count of matching items.
 */
function checkItem($select, $from, $value)
{
    global $con;

    $stmt = $con->prepare("SELECT $select FROM $from WHERE $select = ?");
    $stmt->execute(array($value));
    return $stmt->rowCount(); // Return the count instead of echoing it
}

/**
 * Counts the total number of items in a specified column from a table.
 *
 * @param string $item The column to count.
 * @param string $from The table to count from.
 */
function contItems($item, $from)
{
    global $con;
    $stmt2 = $con->prepare("SELECT COUNT($item) FROM $from");
    $stmt2->execute();
    echo $stmt2->fetchColumn();
}

/**
 * Retrieves the latest records from a specified table.
 *
 * @param string $select The columns to select.
 * @param string $table The table to select from.
 * @param string $order The column to order by.
 * @param int $limit The number of records to retrieve. Default is 5.
 * @return array The latest records.
 */
function getLatest($select, $table, $order, $limit = 5)
{
    global $con;

    $stmt = $con->prepare("SELECT $select FROM $table ORDER BY $order DESC LIMIT $limit");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return $rows;
}

/**
 * Sets up pagination for a table.
 *
 * @param string $table The table to paginate.
 * @param int $rows_per_page The number of rows per page.
 * @param string|null $where Optional column to filter by.
 * @param mixed|null $value The value for the filter.
 * @return array Information for pagination including 'nr_of_rows', 'pages', 'page', and 'start'.
 */
function setPagination($table, $rows_per_page, $where = NULL, $value = NULL)
{
    global $con;

    // Initialize variables
    $params = [];
    $start = 0;
    $page = 0;
    $query = "SELECT * FROM $table";

    // Modify the query if $where and $value are provided
    if ($where && $value) {
        $query .= " WHERE $where = ?";
        $params[] = $value;  // Add the value to the parameter list for binding
    }

    // Prepare and execute the query
    $records = $con->prepare($query);
    $records->execute($params);  // Execute with or without params based on condition

    // Get the total number of rows
    $nr_of_rows = $records->rowCount();

    // Calculate the number of pages
    $pages = ceil($nr_of_rows / $rows_per_page);

    // If the user clicks on the pagination buttons, set a new starting point
    if (isset($_GET['page-nr']) && is_numeric($_GET['page-nr'])) {
        $page = (int)$_GET['page-nr'] - 1;  // Ensure page is an integer
        $start = $page * $rows_per_page;
    }

    return [
        'nr_of_rows' => $nr_of_rows,
        'pages' => $pages,
        'page' => $page,
        'start' => $start
    ];
}

/**
 * Calculates the subtotal of items in the user's cart.
 *
 * @param int $user_id The ID of the user.
 * @return float The subtotal of the items.
 */
function getSubTotal($user_id)
{
    global $con;

    $stmt = $con->prepare("SELECT SUM(total_price_item) AS total_price 
                                   FROM cart
                                   WHERE user_id = ?");
    $stmt->execute(array($user_id));
    return $stmt->fetchColumn();
}

/**
 * Calculates the discounted subtotal for items in the user's cart.
 *
 * @param int $user_id The ID of the user.
 * @return float The subtotal after discount.
 */
function getDiscountSubTotal($user_id)
{
    global $con;

    $totalFinalStmt = $con->prepare("SELECT SUM(final_price_item) AS total_final_price 
                                    FROM cart                                            
                                    WHERE cart.user_id = ?");
    $totalFinalStmt->execute(array($user_id));
    return $totalFinalStmt->fetchColumn();
}

/**
 * Validates a credit card number using the Luhn algorithm.
 *
 * @param string $creditCardNum The credit card number to validate.
 * @return bool True if the card number is valid, false otherwise.
 */
function checkLuhnCreditCard($creditCardNum)
{
    // Initialize sums for odd and even indexed digits
    $sumOdd = 0;
    $sumEven = 0;

    // Get the length of the credit card number
    $length = strlen($creditCardNum);

    // Loop through each digit in the credit card number
    for ($i = 0; $i < $length; $i++) {
        // Get the digit as an integer
        $digit = (int)$creditCardNum[$length - 1 - $i]; // Process digits from right to left

        // Check if the current index is odd or even based on 0-based index
        if ($i % 2 == 0) {
            // For even index (0, 2, 4, ...), sum directly
            $sumOdd += $digit;
        } else {
            // For odd index, double the digit
            $doubledDigit = $digit * 2;

            // If doubling results in a number greater than 9, subtract 9
            if ($doubledDigit > 9) {
                $doubledDigit -= 9;
            }
            $sumEven += $doubledDigit;
        }
    }

    // Total sum for Luhn check
    $sumCheck = $sumOdd + $sumEven;

    // If the total modulo 10 is 0, the number is valid according to Luhn's algorithm
    return $sumCheck % 10 === 0;
}

/**
 * Generates a random code with a specified length.
 *
 * @param int $length The length of the code.
 * @return string The generated code.
 */
function generateCode($length)
{
    $chars = "vwxyzABCD02789";
    $code = "";
    $clen = strlen($chars) - 1;
    while (strlen($code) < $length) {
        $code .= $chars[mt_rand(0, $clen)];
    }
    return $code;
}

/**
 * Retrieves a promo code based on category, item, or event.
 *
 * @param int|null $cat_id The category ID, if filtering by category.
 * @param int|null $item_id The item ID, if filtering by item.
 * @param string|null $event The event name, if filtering by event.
 * @return array The promo code details and count of promo codes.
 */
function getPromoCode($cat_id = NULL, $item_id = NULL, $event = NULL)
{
    global $con;

    $query = "SELECT * FROM promo_codes WHERE ";
    $params = [];

    if ($cat_id) {
        $query .= "cat_id = ?";
        $params[] = $cat_id;
    } elseif ($item_id) {
        $query .= "item_id = ?";
        $params[] = $item_id;
    } elseif ($event) {
        $query .= "event = ?";
        $params[] = $event;
    }

    $getPromoCode = $con->prepare($query);
    $getPromoCode->execute($params);
    $code = $getPromoCode->fetch(PDO::FETCH_ASSOC);
    $count = $getPromoCode->rowCount();

    return [
        'code' => $code,
        'count' => $count
    ];
}

/**
 * Generates a square avatar image with a specified character in the center,
 * using a random background color and white text.
 *
 * @param string $character The character to display on the avatar.
 * @return string The file path of the generated avatar image.
 */

function makeAvatar($character)
{
    // Define the path for saving the avatar
    $path = "layout/images/avatar/" . time() . ".png";

    // Create a blank image
    $image = imagecreate(200, 200);

    // Generate random background color
    $red = rand(0, 255);
    $green = rand(0, 255);
    $blue = rand(0, 255);
    imagecolorallocate($image, $red, $green, $blue);

    // Define text color (white)
    $textColor = imagecolorallocate($image, 255, 255, 255);

    // Font path
    $fontPath = "layout/fonts/arial.ttf";
    if (!file_exists($fontPath)) {
        die("Font file not found");
    }

    // Set font size and calculate text position for better centering
    $fontSize = 100;
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $character);
    $x = (200 - ($bbox[2] - $bbox[0])) / 2; // Center horizontally
    $y = (200 + ($bbox[1] - $bbox[7])) / 2; // Adjust for better vertical centering

    // Add character to image
    imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $character);

    // Save the image as PNG
    imagepng($image, $path);

    // Free memory
    imagedestroy($image);

    // Return the path to the generated avatar
    return $path;
}


/**
 * Retrieves and displays the user avatar based on the user ID.
 *
 * This function fetches the user's avatar image from the database using 
 * the provided user ID. If a valid avatar is found, it displays the image; 
 * otherwise, it shows a default avatar.
 *
 * @param int $user_id The unique identifier of the user whose avatar is to be retrieved.
 * @return void
 */
function getUserAvatar($user_id)
{
    global $con;
    $stmt = $con->prepare("SELECT user_avatar FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['user_avatar'])) {
        echo '<img src="' . htmlspecialchars($result['user_avatar'], ENT_QUOTES, 'UTF-8') . '" class="profile-pic" />';
    } else {
        echo '<img src="layout/images/default-avatar.png" class="profile-pic" />';
    }
}

function setErrorAndRedirect($messageKey, $langArray, $location)
{
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray[$messageKey])];
    header("Location: $location");
    exit;
}

function validateAndUploadImage($file, $allowedExts, $maxSize, $uploadsPath, $langArray)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'upload_err'];
    }

    $name = $file['name'];
    if (is_array($name) && count($name) > 1) {
        return ['error' => 'single_file_err'];
    }

    $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $size = $file['size'];

    if (!in_array($fileExt, $allowedExts) || $size > $maxSize) {
        return ['error' => ($size >= $maxSize) ? 'big_file' : 'type_err'];
    }

    $uniqueName = uniqid('', true) . '.' . $fileExt;
    $path = $uploadsPath . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return ['error' => 'upload_err'];
    }

    return ['path' => $path];
}

function displayImage($item_id, $img_nr)
{
    global $con;
    $stmt = $con->prepare("SELECT image FROM items WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if ($item && !empty($item['image'])) {
        $image_paths = explode(',', $item['image']);

        // Check if the requested image index exists
        if (isset($image_paths[$img_nr])) {
            $image = trim($image_paths[$img_nr]);

            if (file_exists($image)) {
                return $image;
            }
        }
    }

    return 'layout/images/default-item.png';
}
