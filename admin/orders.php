<?php
session_start(); // Start or resume session
$pageTitle = 'Order Items'; // Set the page title

if (isset($_SESSION['email']) && isset($_SESSION['group_id']) && $_SESSION['group_id'] == 1) { // Check if user is logged in and admin
    include 'init.php'; // Include initialization file (e.g., database connection, header)
    $do = isset($_GET['do']) ? $_GET['do'] : 'Manage'; // Get the 'do' parameter, default to 'Manage'

    // Manage Order Items logic based on 'do' value
    if ($do == 'Manage') {
        // Manage Order Items logic
        $rows_per_page = 4;
        $pagination = setPagination('order_items', $rows_per_page);
        $page_nr = isset($_GET['page-nr']) ? intval($_GET['page-nr']) : 1;

        // Select all order items
        $stmt = $con->prepare("SELECT 
                                    order_items.*, 
                                    orders.order_date AS order_date, 
                                    items.item_name AS item_name,
                                    users.first_name AS first_name,
                                    users.last_name AS last_name
                                FROM 
                                    order_items
                                INNER JOIN 
                                    orders ON orders.order_id = order_items.order_id
                                INNER JOIN 
                                    items ON items.item_id = order_items.item_id
                                INNER JOIN 
                                    users ON users.user_id = orders.user_id
                                LIMIT ?, ?");
        $stmt->bindValue(1, $pagination['start'], PDO::PARAM_INT);
        $stmt->bindValue(2, $rows_per_page, PDO::PARAM_INT);
        $stmt->execute();
        $orderItems = $stmt->fetchAll(); // Fetch all the order items
?>

        <h1 class="text-center">Manage Order Items</h1>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <!-- Order Items Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead>
                                <tr>
                                    <th scope="col">Order Item ID</th>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Customer Name</th>
                                    <th scope="col">Item Name</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total Price (Item)</th>
                                    <th scope="col">Order Date</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr class="table-tr">
                                        <td>#<?php echo htmlspecialchars($item['order_item_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['status']); ?></td>
                                        <td class="action-btns">
                                            <?php if ($item['status'] == 'Pending'): ?>
                                                <a href="?do=Approve&orderitemid=<?php echo $item['order_item_id']; ?>" class="approve-btn" title="Approve order"><i class='fa fa-check' aria-hidden='true'></i></a>
                                            <?php endif; ?>
                                            <a href="?do=Edit&orderitemid=<?php echo $item['order_item_id']; ?>" class="edit-btn" title="Edit Order Item"><i class='fa fa-edit'></i></a>
                                            <a href="?do=Delete&orderitemid=<?php echo $item['order_item_id']; ?>" class="delete-btn" title="Delete Order Item"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation example">
                        <ul class="pagination pagination-cat">
                            <li class="page-item <?= ($_GET['page-nr'] ?? 1) <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page-nr=1">First</a>
                            </li>
                            <li class="page-item <?= ($_GET['page-nr'] ?? 1) <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page-nr=<?= max(1, ($_GET['page-nr'] ?? 1) - 1) ?>">Previous</a>
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
                                <a class="page-link" href="?page-nr=<?= min($pagination['pages'], ($_GET['page-nr'] ?? 1) + 1) ?>">Next</a>
                            </li>
                            <li class="page-item <?= ($_GET['page-nr'] ?? 1) >= $pagination['pages'] ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page-nr=<?php echo $pagination['pages'] ?>">Last</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

    <?php
    } elseif ($do == 'Edit') {
        $quantity_error = NULL;

        $orderitemid_edit = ''; // Initialize with an empty string
        $customer_name_edit = ''; // Initialize with an empty string
        $item_name_edit = ''; // Initialize with an empty string
        $quantity_edit = '';
        $status_edit = '';

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['orderitemid']) || !is_numeric($_GET['orderitemid'])) {
                // Redirect if order item ID is invalid
                header('Location: orders.php?do=Manage&msg=invalid');
                exit();
            }

            $orderitemid_edit = intval($_GET['orderitemid']);
            $stmt = $con->prepare("SELECT 
                                        order_items.*, 
                                        items.item_id AS item_id,
                                        items.quantity AS o_quantity,
                                        items.price AS o_price,
                                        items.item_name AS item_name, 
                                        users.first_name AS first_name,
                                        users.last_name AS last_name 
                                    FROM 
                                        order_items 
                                    INNER JOIN 
                                        orders ON orders.order_id = order_items.order_id 
                                    INNER JOIN 
                                        items ON items.item_id = order_items.item_id 
                                    INNER JOIN 
                                        users ON users.user_id = orders.user_id 
                                    WHERE 
                                        order_items.order_item_id = ?");
            $stmt->execute([$orderitemid_edit]);
            $row = $stmt->fetch();

            if (!$row) {
                // Provide feedback if no order is found
                header('Location: orders.php?do=Manage&msg=no_order');
                exit();
            }

            // Fill form values
            $orderitemid_edit = $row['order_item_id'];
            $item_id_edit = $row['item_id'];
            $item_quantity_edit = $row['o_quantity'];
            $item_price_edit = $row['o_price'];
            // $customer_fname_edit = $row['first_name'];
            // $customer_lname_edit = $row['last_name'];
            $customer_name_edit = $row['first_name'] . ' ' . $row['last_name'];
            $item_name_edit = $row['item_name'];
            $quantity_edit = $row['quantity'];
            $status_edit = $row['status'];
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get posted data
            $orderitemid = $_POST['orderitemid'];
            $item_quantity = $_POST['itemQuantity'];
            $item_price = $_POST['itemPrice'];
            $itemid = intval($_POST['itemid']);
            $quantity = intval($_POST['quantity']);
            $status = $_POST['status'];

            if ($quantity < 1) {
                $quantity_error = 'Quantity must be at least 1';
            } elseif ($item_quantity < $quantity) {
                $quantity_error = 'Not enogh Quantity';
            }

            if ($quantity_error == NULL) {
                // Calculate the new total price based on quantity and item price
                $total_price = $quantity * $item_price;

                // Prepare statement for updating order_items
                $stmt = $con->prepare("UPDATE order_items SET price = ?, quantity = ?, status = ? WHERE order_item_id = ?");

                if ($stmt->execute([$total_price, $quantity, $status, $orderitemid])) {
                    // Update item quantity in items table
                    $new_quantity = $item_quantity - $quantity; // Calculate the new stock quantity

                    // Ensure the new quantity does not go below zero
                    if ($new_quantity >= 0) {
                        $stmt_4 = $con->prepare("UPDATE items SET quantity = ? WHERE item_id = ?");
                        $stmt_4->execute([$new_quantity, $itemid]);
                    } else {
                        // Optionally handle the case where stock would be negative
                        echo "Insufficient stock available after update.";
                    }

                    // Redirect with success message
                    header("Location: orders.php?do=Manage&msg=updated");
                    exit();
                } else {
                    // Handle update failure
                    echo "Failed to update order item.";
                }
            }
        }
    ?>
        <!-- Edit Order Item Form -->
        <h1 class="text-center">Edit Order Item</h1>
        <div class="container">
            <form class="form-horizontal" action="" method="POST" style="max-width: 600px; margin: 0 auto;">
                <input type="hidden" name="orderitemid" value="<?php echo htmlspecialchars($orderitemid_edit); ?>">
                <input type="hidden" name="itemid" value="<?php echo htmlspecialchars($item_id_edit); ?>">
                <input type="hidden" name="itemQuantity" value="<?php echo htmlspecialchars($item_quantity_edit); ?>">
                <input type="hidden" name="itemPrice" value="<?php echo htmlspecialchars($item_price_edit); ?>">

                <div class="form-group row mb-4 align-items-center order_group">
                    <label for="customer_name" class="col-sm-4 col-form-label">Customer Name:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="customer_name" id="customer_name" value="<?php echo htmlspecialchars($customer_name_edit); ?>" readonly>
                    </div>
                </div>

                <div class="form-group row mb-4 align-items-center order_group">
                    <label for="item_name" class="col-sm-4 col-form-label">Item Name:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="item_name" id="item_name" value="<?php echo htmlspecialchars($item_name_edit); ?>" readonly>
                    </div>
                </div>

                <div class="form-group row mb-4 align-items-center order_group">
                    <label for="quantity" class="col-sm-4 col-form-label">Quantity:</label>
                    <div class="col-sm-8">
                        <input type="number" class="form-control" name="quantity" id="quantity" value="<?php echo htmlspecialchars($quantity_edit); ?>" required>
                    </div>
                    <p class="text-danger quantity-err"><?php echo $quantity_error ?></p>
                </div>

                <div class="form-group row mb-4 align-items-center order_group">
                    <label for="status" class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <select class="form-select" name="status" id="status" required>
                            <option value="Pending" <?= ($status_edit == 0) ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?= ($status_edit == 1) ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>

<?php
    } elseif ($do == 'Delete') {
        // Delete order item logic
        $orderitemid_delete = isset($_GET['orderitemid']) ? intval($_GET['orderitemid']) : 0;
        $stmt = $con->prepare("DELETE FROM order_items WHERE order_item_id = ?");
        $stmt->execute([$orderitemid_delete]);

        if ($stmt->rowCount() > 0) {
            header("Location: orders.php?do=Manage&msg=deleted");
            exit();
        } else {
            echo "Failed to delete the order item or it does not exist.";
        }
    } elseif ($do == 'Approve') {
        //Start Approve Logic

        $orderitemid_approve = isset($_GET['orderitemid']) ? intval($_GET['orderitemid']) : 0;

        $stmt = $con->prepare("UPDATE order_items SET status = 'Approved' WHERE order_item_id = ?");
        $stmt->execute([$orderitemid_approve]);
        if ($stmt->rowCount() > 0) {
            // Fetch the order ID associated with the approved order item
            $stmt_order = $con->prepare("SELECT order_id FROM order_items WHERE order_item_id = ?");
            $stmt_order->execute([$orderitemid_approve]);
            $order_id = $stmt_order->fetchColumn();

            // Check if all items in this order are approved
            $stmt_check = $con->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND status != 'Approved'");
            $stmt_check->execute([$order_id]);
            $not_approved_count = $stmt_check->fetchColumn();

            // If no items are left not approved, mark the order as completed
            if ($not_approved_count == 0) {
                $stmt_complete = $con->prepare("UPDATE orders SET payment_status = 'Completed' WHERE order_id = ?");
                $stmt_complete->execute([$order_id]);
            }

            // Redirect with a success message
            header("Location: orders.php?do=Manage&msg=approve");
            exit();
        } else {
            echo "Failed to approve the order item or it does not exist.";
        }
    }

    include $tmp . 'footer.php'; // Include footer
} else {
    header('Location: ../login.php'); // Redirect if not logged in
    exit();
}
?>