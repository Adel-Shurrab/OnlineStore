<?php
function ValidateUserName($username)
{
   

    // Validate the username format
    if (!preg_match('/^[a-zA-Z]\w{2,8}[^_]$/', $username)) {
        return 'Username must start with a letter, 3-9 characters, and not end with an underscore.';
    } 

    // No errors, return false
    return false;
}


function ValidateEmail($email)
{
    $check = checkItem("email", "users", $email); // Use the modified function

    if ($check > 0) {
        return 'Email already exists.'; // Return error message if email exists
    }

    return false;
}


function ValidateNameByID($from, $selectname, $selectid, $valuename, $valueid)
{
    include 'connect.php';

    // Check if the username already exists, but ignore the current user's username
    $stmt = $con->prepare("SELECT * FROM $from WHERE $selectname = ? AND $selectid != ?");
    $stmt->execute(array($valuename, $valueid));
    $count = $stmt->rowCount();

    if ($count > 0) {
        return 'already exist.';
    }

    // No errors, return false
    return false;
}

function ValidatePassword($password)
{
    if (preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^\w]).{8,}$/', $password) == false) {
        return 'Password must be 8+ characters, include uppercase, lowercase, number, and special character.';
    }

    return false;
}

function ValidateEmailByID($email, $userid)
{
    include 'connect.php';

    $stmt = $con->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute(array($email, $userid));
    $count = $stmt->rowCount();

    if ($count > 0) {
        return 'Email already exists.';
    }

    return false;
}

function ValidateName($name)
{
    if (!preg_match('/^[a-zA-Z ]{1,50}$/', $name)) {
        return 'Fullname must be up to 12 alphabetic characters.';
    }
    return false;
}
