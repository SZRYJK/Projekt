<?php
require_once __DIR__ . '/db.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['login'];
$is_teacher = is_teacher();

$flash_msg = "";
$flash_type = "";
if ($is_teacher && isset($_POST['action']) && $_POST['action'] === 'delete_test') {
    $test_id_to_del = (int)($_POST['test_id'] ?? 0);
    if ($test_id_to_del > 0) {
        $del_stmt = mysqli_prepare($conn, "DELETE FROM test WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $test_id_to_del);
        if (mysqli_stmt_execute($del_stmt)) {
            $flash_msg = "Test został pomyślnie usunięty.";
            $flash_type = "success";
        } else {
            $flash_msg = "Wystąpił błąd podczas usuwania testu: " . mysqli_error($conn);
            $flash_type = "error";
        }
        mysqli_stmt_close($del_stmt);
    }
}

$tests_query = "
    SELECT t.id, t.nazwa, t.opis, t.max_pkt,
           COUNT(p.id) AS liczba_pytan
    FROM test t
    LEFT JOIN pytania p ON p.id_testu = t.id
    GROUP BY t.id, t.nazwa, t.opis, t.max_pkt
    ORDER BY t.id ASC
";
$tests_res = mysqli_query($conn, $tests_query);
$tests = [];
while ($row = mysqli_fetch_assoc($tests_res)) {
    $tests[] = $row;
}

$student_results = [];
if (!$is_teacher) {
    $stmt = mysqli_prepare($conn, "
        SELECT w.id, w.id_testu, w.ocena, w.data_wykonania, t.nazwa, t.max_pkt
        FROM wyniki w
        JOIN test t ON w.id_testu = t.id
        WHERE w.id_uzytkownik = ?
        ORDER BY w.id DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $student_results[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$teacher_results = [];
if ($is_teacher) {
    $tres = mysqli_query($conn, "
        SELECT w.id, w.ocena, w.data_wykonania, u.login AS uczen, t.nazwa AS test_nazwa, t.max_pkt
        FROM wyniki w
        JOIN uzytkownicy u ON w.id_uzytkownik = u.id
        JOIN test t ON w.id_testu = t.id
        ORDER BY w.id DESC
        LIMIT 50
    ");
    while ($row = mysqli_fetch_assoc($tres)) {
        $teacher_results[] = $row;
    }
}

$latest_test_scores = [];
foreach ($student_results as $sr) {
    if (!isset($latest_test_scores[$sr['id_testu']])) {
        $latest_test_scores[$sr['id_testu']] = $sr;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestHub - Panel Główny</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">
    <div class="bg-animation" aria-hidden="true">
        <div class="bg-grid"></div>
        <div class="glow-orb orb-1"></div>
        <div class="glow-orb orb-2"></div>
        <div class="glow-orb orb-3"></div>
        <div class="particles">
            <span class="p p1"></span>
            <span class="p p2"></span>
            <span class="p p3"></span>
            <span class="p p4"></span>
            <span class="p p5"></span>
            <span class="p p6"></span>
            <span class="p p7"></span>
            <span class="p p8"></span>
        </div>
    </div>

    <div class="top-loader-bar"></div>

    <nav class="main-nav">
        <div class="nav-brand">
            <a href="main.php" class="brand-link">
                <span class="brand-text">TestHub</span>
            </a>
        </div>
        
        <div class="nav-user-panel">
            <div class="user-badge">
                <span class="user-name"><?= htmlspecialchars($user_login) ?></span>
                <span class="role-pill <?= $is_teacher ? 'role-teacher' : 'role-student' ?>">
                    <?= $is_teacher ? 'Nauczyciel' : 'Uczeń' ?>
                </span>
            </div>

            <?php if ($is_teacher): ?>
                <a href="Z.php" class="btn-nav-action btn-add-test">
                    <span class="btn-icon">+</span> Stwórz test
                </a>
            <?php endif; ?>

            <a href="logout.php" class="btn-nav-action btn-logout" title="Wyloguj się">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Wyloguj</span>
            </a>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if (!empty($flash_msg)): ?>
            <div class="alert <?= $flash_type === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($flash_msg) ?>
            </div>
        <?php endif; ?>

        <header class="hero-header">
            <div class="hero-content">
                <h1 class="hero-title">
                    Witaj, <span class="highlight"><?= htmlspecialchars($user_login) ?></span>!
                </h1>
                <p class="hero-subtitle">
                    <?= $is_teacher 
                        ? 'Zarządzaj bazą pytań, twórz nowe testy oraz monitoruj wyniki i oceny uczniów.' 
                        : 'Wybierz interesujący Cię test z poniższej listy, sprawdź swoją wiedzę i uzyskaj natychmiastową ocenę.' 
                    ?>
                </p>
            </div>
            <?php if ($is_teacher): ?>
                <div class="hero-actions">
                    <a href="Z.php" class="btn-hero-primary">
                        <span class="btn-icon">+</span> Utwórz nowy test
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <section class="dashboard-section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Dostępne Testy</h2>
                    <p class="section-subtitle">Wybierz test do rozwiązania lub podglądu</p>
                </div>
                <span class="badge-count"><?= count($tests) ?> testów</span>
            </div>

            <?php if (empty($tests)): ?>
                <div class="empty-state">
                    <h3>Brak dostępnych testów</h3>
                    <p>W bazie danych nie ma jeszcze żadnych testów.
                    <?php if ($is_teacher): ?>
                        Kliknij poniżej, aby stworzyć pierwszy test!
                    <?php endif; ?>
                    </p>
                    <?php if ($is_teacher): ?>
                        <a href="Z.php" class="btn-primary" style="margin-top: 1rem;">Stwórz pierwszy test</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="test-grid">
                    <?php foreach ($tests as $t): ?>
                        <?php 
                            $has_attempt = isset($latest_test_scores[$t['id']]);
                            $latest = $has_attempt ? $latest_test_scores[$t['id']] : null;
                        ?>
                        <div class="test-card">
                            <div class="test-card-top">
                                <div class="test-badges">
                                    <span class="test-meta-badge">
                                        <?= (int)$t['liczba_pytan'] ?> <?= (int)$t['liczba_pytan'] === 1 ? 'pytanie' : ((int)$t['liczba_pytan'] < 5 ? 'pytania' : 'pytań') ?>
                                    </span>
                                    <span class="test-meta-badge points-badge">
                                        Max: <?= (int)$t['max_pkt'] ?> pkt
                                    </span>
                                </div>
                                <?php if ($is_teacher): ?>
                                    <form method="post" class="del-test-form" onsubmit="return confirm('Czy na pewno chcesz usunąć ten test wraz ze wszystkimi pytaniami i wynikami?');">
                                        <input type="hidden" name="action" value="delete_test">
                                        <input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn-delete-text" title="Usuń ten test">Usuń</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <h3 class="test-title"><?= htmlspecialchars($t['nazwa']) ?></h3>
                            <p class="test-desc"><?= htmlspecialchars($t['opis'] ?: 'Brak opisu dla tego testu.') ?></p>

                            <?php if (!$is_teacher && $has_attempt): ?>
                                <div class="test-history-pill">
                                    <span class="pill-dot"></span>
                                    <span>Twój wynik: <strong><?= (float)$latest['ocena'] ?> / <?= (int)$t['max_pkt'] ?> pkt</strong></span>
                                </div>
                            <?php endif; ?>

                            <div class="test-card-footer">
                                <a href="testy.php?id=<?= $t['id'] ?>" class="btn-start-test">
                                    <span><?= ($is_teacher ? 'Podgląd / Wykonaj test' : ($has_attempt ? 'Rozwiąż ponownie' : 'Rozwiąż test')) ?></span>
                                    <span class="arrow">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$is_teacher): ?>
            <section class="dashboard-section" style="margin-top: 3.5rem;">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Twoje Ostatnie Podejścia</h2>
                        <p class="section-subtitle">Historia rozwiązanych testów i uzyskane punkty</p>
                    </div>
                </div>

                <?php if (empty($student_results)): ?>
                    <div class="empty-state-mini">
                        <p>Nie rozwiązałeś jeszcze żadnego testu. Wybierz test powyżej i sprawdź się!</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Zdobyte punkty</th>
                                    <th>Wynik procentowy</th>
                                    <th>Ocena szkolna</th>
                                    <th>Data podejścia</th>
                                    <th>Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_results as $sr): ?>
                                    <?php 
                                        $max = max(1, (int)$sr['max_pkt']);
                                        $score = (float)$sr['ocena'];
                                        $percent = round(($score / $max) * 100);
                                        
                                        if ($percent >= 98) { $grade_num = '6'; $grade_txt = 'celujący'; }
                                        elseif ($percent >= 90) { $grade_num = '5'; $grade_txt = 'bardzo dobry'; }
                                        elseif ($percent >= 75) { $grade_num = '4'; $grade_txt = 'dobry'; }
                                        elseif ($percent >= 50) { $grade_num = '3'; $grade_txt = 'dostateczny'; }
                                        elseif ($percent >= 35) { $grade_num = '2'; $grade_txt = 'dopuszczający'; }
                                        else { $grade_num = '1'; $grade_txt = 'niedostateczny'; }
                                        
                                        $grade_class = ($percent >= 50) ? 'badge-good' : 'badge-bad';
                                    ?>
                                    <tr>
                                        <td class="table-bold"><?= htmlspecialchars($sr['nazwa']) ?></td>
                                        <td><strong><?= $score ?></strong> / <?= $max ?> pkt</td>
                                        <td>
                                            <div class="progress-cell">
                                                <span><?= $percent ?>%</span>
                                                <div class="mini-bar">
                                                    <div class="mini-bar-fill" style="width: <?= min(100, $percent) ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="grade-badge <?= $grade_class ?>"><?= $grade_num ?> (<?= $grade_txt ?>)</span>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($sr['data_wykonania'] ?? 'Brak') ?></td>
                                        <td>
                                            <a href="testy.php?id=<?= $sr['id_testu'] ?>" class="btn-table-action">Powtórz</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="dashboard-section" style="margin-top: 3.5rem;">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Wyniki Uczniów</h2>
                        <p class="section-subtitle">Ostatnie podejścia wykonane przez uczniów</p>
                    </div>
                    <span class="badge-count"><?= count($teacher_results) ?> wpisów</span>
                </div>

                <?php if (empty($teacher_results)): ?>
                    <div class="empty-state-mini">
                        <p>Żaden uczeń nie rozwiązał jeszcze żadnego testu.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Uczeń</th>
                                    <th>Test</th>
                                    <th>Punkty</th>
                                    <th>Wynik %</th>
                                    <th>Ocena</th>
                                    <th>Data i czas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacher_results as $tr): ?>
                                    <?php 
                                        $max = max(1, (int)$tr['max_pkt']);
                                        $score = (float)$tr['ocena'];
                                        $percent = round(($score / $max) * 100);
                                        
                                        if ($percent >= 98) { $grade_num = '6'; $grade_txt = 'celujący'; }
                                        elseif ($percent >= 90) { $grade_num = '5'; $grade_txt = 'bardzo dobry'; }
                                        elseif ($percent >= 75) { $grade_num = '4'; $grade_txt = 'dobry'; }
                                        elseif ($percent >= 50) { $grade_num = '3'; $grade_txt = 'dostateczny'; }
                                        elseif ($percent >= 35) { $grade_num = '2'; $grade_txt = 'dopuszczający'; }
                                        else { $grade_num = '1'; $grade_txt = 'niedostateczny'; }
                                        
                                        $grade_class = ($percent >= 50) ? 'badge-good' : 'badge-bad';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="student-avatar-tag">
                                                <strong><?= htmlspecialchars($tr['uczen']) ?></strong>
                                            </span>
                                        </td>
                                        <td class="table-bold"><?= htmlspecialchars($tr['test_nazwa']) ?></td>
                                        <td><strong><?= $score ?></strong> / <?= $max ?> pkt</td>
                                        <td>
                                            <div class="progress-cell">
                                                <span><?= $percent ?>%</span>
                                                <div class="mini-bar">
                                                    <div class="mini-bar-fill" style="width: <?= min(100, $percent) ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="grade-badge <?= $grade_class ?>"><?= $grade_num ?> (<?= $grade_txt ?>)</span>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($tr['data_wykonania'] ?? 'Brak') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <footer class="main-footer">
        <p>&copy; <?= date('Y') ?> TestHub - System testów i weryfikacji wiedzy online.</p>
    </footer>
</body>
</html>