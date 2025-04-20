<?php

// Load the appropriate language file based on the session variable
$langs = $_SESSION['lang'] ?? 'en';
$languageFile = $langs === 'ar' ? 'ar.php' : 'en.php';

include "$languageFile";    

function lang($phrase) {
    global $langArray; // Access the language array
    return $langArray[$phrase] ?? $phrase; // Return the translated phrase or the phrase itself if not found
}
?>
