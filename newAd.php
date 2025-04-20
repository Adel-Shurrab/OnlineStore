<?php
ob_start();
session_start();
$pageTitle = 'New Ad';
include 'init.php';
$do = isset($_GET['do']) ? $_GET['do'] : 'Manage';
$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header("Location: login.php");
    exit;
} elseif (isset($user_id) && checkUserStatus($_SESSION['email']) == 1) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['non-active'])];
    header("Location: index.php");
    exit;
}

// $alert_message = '';
$form_errors = array(
    'name_err'    => '',
    'desc_err'    => '',
    'price_err'   => '',
    'country_err' => '',
    'status_err'  => '',
    'member_err'  => '',
    'quantity_err' => '',
    'cat_err'     => ''
);

if ($do == 'Add') {
    // If form is submitted via POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Gather form input data
        $name      = $_POST['item_name'];
        $desc      = $_POST['description'];
        $price     = $_POST['price'];
        $quantity  = $_POST['quantity'];
        $country   = $_POST['country_made'];
        $status    = $_POST['status'];
        $category  = $_POST['category'];

        // Validate item inputs and store errors if any 

        // Check if product exists in the database
        $stmt = $con->prepare("SELECT * FROM items WHERE item_name = ? AND user_id = ?");
        $stmt->execute([$name, $user_id]);
        if ($stmt->rowCount() > 0) {
            $form_errors['name_err'] = lang($langArray['Product already exists']);
        }

        if ($price < 0.99) $form_errors['price_err'] = lang($langArray['Price cannot be less than 0.99']);
        if ($quantity < 1) $form_errors['quantity_err'] = lang($langArray['Quantity cannot be less than 1']);
        if ($status == 0) $form_errors['status_err'] = lang($langArray['Select the item status']);
        if ($category == 0) $form_errors['cat_err'] = lang($langArray['Select a category']);

        // Handle the images upload
        $file_path = [];
        $uploadPath = $uploads . 'Products/';
        // Process Primary Image
        $result = validateAndUploadImage($_FILES['primary_image'], ['jpg', 'jpeg', 'png'], 5144576, $uploadPath, $langArray);
        if (isset($result['error'])) {
            setErrorAndRedirect($result['error'], $langArray, 'newAd.php?do=Add');
        } else {
            $filePaths[] = $result['path'];
        }

        // Process Additional Images
        foreach (['secondary_image1', 'secondary_image2'] as $imageField) {
            if (!empty($_FILES[$imageField]['name'])) {
                $result = validateAndUploadImage($_FILES[$imageField], ['jpg', 'jpeg', 'png'], 5144576, $uploadPath, $langArray);
                if (isset($result['error'])) {
                    setErrorAndRedirect($result['error'], $langArray, 'newAd.php?do=Add');
                } else {
                    $filePaths[] = $result['path'];
                }
            }
        }

        // Convert file paths to a single string
        $filePathsString = implode(",", $filePaths);

        // If no validation errors, insert the new item into the database
        if (empty(array_filter($form_errors))) {
            $stmt = $con->prepare("INSERT INTO items(item_name, description, price, quantity, add_date, country_made, status, cat_id, user_id, image) VALUES (?, ?, ?, ?, now(), ?, ?, ?, ?, ?)");
            $stmt->execute(array($name, $desc, $price, $quantity, $country, $status, $category, $user_id, $filePathsString));

            $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['Product added successfully'])];
        }
    }

