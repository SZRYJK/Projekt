<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "Testy";

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    $conn = @mysqli_connect("127.0.0.1", $db_user, $db_pass, $db_name);
}

if (!$conn) {
    die("<div style='background:#18181b;color:#f87171;padding:20px;font-family:sans-serif;text-align:center;'>
            <h2>Błąd połączenia z bazą danych MySQL</h2>
            <p>" . htmlspecialchars(mysqli_connect_error()) . "</p>
            <p>Upewnij się, że serwer MySQL w XAMPP jest uruchomiony i zaimportowano plik <code>baza.sql</code>.</p>
         </div>");
}

header('Content-Type: text/html; charset=utf-8');
mysqli_set_charset($conn, "utf8mb4");
mysqli_query($conn, "SET NAMES utf8mb4");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function is_teacher() {
    return is_logged_in() && !empty($_SESSION['nauczyciel']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

function require_teacher() {
    require_login();
    if (!is_teacher()) {
        header("Location: main.php");
        exit();
    }
}
?>
