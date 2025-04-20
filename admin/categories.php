<?php
session_start(); // Start or resume session
$pageTitle = 'Categories'; // Set the page title

if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) { // Check if user is logged in and admin
    include 'init.php'; // Include initialization file (e.g., database connection, header)

    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage'; // Get the 'do' parameter, default to 'Manage'

    // Manage categories logic based on 'do' value
    if ($do == 'Manage') {
        // Manage categories logic
        $sort = 'ASC'; // Default sorting order
        $sort_arr = array('ASC', 'DESC');

        if (isset($_GET['sort']) && in_array($_GET['sort'], $sort_arr)) {
            $sort = $_GET['sort'];
        }

        $rows_per_page = 4;
        $pagination = setPagination('categories', $rows_per_page);

        // End pagination


        $stmt = $con->prepare("SELECT * FROM categories ORDER BY ordering $sort LIMIT " . $pagination['start'] . ", " . $rows_per_page);
        $stmt->execute();

        $rows = $stmt->fetchAll(); // Fetch all the categories


?>
        <h1 class="text-center">Manage Categories</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Category Deleted Successfully!</div>';
            }
            ?>
            <div class="card">
                <h5 class="card-header header-cat">
                    Manage Categories
                    <div class="ordering">
                        Ordering:
                        <a href="?sort=ASC" class="<?php if ($sort == 'ASC') {
                                                        echo 'active';
                                                    } ?>"><i class="fa fa-arrow-up" aria-hidden="true"></i></a> |
                        <a href="?sort=DESC" class="<?php if ($sort == 'DESC') {
                                                        echo 'active';
                                                    } ?>"><i class="fa fa-arrow-down" aria-hidden="true"></i></a>
                    </div>
                </h5>

                <?php
                foreach ($rows as $cat) {
                    echo "<div class='card-body card-line'>";
                    echo "<div class='button-cont'>";
                    echo "<h5 class='card-title'>" . $cat['cat_name'];
                    echo "</h5>";
                    echo "<div class='buttons'>";
                    $cat_id = $cat['cat_id'];
                    echo "<a class='btn btn-primary pull-right' href='?do=Edit&catid=$cat_id'><i class='fa fa-edit'></i> Edit</a>";
                    echo "<a class='btn btn-danger pull-right' href='?do=Delete&catid=$cat_id'><i class='fa fa-trash-o' aria-hidden='true'></i> Delete</a>";
                    echo "</div>";
                    echo "</div>";

                    echo "<p class='card-text'>";
                    if (empty($cat['description'])) {
                        echo 'This Category has no description';
                    } else {
                        echo $cat['description'];
                    }
                    echo "</p>";

                    if ($cat['visibility'] == 1) {
                        echo "<span class='visibility'><i class='fa fa-eye-slash' aria-hidden='true'></i> Hidden</span>";
                    } else {
                        echo "<span class='visibility'><i class='fa fa-eye' aria-hidden='true'></i> Visible</span>";
                    }

                    if ($cat['allow_comment'] == 1) {
                        echo "<span class='commenting'><i class='fa fa-times' aria-hidden='true'></i> Comment Disabled</span>";
                    } else {
                        echo "<span class='commenting'><i class='fa fa-check' aria-hidden='true'></i> Comment Enabled</span>";
                    }

                    if ($cat['allow_ads'] == 1) {
                        echo "<span class='advertises'><i class='fa fa-times' aria-hidden='true'></i> Ads Disabled</span>";
                    } else {
                        echo "<span class='advertises'><i class='fa fa-check' aria-hidden='true'></i> Ads Enabled</span>";
                    }
                    echo "</div>";
                }
                ?>
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

            <div class="d-grid gap-2 col-6 mx-auto cat-btn">
                <a href="categories.php?do=Add" class="table-btn btn btn-primary btn-cat"><i class="fa fa-plus"></i> Add New Category</a>
            </div>
        </div>

    <?php


    } elseif ($do == 'Add') {
        // Add category Logic
        $form_errors = array(
            'name_err' => '',
        );

        // If form is submitted via POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Gather form input data
            $name = $_POST['name'];
            $desc = $_POST['desc'];
            $order = $_POST['order'];
            $visiblity = $_POST['visiblity'];
            $comment = $_POST['comment'];
            $ads = $_POST['ads'];

            // Check if Category exist in the Database
            $check = checkItem('cat_name', 'categories', $name);
            if ($check == 1) {
                $form_errors['name_err'] = "Sorry this category is already exist";
            }

            // If no validation errors, insert the new member into the database
            if (empty($form_errors['name_err'])) {
                $stmt = $con->prepare("INSERT INTO categories(cat_name , description, ordering, visibility, allow_comment, allow_ads) VALUES (?, ?, ?, ?,?,?)");
                $stmt->execute(array($name, $desc, $order, $visiblity, $comment, $ads));

                // Redirect to Add page with success message
                header("Location: categories.php?do=Add&success=1");
                exit();
            }
        }
    ?>
        <!-- Add category Page -->
        <h1 class="text-center">Add New Category</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Category Added successfully!</div>';
            }
            ?>
            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputname" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputname" name="name" autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['name_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputDescription" name="desc" autocomplete="false">
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputOrder" class="col-sm-2 col-form-label">Ordering</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputOrder" name="order">
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputfullName" class="col-sm-2 col-form-label">Visible</label>
                    <div class="col-sm-9">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="visiblity" id="vis-yes" value="0" checked>
                            <label class="form-check-label" for="vis-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="visiblity" id="vis-no" value="1">
                            <label class="form-check-label" for="vis-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="comment" class="col-sm-2 col-form-label com-label">Allow Commenting</label>
                    <div class="col-sm-10">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="comment" id="com-yes" value="0" checked>
                            <label class="form-check-label" for="com-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="comment" id="com-no" value="1">
                            <label class="form-check-label" for="com-no">No</label>
                        </div>
                    </div>
                </div>


                <div class="row mb-4 align-items-center">
                    <label for="inputfullName" class="col-sm-2 col-form-label">Allow Ads</label>
                    <div class="col-sm-9">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="ads" id="ads-yes" value="0" checked>
                            <label class="form-check-label" for="ads-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="ads" id="ads-no" value="1">
                            <label class="form-check-label" for="ads-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg" type="submit">Add Category</button>
                </div>
            </form>
        </div>

    <?php

    } elseif ($do == 'Edit') {
        // Start Edit Page
        $form_errors = array(
            'name_err' => ''
        );

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['catid'])) {
                header('Location: ../login.php');
                exit();
            }
            $catid = intval($_GET['catid']);

            $stmt = $con->prepare("SELECT * FROM categories WHERE cat_id = ?");
            $stmt->execute(array($catid));
            $row = $stmt->fetch();
            $count = $stmt->rowCount();

            if ($count == 0) { // Check if record count is 0 (no rows found)
                echo "There is no such ID";
                exit();
            }

            // Fetch values to display in form
            $name_edit = $row['cat_name'];
            $desc_edit = $row['description'];
            $order_edit = $row['ordering'];
            $visiblity_edit = $row['visibility'];
            $comment_edit = $row['allow_comment'];
            $ads_edit = $row['allow_ads'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get posted data
            $catid = $_POST['catid'];
            $name = $_POST['name'];
            $desc = $_POST['desc'];
            $order = $_POST['order'];
            $visiblity = $_POST['visiblity'];
            $comment = $_POST['comment'];
            $ads = $_POST['ads'];

            // Check if the category name already exists
            $category_error = ValidateNameByID('categories', 'cat_name', 'cat_id', $name, $catid);
            if ($category_error) {
                $form_errors['name_err'] = $category_error;
            }

            if (empty($form_errors['name_err'])) {
                // Prepare the SQL statement to update the category
                $stmt = $con->prepare("UPDATE categories SET cat_name = ?, description = ?, ordering = ?, visibility = ?, allow_comment = ?, allow_ads = ? WHERE cat_id = ?");

                // Execute the query
                $stmt->execute(array($name, $desc, $order, $visiblity, $comment, $ads, $catid));

                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    // If rows were updated, show success message
                    header("Location: categories.php?do=Edit&catid=$catid&success=1");
                } else {
                    // No rows were updated (no changes were made)
                    header("Location: categories.php?do=Edit&catid=$catid&success=0");
                }

                exit();
            }


            // Retain posted values in the form on validation error
            $name_edit = $name;
            $desc_edit = $desc;
            $order_edit = $order;
            $visiblity_edit = $visiblity;
            $comment_edit = $comment;
            $ads_edit = $ads;
        }

    ?>

        <h1 class="text-center">Edit Category</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Category updated successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 0) {
                echo '<div class="alert alert-warning warning-alert" role="alert">No changes were made. No record updated.</div>';
            }
            ?>
            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="catid" value="<?php echo $catid ?>">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputname" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputname" name="name" value="<?php echo $name_edit ?>" autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['name_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputDescription" name="desc" value="<?php echo $desc_edit ?>" autocomplete="false">
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputOrder" class="col-sm-2 col-form-label">Ordering</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputOrder" name="order" value="<?php echo $order_edit ?>">
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputfullName" class="col-sm-2 col-form-label">Visible</label>
                    <div class="col-sm-9">
                        <div class="form-check form-check-inline">
                            <?php
                            $yes_checked = '';
                            $no_checked = '';
                            if ($visiblity_edit == 0) {
                                $yes_checked = 'checked';
                            } elseif ($visiblity_edit == 1) {
                                $no_checked = 'checked';
                            }
                            ?>
                            <input class="form-check-input" type="radio" name="visiblity" id="vis-yes" value="0" <?php echo $yes_checked ?>>
                            <label class="form-check-label" for="vis-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="visiblity" id="vis-no" value="1" <?php echo $no_checked ?>>
                            <label class="form-check-label" for="vis-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="comment" class="col-sm-2 col-form-label com-label">Allow Commenting</label>
                    <div class="col-sm-10">
                        <div class="form-check form-check-inline">
                            <?php
                            $yes_checked = '';
                            $no_checked = '';
                            if ($comment_edit == 0) {
                                $yes_checked = 'checked';
                            } elseif ($comment_edit == 1) {
                                $no_checked = 'checked';
                            }
                            ?>
                            <input class="form-check-input" type="radio" name="comment" id="com-yes" value="0" <?php echo $yes_checked ?>>
                            <label class="form-check-label" for="com-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="comment" id="com-no" value="1" <?php echo $no_checked ?>>
                            <label class="form-check-label" for="com-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputfullName" class="col-sm-2 col-form-label">Allow Ads</label>
                    <div class="col-sm-9">
                        <div class="form-check form-check-inline">
                            <?php
                            $yes_checked = '';
                            $no_checked = '';
                            if ($ads_edit == 0) {
                                $yes_checked = 'checked';
                            } elseif ($ads_edit == 1) {
                                $no_checked = 'checked';
                            }
                            ?>
                            <input class="form-check-input" type="radio" name="ads" id="ads-yes" value="0" <?php echo $yes_checked ?>>
                            <label class="form-check-label" for="ads-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="ads" id="ads-no" value="1" <?php echo $no_checked ?>>
                            <label class="form-check-label" for="ads-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg" type="submit">Edit Category</button>
                </div>
            </form>
        </div>
<?php

    } elseif ($do == 'Delete') {
        // Delete category logic
        $cat_id = $_GET['catid'];

        $stmt = $con->prepare("DELETE FROM categories WHERE cat_id=?");
        $stmt->execute(array($cat_id));
        header('Location: categories.php?do=Manage&success=1');
    }

    include $tmp . 'footer.php'; // Include the footer
} else {
    header("Location: dashboard.php"); // Redirect to dashboard if not logged in
    exit();
}
?>