?>
    <div class="container-xl px-4 mt-4">
        <h1 style="text-align: center;"><?php echo lang($langArray['Add New Product']); ?></h1>
        <?php
        if (isset($_SESSION['alert_message'])) {
            echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
            echo $_SESSION['alert_message']['message'];
            echo '</div>';
            unset($_SESSION['alert_message']);
        }
        ?>
        <form action="newAd.php?do=Add" method="post" enctype="multipart/form-data" class="row">
            <div class="col-xl-4">
                <!-- Profile picture card-->
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header"><?php echo lang($langArray['Product Picture']); ?></div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle-item mb-2" src="layout/images/default-item.png" alt="" />
                        <div class="small font-italic text-muted mb-4">
                            <?php echo lang($langArray['JPG or PNG, max size 5 MB']); ?>
                        </div>
                        <!-- Primary Image -->
                        <div class="input-group mb-3">
                            <input type="file" class="form-control" id="inputGroupFile01" name="primary_image" accept=".jpg, .jpeg, .png" required />
                            <label class="input-group-text" for="inputGroupFile01"><?php echo lang($langArray['Primary Image']); ?></label>
                        </div>

                        <!-- Additional Images -->
                        <div class="input-group mb-3">
                            <input type="file" class="form-control" id="inputGroupFile02" name="secondary_image1" accept=".jpg, .jpeg, .png" />
                            <label class="input-group-text" for="inputGroupFile02"><?php echo lang($langArray['Additional Image 1']); ?></label>
                        </div>
                        <div class="input-group mb-3">
                            <input type="file" class="form-control" id="inputGroupFile03" name="secondary_image2" accept=".jpg, .jpeg, .png" />
                            <label class="input-group-text" for="inputGroupFile03"><?php echo lang($langArray['Additional Image 2']); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><?php echo lang($langArray['Product Details']); ?></div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="productName" class="form-label"><?php echo lang($langArray['Product Name']); ?></label>
                            <div class="input-container">
                                <input type="text" class="form-control" id="productName" name="item_name" placeholder="<?php echo lang($langArray['Name of The Item']); ?>" required>
                                <span class="asterisk">*</span>
                            </div>
                            <p class="text-danger"><?php echo $form_errors['name_err']; ?></p>
                        </div>

                        <div class="mb-3">
                            <label for="productPrice" class="form-label"><?php echo lang($langArray['Selling Price']); ?></label>
                            <div class="input-container">
                                <input type="number" class="form-control" id="productPrice" name="price" placeholder="<?php echo lang($langArray['Price of The Item']); ?>" step="0.01" required>
                                <span class="asterisk">*</span>
                            </div>
                            <p class="text-danger"><?php echo $form_errors['price_err']; ?></p>
                        </div>

                        <div class="mb-3">
                            <label for="countryMade" class="form-label"><?php echo lang($langArray['Country Made']); ?></label>
                            <div class="input-container">
                                <input type="text" class="form-control" id="countryMade" placeholder="<?php echo lang($langArray['Country of Origin']); ?>" name="country_made" required>
                                <span class="asterisk">*</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="productQuantity" class="form-label"><?php echo lang($langArray['Quantity']); ?></label>
                            <div class="input-container">
                                <input type="number" class="form-control" id="productQuantity" name="quantity" placeholder="<?php echo lang($langArray['Quantity of The Item']); ?>" step="1" required>
                            </div>
                            <p class="text-danger"><?php echo $form_errors['quantity_err']; ?></p>
                        </div>

                        <div class="mb-3">
                            <label for="productStatus" class="form-label"><?php echo lang($langArray['Product Status']); ?></label>
                            <select class="form-select" id="productStatus" name="status" required>
                                <option value=""><?php echo lang($langArray['Select Product Status']); ?></option>
                                <option value="new"><?php echo lang($langArray['New']); ?></option>
                                <option value="like new"><?php echo lang($langArray['Like New']); ?></option>
                                <option value="used"><?php echo lang($langArray['Used']); ?></option>
                                <option value="very old"><?php echo lang($langArray['Very Old']); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="productCategory" class="form-label"><?php echo lang($langArray['Category']); ?></label>
                            <select class="form-select" id="productCategory" name="category" required>
                                <option value="0"><?php echo lang($langArray['Select Category']); ?></option>
                                <?php
                                $stmt = $con->prepare("SELECT * FROM categories");
                                $stmt->execute();
                                $categories = $stmt->fetchAll();
                                foreach ($categories as $cat) {
                                    echo "<option value='" . $cat['cat_id'] . "' " . ($category == $cat['cat_id'] ? 'selected' : '') . "> " . htmlspecialchars(lang($cat['cat_name'])) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="productDescription" class="form-label"><?php echo lang($langArray['Description']); ?></label>
                            <div class="input-container">
                                <textarea class="form-control" id="productDescription" name="description" placeholder="<?php echo lang($langArray['Description of The Item']); ?>" rows="3" required></textarea>
                                <span class="asterisk">*</span>
                            </div>
                            <p class="text-danger"><?php echo $form_errors['desc_err']; ?></p>
                        </div>

                        <button class="btn btn-primary btn-lg btn-item" type="submit"><?php echo lang($langArray['Add Item']); ?></button>

                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
} elseif ($do == 'Edit') {
    if (isset($_GET['item_id'])) {
        $item_id = intval($_GET['item_id']);

        // Fetch the item from the database
        $stmt = $con->prepare("SELECT * FROM items WHERE item_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $user_item = $stmt->fetch();

        // Check if the item exists
        if (!$user_item) {
            echo "Error: Item not found.";
            exit();
        }
    } else {
        echo "Error: Item ID not provided.";
        exit();
    }

    if (isset($_POST['update'])) {
        // Gather form input data
        $name      = $_POST['item_name'];
        $desc      = $_POST['description'];
        $price     = $_POST['price'];
        $quantity  = $_POST['quantity'];
        $country   = $_POST['country_made'];
        $status    = $_POST['status'];
        $category  = $_POST['category'];

        // Validate item inputs and store errors if any 
        // Check if product exists in the database, excluding current item
        $stmt = $con->prepare("SELECT * FROM items WHERE item_name = ? AND user_id = ? AND item_id != ?");
        $stmt->execute([$name, $user_id, $item_id]);
        if ($stmt->rowCount() > 0) {
            $form_errors['name_err'] = lang($langArray['Product already exists']);
        }

        if ($price < 0.99) $form_errors['price_err'] = lang($langArray['Price cannot be less than 0.99']);
        if ($quantity < 1) $form_errors['quantity_err'] = lang($langArray['Quantity cannot be less than 1']);
        if ($status == 0) $form_errors['status_err'] = lang($langArray['Select the item status']);
        if ($category == 0) $form_errors['cat_err'] = lang($langArray['Select a category']);

        // If no validation errors, update the item in the database
        if (empty(array_filter($form_errors))) {
            $stmt = $con->prepare("UPDATE items SET item_name = ?, description = ?, price = ?, quantity = ?, country_made = ?, status = ?, cat_id = ?, approve = ? WHERE item_id = ? AND user_id = ?");
            $stmt->execute([$name, $desc, $price, $quantity, $country, $status, $category, 0, $item_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['Product Updated successfully'])];
            } else {
                $_SESSION['alert_message'] = ['type' => 'warning', 'message' => lang($langArray['No changes were made'])];
            }
        }
    }

    if (isset($_POST['update_img'])) {
        // Handle the images upload
        $newFilePaths = [];
        $uploadPath = $uploads . 'Products/';

        // Fetch current images from the database
        $stmt = $con->prepare("SELECT image FROM items WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        $currentImages = $stmt->fetchColumn();
        $currentImagesArray = $currentImages ? explode(",", $currentImages) : [];

        // Process Primary Image
        if (!empty($_FILES['primary_image']['name'])) {
            $result = validateAndUploadImage($_FILES['primary_image'], ['jpg', 'jpeg', 'png'], 5144576, $uploadPath, $langArray);
            if (isset($result['error'])) {
                setErrorAndRedirect($result['error'], $langArray, 'newAd.php?do=Edit&item_id=' . $item_id);
            } else {
                $newFilePaths[] = $result['path'];
            }
        } elseif (!empty($currentImagesArray[0])) {
            $newFilePaths[] = $currentImagesArray[0];
        }

        // Process Additional Images
        foreach (['secondary_image1', 'secondary_image2'] as $index => $imageField) {
            if (!empty($_FILES[$imageField]['name'])) {
                $result = validateAndUploadImage($_FILES[$imageField], ['jpg', 'jpeg', 'png'], 5144576, $uploadPath, $langArray);
                if (isset($result['error'])) {
                    setErrorAndRedirect($result['error'], $langArray, 'newAd.php?do=Edit&item_id=' . $item_id);
                } else {
                    $newFilePaths[] = $result['path'];
                }
            } elseif (!empty($currentImagesArray[$index + 1])) {
                $newFilePaths[] = $currentImagesArray[$index + 1];
            }
        }

        // Clean up old images
        foreach ($currentImagesArray as $oldImage) {
            if (!empty($oldImage) && !in_array($oldImage, $newFilePaths) && file_exists($oldImage) && $oldImage != 'layout/images/default-item.png') {
                unlink($oldImage);
            }
        }

        // Convert file paths to a single string
        $filePathsString = implode(",", array_filter($newFilePaths));

        $stmt = $con->prepare("UPDATE items SET image = ? WHERE item_id = ? AND user_id = ?");
        $stmt->execute([$filePathsString, $item_id, $user_id]);
        $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['success_uploads'])];

        header("Location: newAd.php?do=Edit&item_id=$item_id");
        exit;
    }

