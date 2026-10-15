<?php
require_once __DIR__ . '/classroom.php';
require_teacher();

$teacher_id = (int)$_SESSION['user_id'];
$classes = teacher_classes($conn, $teacher_id);
$class_id = (int)($_GET['id'] ?? 0);
$selected_class = null;
foreach ($classes as $class) {
    if ((int)$class['id'] === $class_id) {
        $selected_class = $class;
    }
}
if ($class_id !== 0 && !$selected_class) {
    http_response_code(404);
    exit('Nie znaleziono klasy.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(classroom_csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Odśwież stronę i spróbuj ponownie.');
    }
    $action = $_POST['action'] ?? '';
    $message = '';
    $type = 'success';
    try {
        if ($action === 'create_class') {
            $name = trim($_POST['nazwa'] ?? '');
            if ($name === '' || mb_strlen($name) > 100) {
                throw new InvalidArgumentException('Podaj nazwę klasy (maksymalnie 100 znaków).');
            }
            $stmt = mysqli_prepare($conn, 'INSERT INTO klasy (id_nauczyciela, nazwa) VALUES (?, ?)');
            mysqli_stmt_bind_param($stmt, 'is', $teacher_id, $name);
            mysqli_stmt_execute($stmt);
            $class_id = (int)mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            $message = 'Klasa została utworzona. Dodaj uczniów, wpisując ich loginy.';
        } elseif ($selected_class && $action === 'add_student') {
            $login = trim($_POST['login'] ?? '');
            $stmt = mysqli_prepare($conn, 'SELECT id FROM uzytkownicy WHERE login = ? AND nauczyciel = 0');
            mysqli_stmt_bind_param($stmt, 's', $login);
            mysqli_stmt_execute($stmt);
            $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$student) {
                throw new InvalidArgumentException('Nie znaleziono konta ucznia o tym loginie. Uczeń musi najpierw założyć konto.');
            }
            $stmt = mysqli_prepare($conn, '
                INSERT INTO uczniowie_klas (id_klasy, id_ucznia)
                SELECT id, ? FROM klasy WHERE id = ? AND id_nauczyciela = ?
            ');
            mysqli_stmt_bind_param($stmt, 'iii', $student['id'], $class_id, $teacher_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = 'Uczeń został dodany do klasy.';
        } elseif ($selected_class && $action === 'remove_student') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            $stmt = mysqli_prepare($conn, '
                DELETE uk FROM uczniowie_klas uk JOIN klasy k ON k.id = uk.id_klasy
                WHERE uk.id_klasy = ? AND uk.id_ucznia = ? AND k.id_nauczyciela = ?
            ');
            mysqli_stmt_bind_param($stmt, 'iii', $class_id, $student_id, $teacher_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = 'Uczeń został usunięty z klasy. Jego konto i wyniki pozostają zapisane.';
        } elseif ($selected_class && $action === 'delete_class') {
            $stmt = mysqli_prepare($conn, 'DELETE FROM klasy WHERE id = ? AND id_nauczyciela = ?');
            mysqli_stmt_bind_param($stmt, 'ii', $class_id, $teacher_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $class_id = 0;
            $message = 'Klasa została usunięta. Konta i wyniki uczniów pozostają zapisane.';
        } else {
            http_response_code(400);
            exit('Nieprawidłowa operacja.');
        }
    } catch (InvalidArgumentException $ex) {
        $message = $ex->getMessage();
        $type = 'error';
    } catch (mysqli_sql_exception $ex) {
        $message = $ex->getCode() === 1062
            ? ($action === 'create_class' ? 'Masz już klasę o tej nazwie.' : 'Ten uczeń jest już w klasie.')
            : 'Nie udało się zapisać zmian. Spróbuj ponownie.';
        $type = 'error';
    }
    $_SESSION['classroom_flash'] = ['message' => $message, 'type' => $type];
    header('Location: classes.php' . ($class_id ? '?id=' . $class_id : ''), true, 303);
    exit();
}

$flash = $_SESSION['classroom_flash'] ?? null;
unset($_SESSION['classroom_flash']);
$students = [];
if ($selected_class) {
    $stmt = mysqli_prepare($conn, '
        SELECT u.id, u.login FROM uczniowie_klas uk
        JOIN klasy k ON k.id = uk.id_klasy
        JOIN uzytkownicy u ON u.id = uk.id_ucznia
        WHERE k.id = ? AND k.id_nauczyciela = ? ORDER BY u.login
    ');
    mysqli_stmt_bind_param($stmt, 'ii', $class_id, $teacher_id);
    mysqli_stmt_execute($stmt);
    $students = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
$csrf = classroom_csrf_token();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestHub - Moje klasy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="main.php" class="brand-link"><span class="brand-text">TestHub</span></a>
        <div class="nav-user-panel">
            <a href="main.php" class="btn-back">Wróć do testów i wyników</a>
            <span class="user-name"><?= htmlspecialchars($_SESSION['login']) ?></span>
            <a href="logout.php" class="btn-nav-action btn-logout">Wyloguj</a>
        </div>
    </nav>
    <main class="classroom-container">
        <header class="classroom-heading">
            <h1>Moje klasy</h1>
            <p>Dodaj uczniów do swojej klasy, aby widzieć ich wyniki.</p>
        </header>
        <?php if ($flash): ?>
            <div role="status" class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        <div class="classroom-layout">
            <aside class="classroom-sidebar">
                <section class="creator-card">
                    <h2>Twoje klasy</h2>
                    <?php if (!$classes): ?><p class="text-muted">Nie masz jeszcze żadnej klasy.</p><?php endif; ?>
                    <ul class="class-list">
                        <?php foreach ($classes as $class): ?>
                            <li><a href="classes.php?id=<?= (int)$class['id'] ?>" <?= (int)$class['id'] === $class_id ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($class['nazwa']) ?><span>Liczba uczniów: <?= (int)$class['liczba_uczniow'] ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <form method="post" action="classes.php" class="creator-card">
                    <h2>Nowa klasa</h2>
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="create_class">
                    <label for="class-name">Nazwa klasy</label>
                    <input type="text" id="class-name" name="nazwa" placeholder="np. 2B – informatyka" maxlength="100" required>
                    <button class="btn-primary classroom-button" type="submit">Utwórz klasę</button>
                </form>
            </aside>
            <section class="creator-card classroom-roster">
                <?php if (!$selected_class): ?>
                    <h2>Wybierz klasę</h2>
                    <p class="text-muted">Otwórz klasę z listy lub utwórz nową, a następnie dodaj do niej uczniów.</p>
                <?php else: ?>
                    <div class="section-header">
                        <h2><?= htmlspecialchars($selected_class['nazwa']) ?></h2>
                        <a href="main.php?class_id=<?= $class_id ?>#teacher-results">Wyniki tej klasy</a>
                    </div>
                    <form method="post" class="classroom-add-form">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="add_student">
                        <label for="student-login">Login ucznia</label>
                        <div class="classroom-add-row">
                            <input type="text" id="student-login" name="login" placeholder="Wpisz login istniejącego konta" maxlength="191" required>
                            <button type="submit" class="btn-primary">Dodaj ucznia</button>
                        </div>
                    </form>
                    <?php if (!$students): ?>
                        <p class="empty-state-mini">Ta klasa nie ma jeszcze uczniów.</p>
                    <?php else: ?>
                        <ul class="roster-list">
                            <?php foreach ($students as $student): ?>
                                <li>
                                    <span><?= htmlspecialchars($student['login']) ?></span>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="remove_student">
                                        <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
                                        <button type="submit" class="btn-delete-text" aria-label="Usuń <?= htmlspecialchars($student['login']) ?> z klasy">Usuń z klasy</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <form method="post" class="classroom-delete" onsubmit="return confirm('Usunąć tę klasę? Konta i wyniki uczniów pozostaną zapisane.');">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete_class">
                        <button type="submit" class="btn-delete-text">Usuń klasę</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
