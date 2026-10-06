<?php
require_once __DIR__ . '/db.php';
require_teacher();

$user_id = $_SESSION['user_id'];
$user_login = $_SESSION['login'];

$error_msg = "";
$success_msg = "";
$new_test_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa = trim($_POST['nazwa'] ?? '');
    $opis = trim($_POST['opis'] ?? '');
    $pytania_post = $_POST['pytania'] ?? [];

    if (empty($nazwa)) {
        $error_msg = "Proszę podać nazwę testu.";
    } elseif (empty($pytania_post) || !is_array($pytania_post)) {
        $error_msg = "Test musi zawierać co najmniej jedno pytanie.";
    } else {
        // Obliczenie sumy punktów i weryfikacja pytań
        $max_pkt = 0;
        $valid_questions = [];

        foreach ($pytania_post as $q_data) {
            $q_tresc = trim($q_data['tresc'] ?? '');
            $q_pkt = max(1, (int)($q_data['pkt'] ?? 1));
            $odpowiedzi_raw = $q_data['odpowiedzi'] ?? [];

            if (empty($q_tresc)) {
                continue;
            }

            $valid_answers = [];
            $has_correct = false;
            $first_correct_letter = 'A';

            $letters = range('A', 'Z');
            $idx = 0;

            foreach ($odpowiedzi_raw as $ans_data) {
                $a_tresc = trim($ans_data['tresc'] ?? '');
                $is_correct = !empty($ans_data['poprawna']) ? 1 : 0;

                if (empty($a_tresc)) {
                    continue;
                }

                $letter = $letters[$idx] ?? chr(65 + ($idx % 26));
                if ($is_correct && !$has_correct) {
                    $first_correct_letter = $letter;
                    $has_correct = true;
                }

                $valid_answers[] = [
                    'tresc' => $a_tresc,
                    'literka' => $letter,
                    'poprawna' => $is_correct
                ];
                $idx++;
            }

            if (count($valid_answers) < 2) {
                $error_msg = "Każde pytanie musi posiadać co najmniej 2 odpowiedzi.";
                break;
            }

            if (!$has_correct) {
                // Jeśli nie zaznaczono poprawnej, pierwsza odpowiedź staje się poprawna domyślnie
                $valid_answers[0]['poprawna'] = 1;
                $first_correct_letter = $valid_answers[0]['literka'];
            }

            $max_pkt += $q_pkt;
            $valid_questions[] = [
                'tresc' => $q_tresc,
                'pkt' => $q_pkt,
                'dobra_odp' => $first_correct_letter,
                'odpowiedzi' => $valid_answers
            ];
        }

        if (empty($error_msg)) {
            if (empty($valid_questions)) {
                $error_msg = "Wypełnij treść przynajmniej jednego pytania.";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    // 1. Zapis testu
                    $t_stmt = mysqli_prepare($conn, "INSERT INTO test (nazwa, opis, max_pkt) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($t_stmt, "ssi", $nazwa, $opis, $max_pkt);
                    mysqli_stmt_execute($t_stmt);
                    $new_test_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($t_stmt);

                    // 2. Zapis pytań i odpowiedzi
                    $q_stmt = mysqli_prepare($conn, "INSERT INTO pytania (id_testu, tresc, pkt, dobra_odp) VALUES (?, ?, ?, ?)");
                    $a_stmt = mysqli_prepare($conn, "INSERT INTO odpowiedzi (id_pytania, tresc, literka, poprawna) VALUES (?, ?, ?, ?)");

                    foreach ($valid_questions as $vq) {
                        mysqli_stmt_bind_param($q_stmt, "isis", $new_test_id, $vq['tresc'], $vq['pkt'], $vq['dobra_odp']);
                        mysqli_stmt_execute($q_stmt);
                        $new_q_id = mysqli_insert_id($conn);

                        foreach ($vq['odpowiedzi'] as $va) {
                            mysqli_stmt_bind_param($a_stmt, "issi", $new_q_id, $va['tresc'], $va['literka'], $va['poprawna']);
                            mysqli_stmt_execute($a_stmt);
                        }
                    }

                    mysqli_stmt_close($q_stmt);
                    mysqli_stmt_close($a_stmt);

                    mysqli_commit($conn);
                    $success_msg = "Test \"$nazwa\" został pomyślnie utworzony i opublikowany!";
                } catch (Exception $ex) {
                    mysqli_rollback($conn);
                    $error_msg = "Błąd bazy danych: " . $ex->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestHub &bull; Kreator Testów</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="creator-page">
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
            <span class="test-running-name">Kreator Nowego Testu</span>
        </div>
        <div class="test-nav-right">
            <div class="user-badge" style="padding: 0.35rem 0.85rem;">
                <span class="role-pill role-teacher">Nauczyciel: <?= htmlspecialchars($user_login) ?></span>
            </div>
        </div>
    </nav>

    <div class="creator-container">
        <?php if (!empty($success_msg)): ?>
            <div class="success-banner-card">
                <div class="banner-icon">🎉</div>
                <h2><?= htmlspecialchars($success_msg) ?></h2>
                <p>Uczniowie mogą już rozwiązywać ten test na liście dostępnych sprawdzianów.</p>
                <div class="banner-actions">
                    <a href="testy.php?id=<?= $new_test_id ?>" class="btn-primary">Podgląd i wykonaj test</a>
                    <a href="main.php" class="btn-secondary">Wróć do panelu</a>
                </div>
            </div>
        <?php else: ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="creatorForm">
                <!-- Informacje ogólne o teście -->
                <div class="creator-card">
                    <h2 class="creator-card-title">1. Informacje podstawowe o teście</h2>
                    <p class="creator-card-desc">Podaj nazwę oraz zwięzły opis przedmiotu lub zakresu materiału.</p>
                    
                    <div class="form-group">
                        <label for="nazwa">Nazwa testu:</label>
                        <input type="text" name="nazwa" id="nazwa" placeholder="np. Test 4: Relacje i Indeksy w MySQL" required value="<?= htmlspecialchars($_POST['nazwa'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="opis">Opis testu:</label>
                        <textarea name="opis" id="opis" rows="3" placeholder="np. Sprawdzian wiedzy z zakresu relacji 1:N, M:N, indeksów oraz transakcji."><?= htmlspecialchars($_POST['opis'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Lista dynamicznych pytań -->
                <div class="creator-card">
                    <div class="creator-card-header-flex">
                        <div>
                            <h2 class="creator-card-title">2. Pytania i odpowiedzi</h2>
                            <p class="creator-card-desc">Dodawaj pytania, wpisuj opcje odpowiedzi i zaznaczaj poprawne rozwiązania.</p>
                        </div>
                        <button type="button" class="btn-add-question" onclick="addQuestion()">
                            ＋ Dodaj pytanie
                        </button>
                    </div>

                    <div id="questionsContainer" class="questions-builder-list">
                        <!-- Pytania będą generowane dynamicznie przez JS -->
                    </div>

                    <div class="creator-add-btn-bar">
                        <button type="button" class="btn-add-question-large" onclick="addQuestion()">
                            ＋ Dodaj kolejne pytanie
                        </button>
                    </div>
                </div>

                <!-- Dolny pasek zapisu -->
                <div class="creator-footer-bar">
                    <a href="main.php" class="btn-cancel">Anuluj</a>
                    <button type="submit" class="btn-publish-test">
                        <span>Zapisz i opublikuj test</span>
                        <span class="arrow">✓</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    let questionCounter = 0;

    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    function addQuestion(initialData = null) {
        questionCounter++;
        const qIndex = questionCounter;

        const container = document.getElementById('questionsContainer');
        const qCard = document.createElement('div');
        qCard.className = 'q-builder-card';
        qCard.id = `qCard_${qIndex}`;

        qCard.innerHTML = `
            <div class="q-builder-header">
                <span class="q-builder-title">Pytanie #${qIndex}</span>
                <div class="q-builder-controls">
                    <div class="q-pts-input">
                        <label>Punkty:</label>
                        <input type="number" name="pytania[${qIndex}][pkt]" value="${initialData?.pkt || 1}" min="1" max="100">
                    </div>
                    <button type="button" class="btn-del-q" onclick="removeQuestion(${qIndex})" title="Usuń pytanie">🗑 Usuń</button>
                </div>
            </div>

            <div class="form-group">
                <label>Treść pytania:</label>
                <input type="text" name="pytania[${qIndex}][tresc]" placeholder="Wpisz treść pytania..." required value="${initialData?.tresc || ''}">
            </div>

            <div class="answers-builder-section">
                <label class="ans-section-label">Odpowiedzi (zaznacz poprawne):</label>
                <div class="answers-list" id="answersList_${qIndex}"></div>
                <button type="button" class="btn-add-ans-mini" onclick="addAnswer(${qIndex})">＋ Dodaj odpowiedź</button>
            </div>
        `;

        container.appendChild(qCard);

        if (initialData && initialData.odpowiedzi) {
            initialData.odpowiedzi.forEach(ans => addAnswer(qIndex, ans));
        } else {
            // Domyślnie 4 odpowiedzi A, B, C, D
            addAnswer(qIndex, { tresc: '', isCorrect: true });
            addAnswer(qIndex, { tresc: '', isCorrect: false });
            addAnswer(qIndex, { tresc: '', isCorrect: false });
            addAnswer(qIndex, { tresc: '', isCorrect: false });
        }

        reindexQuestions();
    }

    function removeQuestion(qIndex) {
        const qCard = document.getElementById(`qCard_${qIndex}`);
        if (qCard) {
            const all = document.querySelectorAll('.q-builder-card');
            if (all.length <= 1) {
                alert('Test musi zawierać przynajmniej jedno pytanie!');
                return;
            }
            qCard.remove();
            reindexQuestions();
        }
    }

    function addAnswer(qIndex, ansData = null) {
        const list = document.getElementById(`answersList_${qIndex}`);
        if (!list) return;

        const ansIndex = list.children.length;
        const letter = alphabet[ansIndex] || `O${ansIndex + 1}`;

        const ansRow = document.createElement('div');
        ansRow.className = 'ans-builder-row';

        ansRow.innerHTML = `
            <label class="ans-check-label" title="Zaznacz jeśli ta odpowiedź jest poprawna">
                <input type="checkbox" name="pytania[${qIndex}][odpowiedzi][${ansIndex}][poprawna]" value="1" ${ansData?.isCorrect ? 'checked' : ''}>
                <span class="ans-letter-badge">${letter}</span>
            </label>
            <input type="text" name="pytania[${qIndex}][odpowiedzi][${ansIndex}][tresc]" placeholder="Treść odpowiedzi ${letter}" value="${ansData?.tresc || ''}" required>
            <button type="button" class="btn-del-ans" onclick="removeAnswer(this, ${qIndex})" title="Usuń tę odpowiedź">✕</button>
        `;

        list.appendChild(ansRow);
        updateAnswerLetters(qIndex);
    }

    function removeAnswer(btn, qIndex) {
        const list = document.getElementById(`answersList_${qIndex}`);
        if (!list) return;
        if (list.children.length <= 2) {
            alert('Pytanie musi mieć przynajmniej 2 odpowiedzi!');
            return;
        }
        btn.closest('.ans-builder-row').remove();
        updateAnswerLetters(qIndex);
    }

    function updateAnswerLetters(qIndex) {
        const list = document.getElementById(`answersList_${qIndex}`);
        if (!list) return;
        const rows = list.querySelectorAll('.ans-builder-row');
        rows.forEach((row, i) => {
            const letter = alphabet[i] || `O${i + 1}`;
            const badge = row.querySelector('.ans-letter-badge');
            if (badge) badge.textContent = letter;
            const input = row.querySelector('input[type="text"]');
            if (input && !input.value) {
                input.placeholder = `Treść odpowiedzi ${letter}`;
            }
        });
    }

    function reindexQuestions() {
        const cards = document.querySelectorAll('.q-builder-card');
        cards.forEach((card, i) => {
            const title = card.querySelector('.q-builder-title');
            if (title) title.textContent = `Pytanie #${i + 1}`;
        });
    }

    // Załaduj pierwsze pytanie na start
    document.addEventListener('DOMContentLoaded', () => {
        addQuestion();
    });
    </script>
</body>
</html>