?>
    <div class="container-xl px-4 mt-4">
        <h1 style="text-align: center;"><?php echo lang($langArray['Edit Product']); ?></h1>
        <?php
        if (isset($_SESSION['alert_message'])) {
            echo '<div class="alert alert-' . $_SESSION['alert_message']['type'] . '">';
            echo $_SESSION['alert_message']['message'];
            echo '</div>';
            unset($_SESSION['alert_message']);
        }
        ?>
        <div class="row">
            <div class="col-xl-4">
                <!-- Profile picture card-->
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header"><?php echo lang($langArray['Product Picture']); ?></div>
                    <div class="card-body text-center">
                        <img class="img-account-profile rounded-circle-item mb-2" src="<?php echo displayImage($user_item['item_id'], 0); ?>" alt="" />
                        <div class="small font-italic text-muted mb-4">
                            <?php echo lang($langArray['JPG or PNG, max size 5 MB']); ?>
                        </div>
                        <form action="" method="post" enctype="multipart/form-data">
                            <!-- Primary Image -->
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" id="inputGroupFile01" name="primary_image" accept=".jpg, .jpeg, .png" />
                                <label class="input-group-text" for="inputGroupFile01"><?php echo lang($langArray['Primary Image']); ?></label>
                            </div>

                            <!-- Additional Images -->
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" id="inputGroupFile02" name="secondary_image1" accept=".jpg, .jpeg, .png" />
                                <label class="input-group-text" for="inputGroupFile02"><?php echo lang($langArray['Additional Image 1']); ?></label>
                            </div>
                            <div class="input-group mb-3">
                                <input type="file" class="form-control" id="inputGroupFile03" name="secondary_image2" accept=".jpg, .jpeg, .png" />
                                <label class="input-group-text" for="inputGroupFile03"><?php echo lang($langArray['Additional Image 2']); ?></label>
                            </div>
                            <button type="submit" name="update_img" class="btn btn-primary">Change item images</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header"><?php echo lang($langArray['Product Details']); ?></div>
                    <div class="card-body">
                        <form action="newAd.php?do=Edit&item_id=<?php echo $_GET['item_id'] ?>" method="post">
                            <div class="mb-3">
                                <label for="productName" class="form-label"><?php echo lang($langArray['Product Name']); ?></label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="productName" name="item_name" placeholder="<?php echo lang($langArray['Name of The Item']); ?>" value="<?php echo $user_item['item_name'] ?>" required>
                                    <span class="asterisk">*</span>
                                </div>
                                <p class="text-danger"><?php echo $form_errors['name_err']; ?></p>
                            </div>

                            <div class="mb-3">
                                <label for="productPrice" class="form-label"><?php echo lang($langArray['Selling Price']); ?></label>
                                <div class="input-container">
                                    <input type="number" class="form-control" id="productPrice" name="price" placeholder="<?php echo lang($langArray['Price of The Item']); ?>" step="0.01" value="<?php echo $user_item['price'] ?>" required>
                                    <span class="asterisk">*</span>
                                </div>
                                <p class="text-danger"><?php echo $form_errors['price_err']; ?></p>
                            </div>

                            <div class="mb-3">
                                <label for="countryMade" class="form-label"><?php echo lang($langArray['Country Made']); ?></label>
                                <div class="input-container">
                                    <input type="text" class="form-control" id="countryMade" placeholder="<?php echo lang($langArray['Country of Origin']); ?>" name="country_made" value="<?php echo $user_item['country_made'] ?>" required>
                                    <span class="asterisk">*</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="productQuantity" class="form-label"><?php echo lang($langArray['Quantity']); ?></label>
                                <div class="input-container">
                                    <input type="number" class="form-control" id="productQuantity" name="quantity" placeholder="<?php echo lang($langArray['Quantity of The Item']); ?>" step="1" value="<?php echo $user_item['quantity'] ?>" required>
                                </div>
                                <p class="text-danger"><?php echo $form_errors['quantity_err']; ?></p>
                            </div>

                            <div class="mb-3">
                                <label for="productStatus" class="form-label"><?php echo lang($langArray['Product Status']); ?></label>
                                <select class="form-select" id="productStatus" name="status" required>
                                    <option value=""><?php echo lang($langArray['Select Product Status']); ?></option>
                                    <option value="new" <?php echo ($user_item['status'] == "new") ? 'selected' : ''; ?>>
                                        <?php echo lang($langArray['New']); ?>
                                    </option>
                                    <option value="like new" <?php echo ($user_item['status'] == "like new") ? 'selected' : ''; ?>>
                                        <?php echo lang($langArray['Like New']); ?>
                                    </option>
                                    <option value="used" <?php echo ($user_item['status'] == "used") ? 'selected' : ''; ?>>
                                        <?php echo lang($langArray['Used']); ?>
                                    </option>
                                    <option value="very old" <?php echo ($user_item['status'] == "very old") ? 'selected' : ''; ?>>
                                        <?php echo lang($langArray['Very Old']); ?>
                                    </option>
                                </select>
                            </div>


                            <div class="mb-3">
                                <label for="productCategory" class="form-label"><?php echo lang($langArray['Category']); ?></label>
                                <select class="form-select" id="productCategory" name="category" required>
                                    <option value="0"><?php echo lang($langArray['Select Category']); ?></option>
                                    <?php
                                    $stmt = $con->prepare("SELECT cat_id, cat_name FROM categories");
                                    $stmt->execute();
                                    $categories = $stmt->fetchAll();

                                    foreach ($categories as $cat) {
                                        $selected = ($cat['cat_id'] == $user_item['cat_id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($cat['cat_id']) . "' $selected>" . htmlspecialchars(lang($cat['cat_name'])) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="mb-3">
                                <label for="productDescription" class="form-label"><?php echo lang($langArray['Description']); ?></label>
                                <div class="input-container">
                                    <textarea class="form-control" id="productDescription" name="description" placeholder="<?php echo lang($langArray['Description of The Item']); ?>" rows="3" required><?php echo $user_item['description'] ?></textarea>
                                    <span class="asterisk">*</span>
                                </div>
                                <p class="text-danger"><?php echo $form_errors['desc_err']; ?></p>
                            </div>

                            <button class="btn btn-primary btn-lg btn-item" type="submit" name="update"><?php echo lang($langArray['Edit Item']); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
} elseif ($do = 'Delete') {

    if (isset($_GET['item_id'])) {
        $item_id = intval($_GET['item_id']);

        // Fetch the item from the database
        $stmt = $con->prepare("SELECT * FROM items WHERE item_id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $user_item = $stmt->fetch();

        // Check if the item exists
        if (!$user_item) {
            echo "Error: Item not found.";
            exit();
        }
    } else {
        echo "Error: Item ID not provided.";
        exit();
    }

    $stmt = $con->prepare("DELETE FROM items WHERE item_id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // If rows were updated, show success message
        $_SESSION['alert_message'] = ['type' => 'success', 'message' => lang($langArray['success_delete'])];
        header("Location: profile-settings.php?do=info");
    } else {
        $_SESSION['alert_message'] = ['type' => 'danger', 'message' => lang($langArray['delete_item_failed'])];
        header("Location: profile-settings.php?do=info");
    }

    exit;
}
include $tmp . 'footer.php';
ob_end_flush();
?>