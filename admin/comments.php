<?php
session_start(); // Start or resume session
$pageTitle = 'Comments'; // Set the page title

if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) { // Check if user is logged in and admin
    include 'init.php'; // Include initialization file (e.g., database connection, header)

    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage'; // Get the 'do' parameter, default to 'Manage'

    if ($do == 'Manage') {
        //Start Manage Page
        $rows_per_page = 4;
        $pagination = setPagination('orders', $rows_per_page);

        // Select all items
        $stmt = $con->prepare(
            "SELECT 
                                    comments.*, 
                                    users.first_name AS first_name, 
                                    users.last_name AS last_name, 
                                    items.item_name AS item_name 
                                FROM 
                                    comments
                                INNER JOIN 
                                    items 
                                ON 
                                    items.item_id = comments.item_id
                                INNER JOIN 
                                    users 
                                ON 
                                    users.user_id = comments.user_id
                                LIMIT " . $pagination['start'] . ", " . $rows_per_page
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(); // Fetch all the users

?>

        <h1 class="text-center">Manage Commnets</h1>
        <div class="container">

            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Comment Deleted Successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div class="alert alert-success success-message" role="alert">Comment Approved Successfully!</div>';
            }
            ?>

            <div class="shadow">
                <div class="table-responsive">
                    <table class="main-table text-center table table-bordered">
                        <thead>
                            <th>#ID</th>
                            <th>Comment</th>
                            <th>Item Name</th>
                            <th>User Name</th>
                            <th>Adding Date</th>
                            <th>Control</th>
                        </thead>

                        <?php
                        // Loop through each user and display their details
                        foreach ($rows as $row):
                            $id = $row['c_id']; // Get the user's ID
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['c_id']) ?></td>
                                <td><?php echo htmlspecialchars($row['comment']) ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']) ?> </td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?php echo htmlspecialchars($row['comment_date']) ?></td>
                                <td>
                                    <a href="?do=Edit&cid=<?php echo $id ?>>" class="edit-btn" title="Edit comment"><i class='fa fa-edit'></i></a>
                                    <a href="?do=Delete&cid=<?php echo $id ?>" class="delete-btn" title="Delete comment"><i class="fa fa-trash"></i></a>
                                    <?php if ($row['status'] == 0): ?>
                                        <a class='approve-btn' title='Approve comment' href='?do=Approve&cid=<?php echo $id ?>'><i class='fa fa-check' aria-hidden='true'></i></a>
                                    <?php endif ?>
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
        </div>

    <?php

    } elseif ($do == 'Edit') { // Start Edit Page

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['cid'])) {
                header('Location: ../login.php');
                exit();
            }

            $cid = intval($_GET['cid']);

            // Fetch the user information from the database
            $stmt = $con->prepare("SELECT * FROM comments WHERE c_id = ?");
            $stmt->execute(array($cid));
            $row = $stmt->fetch();
            $count = $stmt->rowCount();

            if ($count == 0) {
                echo "There is no such ID";
                exit();
            }

            // Store values to populate the form
            $comment_edit = $row['comment'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cid = $_POST['cid'];
            $comment = $_POST['comment'];


            // Prepare the update query
            $stmt = $con->prepare("UPDATE comments SET comment = ? WHERE c_id = ?");
            $stmt->execute(array($comment, $cid));

            // Check if any rows were actually updated
            if ($stmt->rowCount() > 0) {
                // Redirect with success message if changes were made
                header("Location: comments.php?do=Edit&cid=$cid&success=1");
            } else {
                // Redirect with 'no change' message if no rows were affected
                header("Location: comments.php?do=Edit&cid=$cid&success=2");
            }
            exit();


            // Retain posted values in the form on validation error
            $comment_edit = $comment;
        }

    ?>

        <h1 class="text-center">Edit Comment</h1>
        <div class="container">
            <?php
            // Display success message if available
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div class="alert alert-success success-message" role="alert">Comment updated successfully!</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div class="alert alert-warning warning-alert" role="alert">No changes were made. No record updated.</div>';
            } elseif (isset($_GET['success']) && $_GET['success'] == 0) {
                echo '<div class="alert alert-danger" role="alert">This ID is Not Exist.</div>';
            }
            ?>

            <form class="form-horizontal" action="?do=Edit&cid=<?php echo $cid ?>" method="POST"
                style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="cid" value="<?php echo $cid ?>">
                <div class="form-group row mb-4 align-items-center">
                    <div class="col-sm-9 comment-area">
                        <div class="form-floating">
                            <textarea class="form-control textarea" name="comment" placeholder="Leave a comment here" id="floatingTextarea2" style="height: 100px"><?php echo $comment_edit ?></textarea>
                            <label for="floatingTextarea2">Comment</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button class="btn btn-primary btn-lg" type="submit">Save</button>
                </div>
            </form>
        </div>
<?php

    } elseif ($do == 'Delete') {
        //Start Delete Logic
        $c_id = $_GET['cid'];

        $check = checkItem('c_id', 'comments', $c_id);

        //If there such ID Show the Form
        if ($check > 0) {
            $stmt = $con->prepare("DELETE FROM comments WHERE c_id=?");
            $stmt->execute(array($c_id));
            header('Location: comments.php?do=Manage&success=1');
        } else {
            header('Location: comments.php?do=Manage&success=0');
        }
    } elseif ($do == 'Approve') {

        //Start Approve Logic

        $c_id = $_GET['cid'];

        $stmt = $con->prepare("UPDATE comments SET status = 1 WHERE c_id = ?");
        $stmt->execute(array($c_id));
        header('Location: comments.php?do=Manage&success=3');
    }

    include $tmp . 'footer.php'; // Include the footer
} else {
    header("Location: dashboard.php"); // Redirect to dashboard if not logged in
    exit();
}
