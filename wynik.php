<?php
require_once __DIR__ . '/db.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['login'];

// Sprawdzenie czy dane przyszły metodą POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_testu'])) {
    header("Location: main.php");
    exit();
}

$test_id = (int)$_POST['id_testu'];
$user_answers = $_POST['odp'] ?? []; // Tablica [id_pytania => [id_odp, ...]]

// Pobranie danych testu
$stmt = mysqli_prepare($conn, "SELECT id, nazwa, opis, max_pkt FROM test WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$test_res = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($test_res);
mysqli_stmt_close($stmt);

if (!$test) {
    header("Location: main.php");
    exit();
}

// Pobranie pytań i odpowiedzi dla testu
$q_stmt = mysqli_prepare($conn, "SELECT id, tresc, pkt, dobra_odp FROM pytania WHERE id_testu = ? ORDER BY id ASC");
mysqli_stmt_bind_param($q_stmt, "i", $test_id);
mysqli_stmt_execute($q_stmt);
$q_res = mysqli_stmt_get_result($q_stmt);

$earned_points = 0.0;
$total_possible_points = 0.0;
$question_breakdown = [];

// Usunięcie ewentualnych starych zaznaczeń z tej samej sesji (opcjonalnie, lub dopisanie nowych)
$del_old = mysqli_prepare($conn, "DELETE FROM zaznaczone WHERE id_uzytkownik = ? AND id_test = ?");
mysqli_stmt_bind_param($del_old, "ii", $user_id, $test_id);
mysqli_stmt_execute($del_old);
mysqli_stmt_close($del_old);

$insert_zazn_stmt = mysqli_prepare($conn, "INSERT INTO zaznaczone (id_uzytkownik, id_test, id_pytania, id_odp, zaznaczona) VALUES (?, ?, ?, ?, ?)");

while ($q = mysqli_fetch_assoc($q_res)) {
    $q_id = (int)$q['id'];
    $q_pkt = (float)$q['pkt'];
    $total_possible_points += $q_pkt;

    // Pobranie odpowiedzi do pytania
    $a_stmt = mysqli_prepare($conn, "SELECT id_odp, tresc, literka, poprawna FROM odpowiedzi WHERE id_pytania = ? ORDER BY literka ASC, id_odp ASC");
    mysqli_stmt_bind_param($a_stmt, "i", $q_id);
    mysqli_stmt_execute($a_stmt);
    $a_res = mysqli_stmt_get_result($a_stmt);

    $all_answers = [];
    $correct_ids = [];
    $answers_by_id = [];

    while ($a = mysqli_fetch_assoc($a_res)) {
        $a_id = (int)$a['id_odp'];
        // Jeśli w bazie poprawna == 1, lub pasuje do dobra_odp w pytaniach
        $is_correct = (!empty($a['poprawna']) || $a['literka'] === $q['dobra_odp']);
        $a['is_correct'] = $is_correct;
        if ($is_correct) {
            $correct_ids[] = $a_id;
        }
        $all_answers[] = $a;
        $answers_by_id[$a_id] = $a;
    }
    mysqli_stmt_close($a_stmt);

    // Zaznaczenia użytkownika dla tego pytania
    $selected_ids = [];
    if (isset($user_answers[$q_id]) && is_array($user_answers[$q_id])) {
        foreach ($user_answers[$q_id] as $raw_val) {
            $selected_ids[] = (int)$raw_val;
        }
    }

    // Zapisanie każdego zaznaczenia do tabeli zaznaczone
    foreach ($selected_ids as $sid) {
        $lit = isset($answers_by_id[$sid]) ? $answers_by_id[$sid]['literka'] : '';
        mysqli_stmt_bind_param($insert_zazn_stmt, "iiiis", $user_id, $test_id, $q_id, $sid, $lit);
        mysqli_stmt_execute($insert_zazn_stmt);
    }

    // Weryfikacja poprawności
    sort($selected_ids);
    sort($correct_ids);
    
    $is_question_correct = (!empty($selected_ids) && ($selected_ids === $correct_ids));
    $awarded_pkt = 0.0;

    if ($is_question_correct) {
        $awarded_pkt = $q_pkt;
        $earned_points += $q_pkt;
    }

    $question_breakdown[] = [
        'id' => $q_id,
        'tresc' => $q['tresc'],
        'max_pkt' => $q_pkt,
        'awarded_pkt' => $awarded_pkt,
        'is_correct' => $is_question_correct,
        'all_answers' => $all_answers,
        'selected_ids' => $selected_ids,
        'correct_ids' => $correct_ids
    ];
}
mysqli_stmt_close($insert_zazn_stmt);
mysqli_stmt_close($q_stmt);

// Obliczenie procentów i oceny szkolnej
$max_score = max(1.0, (float)$test['max_pkt']);
if ($total_possible_points > 0) {
    $max_score = $total_possible_points;
}

$percentage = round(($earned_points / $max_score) * 100);

if ($percentage >= 98) {
    $grade_num = '6';
    $grade_name = 'Celujący';
    $grade_color = 'grade-6';
} elseif ($percentage >= 90) {
    $grade_num = '5';
    $grade_name = 'Bardzo dobry';
    $grade_color = 'grade-5';
} elseif ($percentage >= 75) {
    $grade_num = '4';
    $grade_name = 'Dobry';
    $grade_color = 'grade-4';
} elseif ($percentage >= 50) {
    $grade_num = '3';
    $grade_name = 'Dostateczny';
    $grade_color = 'grade-3';
} elseif ($percentage >= 35) {
    $grade_num = '2';
    $grade_name = 'Dopuszczający';
    $grade_color = 'grade-2';
} else {
    $grade_num = '1';
    $grade_name = 'Niedostateczny';
    $grade_color = 'grade-1';
}

$passed = ($percentage >= 50);

// Zapis wyniku do bazy danych
$res_stmt = mysqli_prepare($conn, "INSERT INTO wyniki (id_testu, id_uzytkownik, ocena) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($res_stmt, "iid", $test_id, $user_id, $earned_points);
mysqli_stmt_execute($res_stmt);
mysqli_stmt_close($res_stmt);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wyniki &bull; <?= htmlspecialchars($test['nazwa']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="results-page">
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

    <nav class="test-nav">
        <div class="test-nav-left">
            <a href="main.php" class="btn-back">
                <span class="arrow">←</span>
                <span>Wróć do panelu głównego</span>
            </a>
        </div>
        <div class="test-nav-title">
            <span class="test-running-name">Podsumowanie testu</span>
        </div>
        <div class="test-nav-right">
            <a href="testy.php?id=<?= $test_id ?>" class="btn-nav-action">
                <span>Rozwiąż ponownie</span>
            </a>
        </div>
    </nav>

    <div class="results-container">
        <!-- Główna karta podsumowania punktacji -->
        <div class="score-card <?= $passed ? 'passed' : 'failed' ?>">
            <div class="score-header">
                <span class="score-pill <?= $passed ? 'pill-pass' : 'pill-fail' ?>">
                    <?= $passed ? '✓ Test Zaliczony' : '✕ Test Niezaliczony' ?>
                </span>
                <span class="score-test-name"><?= htmlspecialchars($test['nazwa']) ?></span>
            </div>

            <div class="score-main-flex">
                <div class="score-circle-wrapper">
                    <div class="score-number-display">
                        <span class="score-earned"><?= $earned_points ?></span>
                        <span class="score-divider">/</span>
                        <span class="score-max"><?= $max_score ?></span>
                    </div>
                    <span class="score-points-unit">punktów</span>
                </div>

                <div class="score-meta-panel">
                    <div class="score-stat-box">
                        <span class="stat-label">Wynik procentowy</span>
                        <span class="stat-value highlight-cyan"><?= $percentage ?>%</span>
                    </div>
                    <div class="score-stat-box">
                        <span class="stat-label">Ocena szkolna</span>
                        <span class="stat-value grade-tag <?= $grade_color ?>">
                            <?= $grade_num ?> &bull; <?= $grade_name ?>
                        </span>
                    </div>
                    <div class="score-stat-box">
                        <span class="stat-label">Użytkownik</span>
                        <span class="stat-value">👤 <?= htmlspecialchars($user_login) ?></span>
                    </div>
                </div>
            </div>

            <div class="score-actions">
                <a href="main.php" class="btn-primary">Wróć do listy testów</a>
                <a href="testy.php?id=<?= $test_id ?>" class="btn-secondary">Rozwiąż test jeszcze raz</a>
            </div>
        </div>

        <!-- Przegląd szczegółowy pytań -->
        <section class="review-section">
            <div class="review-header">
                <h2>Szczegółowa analiza odpowiedzi</h2>
                <p>Sprawdź swoje odpowiedzi i poprawne rozwiązania każdego zadania</p>
            </div>

            <div class="review-list">
                <?php foreach ($question_breakdown as $idx => $qb): ?>
                    <?php 
                        $card_class = $qb['is_correct'] ? 'review-correct' : 'review-wrong';
                    ?>
                    <div class="review-card <?= $card_class ?>">
                        <div class="review-card-top">
                            <span class="review-q-num">Pytanie <?= $idx + 1 ?></span>
                            <span class="review-status-pill <?= $qb['is_correct'] ? 'status-ok' : 'status-err' ?>">
                                <?= $qb['is_correct'] ? "✓ Dobrze (+{$qb['awarded_pkt']} pkt)" : "✕ Błąd (0 / {$qb['max_pkt']} pkt)" ?>
                            </span>
                        </div>

                        <h3 class="review-q-text"><?= htmlspecialchars($qb['tresc']) ?></h3>

                        <div class="review-options">
                            <?php foreach ($qb['all_answers'] as $ans): ?>
                                <?php 
                                    $ans_id = (int)$ans['id_odp'];
                                    $was_selected = in_array($ans_id, $qb['selected_ids'], true);
                                    $is_correct_ans = $ans['is_correct'];

                                    $ans_style_class = '';
                                    $badge_text = '';

                                    if ($was_selected && $is_correct_ans) {
                                        $ans_style_class = 'ans-chosen-correct';
                                        $badge_text = '✓ Twoja poprawna odpowiedź';
                                    } elseif ($was_selected && !$is_correct_ans) {
                                        $ans_style_class = 'ans-chosen-wrong';
                                        $badge_text = '✕ Twoja błędna odpowiedź';
                                    } elseif (!$was_selected && $is_correct_ans) {
                                        $ans_style_class = 'ans-missed-correct';
                                        $badge_text = 'Prawidłowa odpowiedź';
                                    } else {
                                        $ans_style_class = 'ans-neutral';
                                    }
                                ?>
                                <div class="review-option-row <?= $ans_style_class ?>">
                                    <span class="review-letter"><?= htmlspecialchars($ans['literka']) ?></span>
                                    <span class="review-text"><?= htmlspecialchars($ans['tresc']) ?></span>
                                    <?php if (!empty($badge_text)): ?>
                                        <span class="review-badge"><?= $badge_text ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <p>&copy; <?= date('Y') ?> TestHub &bull; Wynik został automatycznie zapisany w bazie danych.</p>
    </footer>
</body>
</html>
