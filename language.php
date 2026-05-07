<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language is English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Change language if the user clicks the language switcher
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'si', 'ta'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load the correct language dictionary
$lang_file = 'languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // If it can't find the dictionary, just fail silently to prevent crashing
    $lang = array(
        "app_name" => "Life Drop",
        "email" => "Email",
        "password" => "Password",
        "login_btn" => "Login",
        "login_title" => "Welcome",
        "no_account" => "No account?",
        "register_here" => "Register"
    );
}
?>
