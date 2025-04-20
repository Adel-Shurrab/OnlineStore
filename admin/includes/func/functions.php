<?php

function getTitle()
{
    global $pageTitle;
    if (isset($pageTitle)) {
        echo $pageTitle;
    } else {
        echo 'Default';
    }
}

//$select = The Item to Select [user, item, category].
//$from = The table to select from.
//$value = The value of select [Osama, Box, Electronics].
function checkItem($select, $from, $value)
{
    global $con;

    $stmt = $con->prepare("SELECT $select FROM $from WHERE $select = ?");
    $stmt->execute(array($value));
    return $stmt->rowCount(); // Return the count instead of echoing it
}


function contItems($item, $from)
{
    global $con;
    $stmt2 = $con->prepare("SELECT COUNT($item) FROM $from");
    $stmt2->execute();
    echo $stmt2->fetchColumn();
}

function getLatest($select, $table, $order, $limit = 5)
{
    global $con;

    $stmt = $con->prepare("SELECT $select FROM $table ORDER BY $order DESC LIMIT $limit");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return $rows;
}


function setPagination($table, $rows_per_page)
{
    global $con;

    $start = 0;
    $page = 0;

    //  Get the total nr of rows.
    $records = $con->prepare("SELECT * FROM $table");
    $records->execute();
    $nr_of_rows = $records->rowCount();

    //  Calculate the nr of Pages.
    $pages = ceil($nr_of_rows / $rows_per_page);

    //  if the user clicks on the pagination buttons we set a new starting point.
    if (isset($_GET['page-nr'])) {
        $page = $_GET['page-nr'] - 1;
        $start = $page * $rows_per_page;
    }

    return [
        'nr_of_rows' => $nr_of_rows,
        'pages' => $pages,
        'page' => $page,
        'start' => $start
    ];
}

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
