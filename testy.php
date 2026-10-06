<?php
require_once __DIR__ . '/db.php';
require_login();

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['login'];
$is_teacher = is_teacher();

$test_id = (int)($_GET['id'] ?? 0);

if ($test_id <= 0) {
    header("Location: main.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, nazwa, opis, max_pkt FROM test WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $test_id);
mysqli_stmt_execute($stmt);
$test_res = mysqli_stmt_get_result($stmt);
$test = mysqli_fetch_assoc($test_res);
mysqli_stmt_close($stmt);

if (!$test) {
    die("
    <!DOCTYPE html>
    <html lang='pl'>
    <head>
        <meta charset='UTF-8'>
        <title>Błąd - Test nie istnieje</title>
        <link rel='stylesheet' href='style.css'>
    </head>
    <body style='align-items:center; justify-content:center;'>
        <div class='error-card' style='max-width: 500px; text-align: center; margin: auto;'>
            <h2>Nie znaleziono testu</h2>
            <p>Test o identyfikatorze #$test_id nie istnieje w bazie danych.</p>
            <br>
            <a href='main.php' class='btn-primary'>Powrót do listy testów</a>
        </div>
    </body>
    </html>
    ");
}

$q_stmt = mysqli_prepare($conn, "SELECT id, tresc, pkt, dobra_odp FROM pytania WHERE id_testu = ? ORDER BY id ASC");
mysqli_stmt_bind_param($q_stmt, "i", $test_id);
mysqli_stmt_execute($q_stmt);
$q_res = mysqli_stmt_get_result($q_stmt);

$questions = [];
while ($q = mysqli_fetch_assoc($q_res)) {
    $a_stmt = mysqli_prepare($conn, "SELECT id_odp, tresc, literka, poprawna FROM odpowiedzi WHERE id_pytania = ? ORDER BY literka ASC, id_odp ASC");
    mysqli_stmt_bind_param($a_stmt, "i", $q['id']);
    mysqli_stmt_execute($a_stmt);
    $a_res = mysqli_stmt_get_result($a_stmt);
    
    $answers = [];
    $correct_count = 0;
    while ($a = mysqli_fetch_assoc($a_res)) {
        if (!empty($a['poprawna'])) {
            $correct_count++;
        }
        $answers[] = $a;
    }
    mysqli_stmt_close($a_stmt);
    
    $q['answers'] = $answers;
    $q['is_multiple'] = ($correct_count > 1);
    $questions[] = $q;
}
mysqli_stmt_close($q_stmt);

$total_questions = count($questions);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($test['nazwa']) ?> - Rozwiązywanie testu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="test-runner-page">
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
                <span class="arrow">&larr;</span>
                <span>Wróć do wyboru testów</span>
            </a>
        </div>
        <div class="test-nav-title">
            <span class="test-running-name"><?= htmlspecialchars($test['nazwa']) ?></span>
        </div>
        <div class="test-nav-right">
            <div class="progress-pill">
                <span>Pytania:</span>
                <strong id="answeredCount">0</strong> / <strong><?= $total_questions ?></strong>
            </div>
        </div>
    </nav>

    <div class="test-container">
        <header class="test-header-card">
            <div class="test-header-top">
                <span class="test-category-tag">Egzamin / Quiz</span>
                <span class="test-max-score">Maksymalnie: <strong><?= (int)$test['max_pkt'] ?> pkt</strong></span>
            </div>
            <h1 class="test-main-title"><?= htmlspecialchars($test['nazwa']) ?></h1>
            <p class="test-main-desc"><?= htmlspecialchars($test['opis'] ?: 'Odpowiedz na wszystkie pytania i zatwierdź test na dole strony.') ?></p>
            <div class="test-instruction">
                <span>Wskazówka: Zwróć uwagę na oznaczenia pytań – niektóre mogą posiadać więcej niż jedną poprawną odpowiedź.</span>
            </div>
        </header>

        <?php if ($total_questions === 0): ?>
            <div class="empty-state">
                <h3>Ten test nie zawiera jeszcze żadnych pytań</h3>
                <p>Nauczyciel nie dodał pytań do tego testu.</p>
                <a href="main.php" class="btn-primary" style="margin-top: 1rem;">Powrót do menu</a>
            </div>
        <?php else: ?>
            <form action="wynik.php" method="POST" id="testForm">
                <input type="hidden" name="id_testu" value="<?= $test_id ?>">

                <div class="questions-list">
                    <?php foreach ($questions as $index => $q): ?>
                        <?php 
                            $q_num = $index + 1;
                            $input_type = $q['is_multiple'] ? 'checkbox' : 'radio';
                        ?>
                        <div class="question-card" id="q-card-<?= $q['id'] ?>" data-question-id="<?= $q['id'] ?>">
                            <div class="question-card-header">
                                <div class="q-number-badge">
                                    <span class="q-badge-num">Pytanie <?= $q_num ?> z <?= $total_questions ?></span>
                                    <?php if ($q['is_multiple']): ?>
                                        <span class="q-type-badge multi">Wielokrotny wybór</span>
                                    <?php else: ?>
                                        <span class="q-type-badge single">Jednokrotny wybór</span>
                                    <?php endif; ?>
                                </div>
                                <div class="q-points-badge">
                                    <?= (int)$q['pkt'] ?> <?= (int)$q['pkt'] === 1 ? 'punkt' : 'punkty' ?>
                                </div>
                            </div>

                            <div class="question-body">
                                <h3 class="question-text"><?= htmlspecialchars($q['tresc']) ?></h3>

                                <div class="options-container">
                                    <?php foreach ($q['answers'] as $ans): ?>
                                        <label class="option-card" for="opt-<?= $ans['id_odp'] ?>">
                                            <input 
                                                type="<?= $input_type ?>" 
                                                id="opt-<?= $ans['id_odp'] ?>" 
                                                name="odp[<?= $q['id'] ?>][]" 
                                                value="<?= $ans['id_odp'] ?>" 
                                                class="option-input"
                                                onchange="updateProgress()"
                                            >
                                            <span class="option-indicator"><?= htmlspecialchars($ans['literka']) ?></span>
                                            <span class="option-label-text"><?= htmlspecialchars($ans['tresc']) ?></span>
                                            <span class="option-check-mark"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="test-submit-bar">
                    <div class="submit-bar-inner">
                        <div class="submit-progress-info">
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
                            </div>
                            <span class="progress-label" id="progressLabel">Odpowiedziano na 0 z <?= $total_questions ?> pytań</span>
                        </div>
                        <button type="submit" class="btn-submit-exam" id="btnSubmitExam">
                            <span class="btn-text">Zakończ i wyślij test</span>
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function updateProgress() {
        const questionCards = document.querySelectorAll('.question-card');
        let answered = 0;
        const total = questionCards.length;

        questionCards.forEach(card => {
            const inputs = card.querySelectorAll('.option-input');
            const isChecked = Array.from(inputs).some(input => input.checked);
            
            if (isChecked) {
                answered++;
                card.classList.add('answered');
            } else {
                card.classList.remove('answered');
            }

            inputs.forEach(input => {
                const label = input.closest('.option-card');
                if (input.checked) {
                    label.classList.add('selected');
                } else {
                    label.classList.remove('selected');
                }
            });
        });

        const answeredCountEl = document.getElementById('answeredCount');
        const progressBarFill = document.getElementById('progressBarFill');
        const progressLabel = document.getElementById('progressLabel');

        if (answeredCountEl) answeredCountEl.textContent = answered;
        if (progressBarFill && total > 0) {
            const percent = Math.round((answered / total) * 100);
            progressBarFill.style.width = percent + '%';
        }
        if (progressLabel) {
            progressLabel.textContent = `Odpowiedziano na ${answered} z ${total} pytań`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateProgress();

        const form = document.getElementById('testForm');
        form?.addEventListener('submit', (e) => {
            const questionCards = document.querySelectorAll('.question-card');
            let unansweredCount = 0;

            questionCards.forEach(card => {
                const inputs = card.querySelectorAll('.option-input');
                const isChecked = Array.from(inputs).some(input => input.checked);
                if (!isChecked) unansweredCount++;
            });

            if (unansweredCount > 0) {
                const confirmMsg = `Uwaga! Nie udzielono odpowiedzi na ${unansweredCount} ${unansweredCount === 1 ? 'pytanie' : 'pytań'}.\nCzy na pewno chcesz wysłać test teraz?`;
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    for (const card of questionCards) {
                        const inputs = card.querySelectorAll('.option-input');
                        if (!Array.from(inputs).some(i => i.checked)) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            card.classList.add('flash-attention');
                            setTimeout(() => card.classList.remove('flash-attention'), 1500);
                            break;
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>