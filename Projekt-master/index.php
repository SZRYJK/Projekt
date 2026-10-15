<?php
require_once __DIR__ . '/db.php';

if (is_logged_in()) {
    header("Location: main.php");
    exit();
}

$error_msg = "";
$success_msg = "";
$active_tab = "login";

if (isset($_GET['error'])) {
    if ($_GET['error'] === '1') {
        $error_msg = "Użytkownik o podanym loginie nie istnieje.";
    } elseif ($_GET['error'] === '2') {
        $error_msg = "Nieprawidłowe hasło. Spróbuj ponownie.";
    } elseif ($_GET['error'] === '0') {
        $error_msg = "Wypełnij wszystkie wymagane pola.";
    }
}

if (isset($_GET['tab']) && $_GET['tab'] === 'register') {
    $active_tab = "register";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $login = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($login) || empty($password)) {
            $error_msg = "Podaj login i hasło.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, login, password, nauczyciel FROM uzytkownicy WHERE login = ?");
            mysqli_stmt_bind_param($stmt, "s", $login);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($res)) {
                $is_valid = false;
                if ($password === $user['password']) {
                    $is_valid = true;
                } elseif (password_verify($password, $user['password'])) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['login'] = $user['login'];
                    $_SESSION['nauczyciel'] = (int)$user['nauczyciel'];

                    header("Location: main.php");
                    exit();
                } else {
                    $error_msg = "Nieprawidłowe hasło.";
                }
            } else {
                $error_msg = "Użytkownik o loginie \"$login\" nie istnieje.";
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'register') {
        $active_tab = "register";
        $login = trim($_POST['reg_login'] ?? '');
        $password = trim($_POST['reg_password'] ?? '');
        $role = (int)($_POST['reg_role'] ?? 0);

        if (empty($login) || empty($password)) {
            $error_msg = "Wszystkie pola rejestracji są wymagane.";
        } elseif (mb_strlen($login) < 3) {
            $error_msg = "Login musi mieć co najmniej 3 znaki.";
        } elseif (mb_strlen($password) < 4) {
            $error_msg = "Hasło musi mieć co najmniej 4 znaki.";
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM uzytkownicy WHERE login = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $login);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_res) > 0) {
                $error_msg = "Użytkownik o takim loginie już istnieje. Wybierz inny.";
            } else {
                $insert_stmt = mysqli_prepare($conn, "INSERT INTO uzytkownicy (login, password, nauczyciel) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($insert_stmt, "ssi", $login, $password, $role);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['login'] = $login;
                    $_SESSION['nauczyciel'] = $role;

                    header("Location: main.php");
                    exit();
                } else {
                    $error_msg = "Błąd rejestracji w bazie danych.";
                }
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestHub - Logowanie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
        <div class="nav-brand">
            <span class="brand-text">TestHub</span>
        </div>
        <div class="nav-actions">
            <button type="button" class="tab-pill <?= $active_tab === 'login' ? 'active' : '' ?>" id="navTabLogin" onclick="switchTab('login')">Zaloguj się</button>
            <button type="button" class="tab-pill <?= $active_tab === 'register' ? 'active' : '' ?>" id="navTabRegister" onclick="switchTab('register')">Zarejestruj się</button>
        </div>
    </nav>

    <main>
        <div id="loginModal">
            <header class="auth-intro">
                <h1>Testy szkolne</h1>
                <p>Zaloguj się, żeby rozwiązać test lub dodać własny.</p>
            </header>
            <div class="auth-tabs">
                <button type="button" class="auth-tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" id="tabBtnLogin" onclick="switchTab('login')">Logowanie</button>
                <button type="button" class="auth-tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" id="tabBtnRegister" onclick="switchTab('register')">Rejestracja</button>
            </div>

            <form id="loginForm" class="auth-form <?= $active_tab === 'login' ? 'active' : 'hidden' ?>" action="index.php" method="post">
                <input type="hidden" name="action" value="login">
                
                <label for="login-txt">Login:</label>
                <input type="text" name="login" id="login-txt" autocomplete="username" placeholder="Wpisz swój login" required>
                
                <label for="password-txt">Hasło:</label>
                <input type="password" name="password" id="password-txt" autocomplete="current-password" placeholder="Wpisz hasło" required>
                
                <button type="submit" id="login-btn">
                    <span class="btn-text">Zaloguj się</span>
                </button>
                
                <div class="auth-hints">
                    <p class="hint-title">Konta demonstracyjne:</p>
                    <p class="hint-account"><strong>Nauczyciel:</strong> Ryszard / <code>12344321</code></p>
                    <p class="hint-account"><strong>Uczeń:</strong> Klaudia / <code>zaq1@WSX</code> lub Aleks / <code>12345678</code></p>
                </div>
            </form>

            <form id="registerForm" class="auth-form <?= $active_tab === 'register' ? 'active' : 'hidden' ?>" action="index.php" method="post">
                <input type="hidden" name="action" value="register">
                
                <label for="reg-login-txt">Login:</label>
                <input type="text" name="reg_login" id="reg-login-txt" autocomplete="username" minlength="3" placeholder="Wybierz nazwę użytkownika" required>
                
                <label for="reg-password-txt">Hasło:</label>
                <input type="password" name="reg_password" id="reg-password-txt" autocomplete="new-password" minlength="4" placeholder="Minimum 4 znaki" required>
                
                <label for="reg-role-select">Typ konta:</label>
                <select name="reg_role" id="reg-role-select" class="custom-select">
                    <option value="0">Uczeń (rozwiązywanie testów)</option>
                    <option value="1">Nauczyciel (tworzenie i podgląd testów)</option>
                </select>

                <button type="submit" id="register-btn" class="btn-register">
                    <span class="btn-text">Utwórz konto</span>
                </button>
            </form>

            <p id="blad" class="error-box <?= !empty($error_msg) ? 'visible' : '' ?>"><?= htmlspecialchars($error_msg) ?></p>
        </div>
    </main>

    <script>
    function switchTab(tab) {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const tabBtnLogin = document.getElementById('tabBtnLogin');
        const tabBtnRegister = document.getElementById('tabBtnRegister');
        const navTabLogin = document.getElementById('navTabLogin');
        const navTabRegister = document.getElementById('navTabRegister');
        const blad = document.getElementById('blad');

        if (blad) {
            blad.classList.remove('visible');
            blad.textContent = '';
        }

        if (tab === 'register') {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            tabBtnLogin.classList.remove('active');
            tabBtnRegister.classList.add('active');
            navTabLogin?.classList.remove('active');
            navTabRegister?.classList.add('active');
        } else {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            tabBtnRegister.classList.remove('active');
            tabBtnLogin.classList.add('active');
            navTabRegister?.classList.remove('active');
            navTabLogin?.classList.add('active');
        }
    }

    document.querySelectorAll('.auth-form').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.querySelector('.btn-text').textContent = 'Proszę czekać…';
        });
    });

    window.addEventListener('pageshow', () => {
        document.getElementById('login-btn').disabled = false;
        document.querySelector('#login-btn .btn-text').textContent = 'Zaloguj się';
        document.getElementById('register-btn').disabled = false;
        document.querySelector('#register-btn .btn-text').textContent = 'Utwórz konto';
    });
    </script>
</body>
</html>
