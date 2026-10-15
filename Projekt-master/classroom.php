<?php
require_once __DIR__ . '/db.php';

function teacher_classes($conn, $teacher_id) {
    $stmt = mysqli_prepare($conn, '
        SELECT k.id, k.nazwa, COUNT(uk.id_ucznia) AS liczba_uczniow
        FROM klasy k
        LEFT JOIN uczniowie_klas uk ON uk.id_klasy = k.id
        WHERE k.id_nauczyciela = ?
        GROUP BY k.id, k.nazwa ORDER BY k.nazwa, k.id
    ');
    mysqli_stmt_bind_param($stmt, 'i', $teacher_id);
    mysqli_stmt_execute($stmt);
    $classes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $classes;
}

function teacher_scores($conn, $teacher_id, $class_id = 0) {
    // EXISTS prevents duplicate scores when a student belongs to several classes.
    $stmt = mysqli_prepare($conn, '
        SELECT w.id, w.ocena, w.data_wykonania, u.login AS uczen,
               t.nazwa AS test_nazwa, t.max_pkt
        FROM wyniki w
        JOIN uzytkownicy u ON w.id_uzytkownik = u.id
        JOIN test t ON w.id_testu = t.id
        WHERE u.nauczyciel = 0 AND EXISTS (
            SELECT 1 FROM uczniowie_klas uk
            JOIN klasy k ON k.id = uk.id_klasy
            WHERE uk.id_ucznia = u.id AND k.id_nauczyciela = ?
              AND (? = 0 OR k.id = ?)
        )
        ORDER BY w.id DESC LIMIT 50
    ');
    mysqli_stmt_bind_param($stmt, 'iii', $teacher_id, $class_id, $class_id);
    mysqli_stmt_execute($stmt);
    $scores = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $scores;
}

function classroom_csrf_token() {
    if (empty($_SESSION['classroom_csrf'])) {
        $_SESSION['classroom_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['classroom_csrf'];
}
