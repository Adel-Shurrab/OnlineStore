<?php
session_start(); // Start or resume session
$pageTitle = 'Items'; // Set the page title

if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) { // Check if user is logged in
    include 'init.php'; // Include initialization file (e.g., database connection, header)

    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage'; // Get the 'do' parameter, default to 'Manage'

    // Manage categories logic based on 'do' value
    if ($do == 'Manage') {
        //Start Manage Page

        $rows_per_page = 4;
        $pagination = setPagination('items', $rows_per_page);

        // Select all items
        $stmt = $con->prepare("SELECT 
                                    items.*, 
                                    users.first_name AS first_name, 
                                    users.last_name AS last_name, 
                                    categories.cat_name AS category_name 
                                FROM 
                                    items
                                INNER JOIN 
                                    categories 
                                ON 
                                    categories.cat_id = items.cat_id
                                INNER JOIN 
                                    users 
                                ON 
                                    users.user_id = items.user_id
                                LIMIT " . $pagination['start'] . ", " . $rows_per_page);
        $stmt->execute();
        $rows = $stmt->fetchAll(); // Fetch all the users
?>
        <h1 class="text-center">Manage Items</h1>
        <div class="container">

            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Item Deleted Successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div class="alert alert-success success-message" role="alert">Item Approved Successfully!</div>';
            }
            ?>

            <div class="shadow">
                <div class="table-responsive">
                    <table class="main-table text-center table table-bordered">
                        <thead>
                            <th>#ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Adding Date</th>
                            <th>Category</th>
                            <th>Username</th>
                            <th>Control</th>
                        </thead>

                        <?php
                        // Loop through each user and display their details
                        foreach ($rows as $row):
                            $id = $row['item_id']; // Get the item's ID
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($id); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['price']); ?>$</td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($row['add_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td>
                                    <a href="?do=Edit&itemid=<?php echo $id; ?>" class="edit-btn" title="Edit item"><i class='fa fa-edit'></i></a>
                                    <a href="?do=Delete&itemid=<?php echo $id; ?>" class="delete-btn" title="Delete item"><i class="fa fa-trash"></i></a>
                                    <?php if ($row['approve'] == 0): ?>
                                        <a class='approve-btn' title='Approve item' href='?do=Approve&itemid=<?php echo $id; ?>'><i class='fa fa-check' aria-hidden='true'></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </table>
                </div>
            </div>

            <!-- Pagination -->
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
                <a href="items.php?do=Add" class="table-btn btn btn-primary"><i class="fa fa-plus"></i> Add New Item</a>
            </div>
        </div>
    <?php

    } elseif ($do == 'Add') {
        //Start Add Page

        $form_errors = array(
            'name_err'    => '',
            'desc_err'    => '',
            'price_err'   => '',
            'quantity_err' => '',
            'country_err' => '',
            'status_err'  => '',
            'member_err'  => '',
            'cat_err'     => ''
        );

        // If form is submitted via POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Gather form input data
            $itemid    = $_POST['itemid'];
            $name      = $_POST['name'];
            $desc      = $_POST['desc'];
            $price     = $_POST['price'];
            $quantity  = $_POST['quantity'];
            $country   = $_POST['country'];
            $status    = $_POST['status'];
            $member    = $_POST['member'];
            $category  = $_POST['category'];

            // Validate item inputs and store errors if any 

            // Check if Category exist in the Database
            $item_error = ValidateNameByID('items', 'item_name', 'item_id', $name, $itemid);
            if ($item_error) {
                $form_errors['name_err'] = $item_error;
            }

            if ($status == 0) {
                $form_errors['status_err'] = 'You must Select the Item Status';
            }

            if ($member == 0) {
                $form_errors['member_err'] = 'You must Select a Member';
            }

            if ($category == 0) {
                $form_errors['cat_err'] = 'You must Select an Category';
            }

            if ($price < 0.99) {
                $form_errors['price_err'] = 'You cant Put a Price Less than 0.99$';
            }

            if ($quantity < 1) {
                $form_errors['quantity_err'] = 'You cant Put a Quantity Less than 1';
            }

            // If no validation errors, insert the new member into the database
            if (empty($form_errors['name_err']) && empty($form_errors['desc_err']) && empty($form_errors['price_err']) && empty($form_errors['country_err']) && empty($form_errors['status_err']) && empty($form_errors['member_err'])) {
                $stmt = $con->prepare("INSERT INTO items(item_name, description, price, quantity, add_date, country_made, status, user_id, cat_id) VALUES (?, ?, ?, ?,now(), ?, ?, ?, ?)");
                $stmt->execute(array($name, $desc, $price, $quantity, $country, $status, $member, $category));

                // Redirect to Add page with success message
                header("Location: items.php?do=Add&success=1");
                exit();
            }
        }

    ?>
        <!-- Add Item Page -->
        <h1 class="text-center">Add New Item</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Category Added successfully!</div>';
            }
            ?>
            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="itemid" value="<?php echo $itemid ?>">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputname" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputname" name="name" autocomplete="false" placeholder="Name of The Item" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['name_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputDescription" name="desc" autocomplete="false" placeholder="Description of The Item" required>
                        <span class="asterisk">*</span>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputPrice" class="col-sm-2 col-form-label">Price</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputPrice" name="price" step="0.01" placeholder="Price of The Item" required>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['price_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputQuantity" class="col-sm-2 col-form-label">Quantity</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputQuantity" step="1" name="quantity" placeholder="Quantity of The Item" required>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['quantity_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputCountry" class="col-sm-2 col-form-label">Country</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputCountry" name="country" placeholder="Country of Made" required>
                        <span class="asterisk">*</span>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputStatus" class="col-sm-2 col-form-label">Status</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="status" id="inputStatus" aria-label=".form-select-lg example">
                            <option selected value="0">Select Item Status</option>
                            <option value="1">New</option>
                            <option value="2">Like New</option>
                            <option value="3">Used</option>
                            <option value="3">Very Old</option>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['status_err'] ?></p>
                </div>

                <!-- Start Members Field -->
                <div class="row mb-4 align-items-center">
                    <label for="inputMember" class="col-sm-2 col-form-label">Member</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="member" id="inputMember" aria-label=".form-select-lg example">
                            <option selected value="0">Select User</option>
                            <?php
                            $stmt = $con->prepare("SELECT * FROM users");
                            $stmt->execute();
                            $users = $stmt->fetchAll();
                            foreach ($users as $user) {
                                echo "<option value='" . $user['user_id'] . "'> " . $user['first_name'] . ' ' . $user['last_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['member_err'] ?></p>
                </div>
                <!-- End Members Field -->

                <!-- Start Category Field -->
                <div class="row mb-4 align-items-center">
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
                    <p class="text-danger"><?php echo $form_errors['cat_err'] ?></p>
                </div>
                <!-- End Category Field -->

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg btn-item" type="submit">Add Item</button>
                </div>
            </form>
        </div>

    <?php

    } elseif ($do == 'Edit') {
        //Start Edit Page
        $form_errors = array(
            'name_err'    => '',
            'desc_err'    => '',
            'price_err'   => '',
            'quantity_err' => '',
            'country_err' => '',
            'status_err'  => '',
            'member_err'  => '',
            'cat_err'     => ''
        );

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['itemid'])) {
                header('Location: ../login.php');
                exit();
            }
            $itemid = intval($_GET['itemid']);

            $stmt = $con->prepare("SELECT * FROM items WHERE item_id=?");
            $stmt->execute(array($itemid));
            $row = $stmt->fetch();
            $count = $stmt->rowCount();

            if ($count == 0) { // Check if record count is 0 (no rows found)
                echo "There is no such ID";
                exit();
            }

            // Fetch values to display in form
            $name_edit      = $row['item_name'];
            $desc_edit      = $row['description'];
            $price_edit     = $row['price'];
            $quantity_edit  = $row['quantity'];
            $country_edit   = $row['country_made'];
            $status_edit    = $row['status'];
            $cat_edit       = $row['cat_id'];
            $user_edit      = $row['user_id'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get posted data
            $itemid      = $_POST['itemid'];
            $name      = $_POST['name'];
            $desc      = $_POST['desc'];
            $price     = $_POST['price'];
            $quantity  = $_POST['quantity'];
            $country   = $_POST['country'];
            $status    = $_POST['status'];
            $member    = $_POST['member'];
            $category  = $_POST['category'];

            // Check if the category name already exists
            $item_error = ValidateNameByID('items', 'item_name', 'item_id', $name, $itemid);
            if ($item_error) {
                $form_errors['name_err'] = $item_error;
            }

            if ($status == 0) {
                $form_errors['status_err'] = 'You must Select the Item Status';
            }

            if ($member == 0) {
                $form_errors['member_err'] = 'You must Select a Member';
            }

            if ($category == 0) {
                $form_errors['cat_err'] = 'You must Select an Category';
            }

            if ($price < 0.99) {
                $form_errors['price_err'] = 'You cant Put a Price Less than 0.99$';
            }

            if ($quantity < 1) {
                $form_errors['quantity_err'] = 'You cant Put a Quantity Less than 1';
            }


            if (empty($form_errors['name_err']) && empty($form_errors['desc_err']) && empty($form_errors['price_err']) && empty($form_errors['country_err']) && empty($form_errors['status_err']) && empty($form_errors['member_err'])) {
                // Prepare the SQL statement to update the category
                $stmt = $con->prepare("UPDATE items SET item_name = ?, description = ?, price = ?, quantity = ?, country_made = ?, status = ?, cat_id  = ?, user_id   = ? WHERE item_id = ?");

                // Execute the query
                $stmt->execute(array($name, $desc, $price, $quantity, $country, $status, $category, $member, $itemid));

                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    // If rows were updated, show success message
                    header("Location: items.php?do=Edit&itemid=$itemid&success=1");
                } else {
                    // No rows were updated (no changes were made)
                    header("Location: items.php?do=Edit&itemid=$itemid&success=0");
                }

                exit();
            }

            // Retain posted values in the form on validation error
            $name_edit      = $name;
            $desc_edit      = $desc;
            $price_edit     = $price;
            $quantity_edit  = $quantity;
            $country_edit   = $country;
            $status_edit    = $status;
            $cat_edit       = $category;
            $user_edit      = $member;
        }
    ?>
        <!-- Edit Item Page -->
        <h1 class="text-center">Edit Item</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Item Updated successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 0) {
                echo '<div class="alert alert-warning warning-alert" role="alert">No changes were made. No record updated.</div>';
            }
            ?>
            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="itemid" value="<?php echo $itemid ?>">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputname" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputname" name="name" autocomplete="false" value="<?php echo $name_edit ?>" placeholder="Name of The Item" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['name_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputDescription" name="desc" autocomplete="false" value="<?php echo $desc_edit ?>" placeholder="Description of The Item" required>
                        <span class="asterisk">*</span>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputPrice" class="col-sm-2 col-form-label">Price</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputPrice" name="price" step="0.01" value="<?php echo $price_edit ?>" placeholder="Price of The Item" required>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['price_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputQuantity" class="col-sm-2 col-form-label">Quantity</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="inputQuantity" step="1" name="quantity" value="<?php echo $quantity_edit ?>" placeholder="Quantity of The Item" required>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['quantity_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputCountry" class="col-sm-2 col-form-label">Country</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputCountry" name="country" value="<?php echo $country_edit ?>" placeholder="Country of Made" required>
                        <span class="asterisk">*</span>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputStatus" class="col-sm-2 col-form-label">Status</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="status" id="inputStatus" aria-label=".form-select-lg example">
                            <?php
                            $new_select = '';
                            $likenew_select = '';
                            $usedselect = '';
                            $oldselect = '';
                            if ($status_edit == 1) {
                                $new_select = 'selected';
                            } elseif ($status_edit == 2) {
                                $likenew_select = 'selected';
                            } elseif ($status_edit == 3) {
                                $usedselect = 'selected';
                            } elseif ($status_edit == 4) {
                                $oldselect = 'selected';
                            }
                            ?>
                            <option value="0">Select Item Status</option>
                            <option <?php echo $new_select ?> value="1">New</option>
                            <option <?php echo $likenew_select ?> value="2">Like New</option>
                            <option <?php echo $usedselect ?> value="3">Used</option>
                            <option <?php echo $oldselect ?> value="3">Very Old</option>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['status_err'] ?></p>
                </div>

                <!-- Start Members Field -->
                <div class="row mb-4 align-items-center">
                    <label for="inputMember" class="col-sm-2 col-form-label">Member</label>
                    <div class="col-sm-9">
                        <select class="form-select form-select mb-3" name="member" id="inputMember" aria-label=".form-select-lg example">
                            <option selected value="0">Select User</option>
                            <?php
                            $stmt = $con->prepare("SELECT * FROM users");
                            $stmt->execute();
                            $users = $stmt->fetchAll();
                            foreach ($users as $user) {
                                echo "<option value='" . $user['user_id'] . "'";
                                if ($user_edit == $user['user_id']) {
                                    echo 'selected';
                                }
                                echo ">" . $user['first_name'] . $user['last_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['member_err'] ?></p>
                </div>
                <!-- End Members Field -->

                <!-- Start Category Field -->
                <div class="row mb-4 align-items-center">
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
                                if ($cat_edit == $cat['cat_id']) {
                                    echo 'selected';
                                }
                                echo ">" . $cat['cat_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['cat_err'] ?></p>
                </div>
                <!-- End Category Field -->

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg btn-item" type="submit">Edit Item</button>
                </div>
            </form>
        </div>
<?php
    } elseif ($do == 'Delete') {
        //Start Delete Logic
        $item_id = $_GET['itemid'];

        $stmt = $con->prepare("DELETE FROM items WHERE item_id=?");
        $stmt->execute(array($item_id));
        header('Location: items.php?do=Manage&success=1');
    } elseif ($do == 'Approve') {
        //Start Approve Logic

        $item_id = $_GET['itemid'];

        $stmt = $con->prepare("UPDATE items SET approve = 1 WHERE item_id = ?");
        $stmt->execute(array($item_id));
        header('Location: items.php?do=Manage&success=3');
    }
    include $tmp . 'footer.php'; // Include the footer
} else {
    header("Location: dashboard.php"); // Redirect to dashboard if not logged in
    exit();
}
