<?php
session_start();
if (!isset($_SESSION['email']) && !isset($_SESSION['group_id']) && $_SESSION['group_id'] == 0) {
    header("Location: ../login.php");
    exit;
} else {
    $pageTitle = 'Members';
    include 'init.php';
    $alert_message = '';

    $user_id = $_SESSION['user_id'];
    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage';

    if ($do == 'Manage') {

        $stmt = $con->prepare("SELECT
                                    promo_codes.*,
                                    categories.cat_name,
                                    items.item_name
                                FROM
                                    promo_codes
                                LEFT JOIN
                                    categories ON promo_codes.cat_id = categories.cat_id
                                LEFT JOIN
                                    items ON promo_codes.item_id = items.item_id;
                            ");
        $stmt->execute();
        $codes = $stmt->fetchAll();
        $count = $stmt->rowCount();


?>
        <h1 class="text-center">Manage Codes</h1>
        <div class="container">

            <?php
            if (isset($_SESSION['alert_message'])) {
                echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
                echo $_SESSION['alert_message']['message'];
                echo '</div>';
                unset($_SESSION['alert_message']);
            }
            ?>

            <div class="shadow">
                <div class="table-responsive">
                    <table class="main-table text-center table table-bordered">
                        <thead>
                            <th>#ID</th>
                            <th>code</th>
                            <th>Discount value</th>
                            <th>Start on</th>
                            <th>End on</th>
                            <th>usage limit</th>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Event</th>
                            <th>Action</th>
                        </thead>


                        <!-- Loop through each user and display their details -->
                        <?php foreach ($codes as $code):
                            $id = $code['id']; // Get the user's ID
                        ?>
                            <tr>
                                <td> <?php echo htmlspecialchars($code['id']) ?> </td>
                                <td> <?php echo htmlspecialchars($code['code']) ?></td>
                                <td> <?php echo htmlspecialchars($code['discount_value']) ?> </td>
                                <td> <?php echo htmlspecialchars($code['start_date']) ?></td>
                                <td> <?php echo htmlspecialchars($code['end_date']) ?></td>
                                <td> <?php echo htmlspecialchars($code['usage_limit']) ?></td>
                                <td> <?php echo ($code['cat_name'] != NULL) ? htmlspecialchars($code['cat_name']) : "NO"; ?></td>
                                <td> <?php echo ($code['item_name'] != NULL) ? htmlspecialchars($code['item_name']) : "NO"; ?></td>
                                <td> <?php echo ($code['event'] != NULL) ? htmlspecialchars($code['event']) : "NO"; ?></td>
                                <td>
                                    <a href="?do=Edit&id=<?php echo $id ?>" class="edit-btn" title="Edit code"><i class='fa fa-edit'></i></a>
                                    <a href="?do=Delete&id=<?php echo $id ?>" class="delete-btn" title="Delete code"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php
            $rows_per_page = 4;
            if (isset($_GET['pageid'])) {
                $pagination = setPagination('promo_codes', $rows_per_page, 'id', $id);
            } else {
                $pagination = setPagination('promo_codes', $rows_per_page, NULL, NULL);
            }
            ?>
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-cat">
                    <li class="page-item <?= ($_GET['page-nr'] ?? 1) <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page-nr=1">First</a>
                    </li>
                    <li class="page-item <?= ($_GET['page-nr'] ?? 1) <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page-nr=<?= ($_GET['page-nr'] ?? 1) - 1 ?>">Previous</a>
                    </li>

                    <?php
                    // Loop through pages and add active class to the current page
                    for ($counter = 1; $counter <= $pagination['pages']; $counter++) {
                        $active = (isset($_GET['page-nr']) && $_GET['page-nr'] == $counter) ? 'active' : '';
                    ?>
                        <li class="page-item <?= $active; ?>">
                            <a class="page-link" href="?page-nr=<?php echo $counter ?>"><?php echo $counter ?></a>
                        </li>
                    <?php
                    }
                    ?>

                    <li class="page-item <?= ($_GET['page-nr'] ?? 1) >= $pagination['pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page-nr=<?= ($_GET['page-nr'] ?? 1) + 1 ?>">Next</a>
                    </li>
                    <li class="page-item <?= ($_GET['page-nr'] ?? 1) >= $pagination['pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page-nr=<?php echo $pagination['pages'] ?>">Last</a>
                    </li>
                </ul>
            </nav>

            <div class="d-grid gap-2 col-6 mx-auto">
                <a href="promo_codes.php?do=Add" class="table-btn btn btn-primary"><i class="fa fa-plus"></i> Add New Code</a>
            </div>
        </div>
    <?php
    } elseif ($do == 'Add') {

        $form_errors = array(
            'code_err' => '',
            'discount_value_err' => '',
            'start_date_err' => '',
            'end_date_err' => '',
            'usage_limit_err' => '',
            'cat_id_err' => '',
            'item_id_err' => '',
            'event_err' => ''
        );

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $code = $_POST['code'];
            $discount_value = $_POST['discount_value'];
            $start_date = date("Y-m-d", strtotime($_POST['start_date']));
            $end_date = date("Y-m-d", strtotime($_POST['end_date']));
            $usage_limit = $_POST['usage_limit'];
            $cat_id = $_POST['category'];;
            $item_id = $_POST['item_id'];
            $event = $_POST['event'];

            // Validate code
            $check_code = checkItem('code', 'promo_codes', $code);
            if (empty($code)) {
                $form_errors['code_err'] = 'Promo code is required.';
            } elseif ($check_code > 0) {
                $form_errors['code_err'] = 'Promo code exist.';
            } elseif (strlen($code) > 50) {
                $form_errors['code_err'] = "Promo code must be 50 characters or fewer.";
            }

            // Validate discount value
            if (empty($discount_value) || !is_numeric($discount_value) || $discount_value <= 0) {
                $form_errors['discount_value_err'] = "Discount value must be a positive number.";
            }

            // Validate dates
            if (empty($start_date) || !strtotime($start_date)) {
                $form_errors['start_date_err'] = "A valid start date is required.";
            } elseif (strtotime($start_date) < strtotime('today')) {
                $form_errors['start_date_err'] = "The start date must be today or in the future.";
            }
            if (empty($end_date) || !strtotime($end_date)) {
                $form_errors['end_date_err'] = "A valid end date is required.";
            } elseif (strtotime($end_date) <= strtotime($start_date)) {
                $form_errors['end_date_err'] = "The end date must be after the start date.";
            }

            // Validate usage limit
            if (empty($usage_limit) || !is_numeric($usage_limit) || $usage_limit <= 0) {
                $form_errors['usage_limit_err'] = "Usage limit must be a positive integer.";
            }

            // Ensure exactly one type (Category, Item, or Event) is selected
            $type_selected_count = !empty($cat_id) + !empty($item_id) + !empty($event);
            if ($type_selected_count === 0) {
                $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Please select one type of code (Category, Item, or Event).'];
                header("Location: promo_codes.php?do=Add");
                exit;
            } elseif ($type_selected_count > 1) {
                $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Please select only one type of code (Category, Item, or Event).'];
                header("Location: promo_codes.php?do=Add");
                exit;
            }

            // If no errors, insert data into the database
            if (!array_filter($form_errors)) {
                $stmt = $con->prepare("INSERT INTO promo_codes (code, discount_value, start_date, end_date, usage_limit, cat_id, item_id, event) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_value, $start_date, $end_date, $usage_limit, $cat_id ?: NULL, $item_id ?: NULL, $event ?: NULL]);

                $_SESSION['alert_message'] = ['type' => 'success', 'message' => 'Promo code added successfully.'];
                header("Location: promo_codes.php?do=Manage");
                exit;
            }
        }
    ?>
        <h1 class="text-center">Add Promo Code</h1>
        <div class="container">
            <?php
            if (isset($_SESSION['alert_message'])) {
                echo "<div class='alert alert-" . $_SESSION['alert_message']['type'] . "' role='alert'>";
                echo $_SESSION['alert_message']['message'];
                echo "</div>";
                unset($_SESSION['alert_message']); // Clear the message after displaying it
            }
            ?>
            <form class="form-horizontal" action="" method="post" style="max-width: 600px; margin: 0 auto;">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputCode" class="col-sm-2 col-form-label">Code</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputCode" name="code" autocomplete="off" placeholder="Name of The Code" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['code_err']; ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputDiscount" class="col-sm-2 col-form-label">Discount Value</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputDiscount" name="discount_value" step="1" min="1" max="100" placeholder="Discount Value" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['discount_value_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputUsage" class="col-sm-2 col-form-label">Usage Limit</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputUsage" name="usage_limit" step="1" min="1" placeholder="The number of usages" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['usage_limit_err']; ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputItem" class="col-sm-2 col-form-label">Item</label>
                    <div class="col-sm-9">
                        <input type="hidden" name="item_id">
                        <input type="text" class="form-control" id="inputItem" name="item_name" placeholder="Search for an item" autocomplete="off">
                    </div>
                    <p class="text-danger"><?php echo $form_errors['item_id_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputCategory" class="col-sm-2 col-form-label">Category</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="category" id="inputCategory" aria-label=".form-select-lg example">
                            <option selected value="0">Select Category</option>
                            <?php
                            $stmt = $con->prepare("SELECT * FROM categories");
                            $stmt->execute();
                            $categories = $stmt->fetchAll();
                            foreach ($categories as $cat) {
                                echo "<option value='" . $cat['cat_id'] . "'> " . $cat['cat_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['cat_id_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputEvent" class="col-sm-2 col-form-label">Event</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputEvent" name="event" autocomplete="false" placeholder="Event name">
                    </div>
                    <p class="text-danger"><?php echo $form_errors['code_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="datepickerStart" class="col-sm-2 col-form-label">Start date</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="datepickerStart" name="start_date">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['start_date_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="datepickerEnd" class="col-sm-2 col-form-label">End date</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="datepickerEnd" name="end_date">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['end_date_err'] ?></p>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg btn-item" type="submit">Add Code</button>
                </div>
            </form>
        </div>
    <?php
    } elseif ($do == 'Edit') {
        $form_errors = array(
            'code_err' => '',
            'discount_value_err' => '',
            'start_date_err' => '',
            'end_date_err' => '',
            'usage_limit_err' => '',
            'cat_id_err' => '',
            'item_id_err' => '',
            'event_err' => ''
        );

        if (isset($_GET['id'])) {
            $code_id = intval($_GET['id']);

            $stmt = $con->prepare("SELECT
                                promo_codes.*,
                                categories.cat_name,
                                items.item_name
                            FROM
                                promo_codes
                            LEFT JOIN
                                categories ON promo_codes.cat_id = categories.cat_id
                            LEFT JOIN
                                items ON promo_codes.item_id = items.item_id
                            WHERE
                                promo_codes.id = ?;
                        ");
            $stmt->execute([$code_id]);
            $code = $stmt->fetch();
            $count = $stmt->rowCount();

            $code_edit = $code['code'];
            $discount_edit = $code['discount_value'];
            $usage_edit = $code['usage_limit'];
            $startDate_edit = $code['start_date'];
            $endDate_edit = $code['end_date'];
            $cat_edit = $code['cat_name'];
            $item_edit = $code['item_name'];
            $event_edit = $code['event'];
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $code = trim($_POST['code']);
            $discount_value = $_POST['discount_value'];
            $start_date = date("Y-m-d", strtotime($_POST['start_date']));
            $end_date = date("Y-m-d", strtotime($_POST['end_date']));
            $usage_limit = $_POST['usage_limit'];
            $cat_id = $_POST['category'];
            $item_id = $_POST['item_id'];
            $event = trim($_POST['event']);

            // After getting input values
            $errors = false; // Flag to check if any validation failed

            // Validate code
            $check_code = ValidateNameByID('promo_codes', 'code', 'id', $code, $code_id);
            if (empty($code)) {
                $form_errors['code_err'] = 'Promo code is required.';
                $errors = true;
            } elseif ($check_code > 0) {
                $form_errors['code_err'] = 'Promo code exists.';
                $errors = true;
            } elseif (strlen($code) > 50) {
                $form_errors['code_err'] = "Promo code must be 50 characters or fewer.";
                $errors = true;
            }

            // Validate discount value
            if (empty($discount_value) || !is_numeric($discount_value) || $discount_value <= 0) {
                $form_errors['discount_value_err'] = "Discount value must be a positive number.";
                $errors = true;
            }

            // Validate dates
            if (empty($start_date) || !strtotime($start_date)) {
                $form_errors['start_date_err'] = "A valid start date is required.";
                $errors = true;
            } elseif (strtotime($start_date) < strtotime('today')) {
                $form_errors['start_date_err'] = "The start date must be today or in the future.";
                $errors = true;
            }

            if (empty($end_date) || !strtotime($end_date)) {
                $form_errors['end_date_err'] = "A valid end date is required.";
                $errors = true;
            } elseif (strtotime($end_date) <= strtotime($start_date)) {
                $form_errors['end_date_err'] = "The end date must be after the start date.";
                $errors = true;
            }

            // Validate usage limit
            if (empty($usage_limit) || !is_numeric($usage_limit) || $usage_limit <= 0) {
                $form_errors['usage_limit_err'] = "Usage limit must be a positive integer.";
                $errors = true;
            }

            // Ensure exactly one type (Category, Item, or Event) is selected
            $type_selected_count = (int)!empty($cat_id) + (int)!empty($item_id) + (int)!empty($event);
            if ($type_selected_count === 0) {
                $form_errors['event_err'] = 'Please select one type of code (Category, Item, or Event).';
                $errors = true;
            } elseif ($type_selected_count > 1) {
                $form_errors['event_err'] = 'Please select only one type of code (Category, Item, or Event).';
                $errors = true;
            }


            // If no errors, insert data into the database
            if (!$errors) {
                $stmt = $con->prepare("UPDATE promo_codes SET code = ?, discount_value = ?, start_date = ?, end_date = ?, usage_limit = ?, cat_id = ?, item_id = ?, event = ? WHERE id = ?");
                $updateCode = $stmt->execute([$code, $discount_value, $start_date, $end_date, $usage_limit, $cat_id ?: NULL, $item_id ?: NULL, $event ?: NULL, $code_id]);

                $_SESSION['alert_message'] = ['type' => 'success', 'message' => 'Promo code updated successfully.'];
                header("Location: promo_codes.php?do=Manage");
                exit;
            }
        }
    ?>
        <h1 class="text-center">Edit Promo Code</h1>
        <div class="container">
            <?php
            if (isset($_SESSION['alert_message'])) {
                echo "<div class='alert alert-" . $_SESSION['alert_message']['type'] . "' role='alert'>";
                echo $_SESSION['alert_message']['message'];
                echo "</div>";
                unset($_SESSION['alert_message']); // Clear the message after displaying it
            }
            ?>
            <form class="form-horizontal" action="" method="post" style="max-width: 600px; margin: 0 auto;">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputCode" class="col-sm-2 col-form-label">Code</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputCode" name="code" autocomplete="off" placeholder="Name of The Code" value="<?php echo $code_edit ?>" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['code_err']; ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputDiscount" class="col-sm-2 col-form-label">Discount Value</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputDiscount" name="discount_value" step="1" min="1" max="100" placeholder="Discount Value" value="<?php echo $discount_edit ?>" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['discount_value_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputUsage" class="col-sm-2 col-form-label">Usage Limit</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputUsage" name="usage_limit" step="1" min="1" placeholder="The number of usages" value="<?php echo $usage_edit ?>" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['usage_limit_err']; ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputItem" class="col-sm-2 col-form-label">Item</label>
                    <div class="col-sm-9">
                        <input type="hidden" name="item_id" value="<?php echo $code['item_id']; ?>">
                        <input type="text" class="form-control" id="inputItem" name="item_name" placeholder="Search for an item" autocomplete="off" value="<?php echo $item_edit ?>">
                    </div>
                    <p class="text-danger"><?php echo $form_errors['item_id_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputCategory" class="col-sm-2 col-form-label">Category</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="category" id="inputCategory" aria-label=".form-select-lg example">
                            <option selected value="0">Select Category</option>
                            <?php
                            $stmt = $con->prepare("SELECT * FROM categories");
                            $stmt->execute();
                            $cats = $stmt->fetchAll();
                            foreach ($cats as $cat) {
                                echo "<option value='" . $cat['cat_id'] . "'";
                                if ($cat_edit == $cat['cat_name']) {
                                    echo 'selected';
                                }
                                echo ">" . $cat['cat_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['cat_id_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputEvent" class="col-sm-2 col-form-label">Event</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputEvent" name="event" autocomplete="false" placeholder="Event name" value="<?php echo $event_edit ?>">
                    </div>
                    <p class="text-danger"><?php echo $form_errors['event_err']; ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="datepickerStart" class="col-sm-2 col-form-label">Start date</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="datepickerStart" name="start_date" value="<?php echo $startDate_edit ?>">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['start_date_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="datepickerEnd" class="col-sm-2 col-form-label">End date</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="datepickerEnd" name="end_date" value="<?php echo $endDate_edit ?>">
                        <i class="fa fa-calendar-o" aria-hidden="true"></i>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['end_date_err'] ?></p>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg btn-item" type="submit">Edit Code</button>
                </div>
            </form>
        </div>
<?php
    } elseif ($do == 'Delete') {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);

            // Fetch the item from the database
            $stmt = $con->prepare("SELECT * FROM promo_codes WHERE id = ?");
            $stmt->execute([$id]);
            $code = $stmt->fetch();

            // Check if the item exists
            if (!$code) {
                echo "Error: Code not found.";
                exit();
            }
        } else {
            echo "Error: Code ID not provided.";
            exit();
        }

        $stmt = $con->prepare("DELETE FROM promo_codes WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            // If rows were updated, show success message
            $_SESSION['alert_message'] = ['type' => 'success', 'message' => 'Deleted sucessfully'];
            header("Location: promo_codes.php?do=Manage");
        } else {
            $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Failed while deleting'];
            header("Location: promo_codes.php?do=Manage");
        }
        exit;
    }
    include $tmp . 'footer.php';
}
?>