<!-- Members Page -->

<?php
session_start(); // Start or resume the session
if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) { // Check if the user is logged in
    $global_id = $_SESSION['user_id']; // Store the current logged-in user ID in a global variable
    $pageTitle = 'Members'; // Set the page title
    include 'init.php'; // Include the initialization file (e.g., database connection, header)
    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage'; // Determine the action from the 'do' parameter, default to 'Manage'

    // Start Manage Page
    if ($do == 'Manage') { // Manage Members page logic

        $query = '';
        // Check if the page is for managing pending members
        if (isset($_GET['Page']) && $_GET['Page'] == 'Pending') {
            $query = 'AND reg_stat = 0'; // Filter for members with registration status pending
        }

        $rows_per_page = 4;
        $pagination = setPagination('users', $rows_per_page);

        // Select all users except admin (group_id != 1)
        $stmt = $con->prepare("SELECT * FROM users WHERE group_id != 1 $query LIMIT " . $pagination['start'] . ", " . $rows_per_page);
        $stmt->execute();
        $rows = $stmt->fetchAll(); // Fetch all the users

?>
        <h1 class="text-center">Manage Members</h1>
        <div class="container">

            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Member Deleted Successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div class="alert alert-success success-message" role="alert">Member Activated Successfully!</div>';
            }
            ?>

            <div class="shadow">
                <div class="table-responsive">
                    <table class="main-table text-center table table-bordered">
                        <thead>
                            <th>#ID</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Registered Date</th>
                            <th>Control</th>
                        </thead>


                        <!-- Loop through each user and display their details -->
                        <?php foreach ($rows as $row):
                            $id = $row['user_id']; // Get the user's ID
                        ?>
                            <tr>
                                <td> <?php echo htmlspecialchars($row['user_id']) ?> </td>
                                <td> <?php echo htmlspecialchars($row['email']) ?></td>
                                <td> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?> </td>
                                <td> <?php echo htmlspecialchars($row['registerdDate']) ?></td>
                                <td>
                                    <a href="?do=Edit&userid=<?php echo $id ?>" class="edit-btn" title="Edit member"><i class='fa fa-edit'></i></a>
                                    <a href="?do=Delete&userid=<?php echo $id ?>" class="delete-btn" title="Delete member"><i class="fa fa-trash"></i></a>
                                    <?php
                                    if ($row['reg_stat'] == 0) {
                                        echo "<a class='approve-btn' title='Activate member' href='?do=Active&userid=$id'><i class='fa fa-check' aria-hidden='true'></i></a>";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
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
                <a href="members.php?do=Add" class="table-btn btn btn-primary"><i class="fa fa-user-plus"></i>Add New Member</a>
            </div>
        </div>
    <?php
    } elseif ($do == 'Add') { // Add Member page logic

        $form_errors = array(
            'email_err' => '',
            'pass_err' => '',
            'fname_err' => '',
            'lname_err' => ''
        );

        // If form is submitted via POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Gather form input data
            $email = $_POST['email'];
            $fname = $_POST['fname'];
            $lname = $_POST['lname'];
            $password = $_POST['password'];

            // Validate user inputs and store errors if any
            $pass_error = ValidatePassword($password); // Validate password
            if (empty($password)) {
                $form_errors['pass_err'] = "can't be empty";
            } elseif ($pass_error) {
                $form_errors['pass_err'] = $pass_error;
            } else {
                // Hash password if validation passes
                $pass = sha1($password);
            }

            $check = checkItem("email", "users", $email); // Use the modified function
            if (empty($email)) {
                $form_errors['email_err'] = "can't be empty";
            } elseif ($check > 0) {
                $form_errors['email_err'] = "Email already exists."; // Return error message if email exists
            }

            $fname_err = ValidateName($fname); // Validate first name
            if (empty($fname)) {
                $form_errors['fname_err'] = "can't be empty";
            } elseif ($fname_err) {
                $form_errors['fname_err'] = $fname_err;
            }

            $lname_err = ValidateName($lname); // Validate last name
            if (empty($lname)) {
                $form_errors['lname_err'] = "can't be empty";
            } elseif ($lname_err) {
                $form_errors['lname_err'] = $lname_err;
            }

            // If no validation errors, insert the new member into the database
            if (empty($form_errors['user_err']) && empty($form_errors['pass_err']) && empty($form_errors['email_err']) && empty($form_errors['fullname_err'])) {
                $stmt = $con->prepare("INSERT INTO users(email, password, first_name, last_name, reg_stat) VALUES (?, ?, ?, ?,1)");
                $stmt->execute(array($email, $pass, $fname, $lname));

                // Redirect to Add page with success message
                header("Location: members.php?do=Add&success=1");
                exit();
            }
        }

    ?>
        <h1 class="text-center">Add New Member</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Member Added successfully!</div>';
            }
            ?>

            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <div class="form-group row mb-4 align-items-center">
                    <label for="inputfname" class="col-sm-2 col-form-label">First name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputfname" name="fname" autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['fname_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputlname" class="col-sm-2 col-form-label">Last name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputlname" name="lname" autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['lname_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-9">
                        <i class="fa fa-solid fa-eye" id="togglePassword" style="display: none;"></i> <!-- Start hidden -->
                        <input type="password" class="form-control" id="inputPassword" name="password" autocomplete="false"
                            required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['pass_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" id="inputEmail" name="email" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['email_err'] ?></p>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg" type="submit">Add Member</button>
                </div>
            </form>
        </div>
    <?php

    } elseif ($do == 'Edit') {  // Start Edit Page

        $form_errors = array(
            'email_err' => '',
            'pass_err' => '',
            'fname_err' => '',
            'lname_err' => ''
        );

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['userid'])) {
                header('Location: ../login.php');
                exit();
            }

            $userid = intval($_GET['userid']);

            // Fetch the user information from the database
            $stmt = $con->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute(array($userid));
            $row = $stmt->fetch();
            $count = $stmt->rowCount();

            if ($count == 0) {
                echo "There is no such ID";
                exit();
            }

            // Store values to populate the form
            $fname_edit = $row['first_name'];
            $lname_edit = $row['last_name'];
            $password_edit = $row['password'];
            $email_edit = $row['email'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userid = $_POST['userid'];
            $email = $_POST['email'];
            $old_pass = $_POST['old_password'];
            $new_pass = $_POST['new_password'];
            $fname = $_POST['fname'];
            $lname = $_POST['lname'];

            // Validate inputs and collect errors

            // Validate new password if provided
            if (!empty($new_pass)) {
                $pass_error = ValidatePassword($new_pass);
                if ($pass_error) {
                    $form_errors['pass_err'] = $pass_error;
                } else {
                    $pass = sha1($new_pass);
                }
            } else {
                $pass = $old_pass;
            }

            $email_error = ValidateEmailByID($email, $userid);
            if (empty($email)) {
                $form_errors['email_err'] = "can't be empty";
            } elseif ($email_error) {
                $form_errors['email_err'] = $email_error;
            }

            $fname_err = ValidateName($fname); // Validate first name
            if (empty($fname)) {
                $form_errors['fname_err'] = "can't be empty";
            } elseif ($fname_err) {
                $form_errors['fname_err'] = $fname_err;
            }

            $lname_err = ValidateName($lname); // Validate last name
            if (empty($lname)) {
                $form_errors['lname_err'] = "can't be empty";
            } elseif ($lname_err) {
                $form_errors['lname_err'] = $lname_err;
            }

            // Only update if no validation errors exist
            if (empty(array_filter($form_errors))) {
                // Prepare the update query
                $stmt = $con->prepare("UPDATE users SET email = ?, password = ?, first_name = ?, last_name = ? WHERE user_id = ?");
                $stmt->execute(array($email, $pass, $fname, $lname, $userid));

                // Update session variables if needed
                $_SESSION['email'] = $email; // Update the session with new email

                // Check if any rows were actually updated
                if ($stmt->rowCount() > 0) {
                    // Redirect with success message if changes were made
                    header("Location: members.php?do=Edit&userid=$userid&success=1");
                } else {
                    // Redirect with 'no change' message if no rows were affected
                    header("Location: members.php?do=Edit&userid=$userid&success=0");
                }
                exit();
            }

            // Retain posted values on error
            $fname_edit = $fname;
            $lname_edit = $lname;
            $email_edit = $email;
        }

    ?>

        <h1 class="text-center">Edit Member</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Member updated successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 0) {
                echo '<div class="alert alert-warning warning-alert" role="alert">No changes were made. No record updated.</div>';
            }
            ?>

            <form class="form-horizontal" action="?do=Edit&userid=<?php echo $userid ?>" method="POST"
                style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="userid" value="<?php echo $userid ?>">

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputfname" class="col-sm-2 col-form-label">First name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputfname" name="fname" value="<?php echo $fname_edit ?>"
                            autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['fname_err'] ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center">
                    <label for="inputlname" class="col-sm-2 col-form-label">Last name</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="inputlname" name="lname" value="<?php echo $lname_edit ?>"
                            autocomplete="false" required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['lname_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-9">
                        <i class="fa fa-solid fa-eye" id="togglePassword" style="display: none;"></i> <!-- Start hidden -->
                        <input type="hidden" name="old_password" value="<?php echo $password_edit ?>">
                        <input type="password" class="form-control" id="inputPassword" name="new_password" autocomplete="false">
                    </div>
                    <p class="text-danger"><?php echo $form_errors['pass_err'] ?></p>
                </div>

                <div class="row mb-4 align-items-center">
                    <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" id="inputEmail" name="email" value="<?php echo $email_edit ?>"
                            required>
                        <span class="asterisk">*</span>
                    </div>
                    <p class="text-danger"><?php echo $form_errors['email_err'] ?></p>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg" type="submit">Save</button>
                </div>
            </form>
        </div>
    <?php

    } elseif ($do == 'Delete') {
        $user_id = $_GET['userid'];

        $stmt = $con->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->execute(array($user_id));
        header('Location: members.php?do=Manage&success=1');
    ?>

<?php
    } else if ($do == 'Active') {
        $user_id = $_GET['userid'];

        $stmt = $con->prepare("UPDATE users SET reg_stat = 1 WHERE user_id = ?");
        $stmt->execute(array($user_id));
        header('Location: members.php?do=Manage&success=3');
    }
    include $tmp . 'footer.php';
} else {
    header('Location: ../login.php');
    exit();
}
?>