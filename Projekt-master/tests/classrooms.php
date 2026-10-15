<?php
// Run with PHP CLI against a running local XAMPP instance. Fixtures are removed.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once dirname(__DIR__) . '/classroom.php';

$base_url = rtrim($argv[1] ?? 'http://localhost/Projekt-master', '/');
$prefix = 'classroom_check_' . bin2hex(random_bytes(6));
$user_ids = [];
$test_id = 0;
$checks = 0;

function check($condition, $message) {
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "PASS: $message\n";
}

function browser() {
    $handle = curl_init();
    curl_setopt_array($handle, [
        CURLOPT_COOKIEFILE => '', CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15,
    ]);
    return $handle;
}

function request($browser, $path, $post = null) {
    global $base_url;
    curl_setopt($browser, CURLOPT_URL, $base_url . '/' . $path);
    if ($post === null) {
        curl_setopt($browser, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($browser, CURLOPT_POST, true);
        curl_setopt($browser, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($browser);
    if ($body === false) {
        throw new RuntimeException(curl_error($browser));
    }
    return ['body' => $body, 'status' => curl_getinfo($browser, CURLINFO_HTTP_CODE),
            'url' => curl_getinfo($browser, CURLINFO_EFFECTIVE_URL)];
}

function csrf($page) {
    if (!preg_match('/name="csrf" value="([a-f0-9]+)"/', $page['body'], $matches)) {
        throw new RuntimeException('Missing CSRF token');
    }
    return $matches[1];
}

function create_class($browser, $name) {
    $page = request($browser, 'classes.php');
    $page = request($browser, 'classes.php', ['action' => 'create_class', 'nazwa' => $name, 'csrf' => csrf($page)]);
    if (!preg_match('/classes.php\?id=(\d+)/', $page['url'], $matches)) {
        throw new RuntimeException('Class creation failed');
    }
    return (int)$matches[1];
}

function change_class($browser, $class_id, $action, $fields = []) {
    $page = request($browser, 'classes.php?id=' . $class_id);
    return request($browser, 'classes.php?id=' . $class_id,
        array_merge(['action' => $action, 'csrf' => csrf($page)], $fields));
}

try {
    foreach (['teacher_a' => 1, 'teacher_b' => 1, 'student_a' => 0, 'student_b' => 0] as $suffix => $role) {
        $login = $prefix . '_' . $suffix;
        $password = 'fixture-password';
        $stmt = mysqli_prepare($conn, 'INSERT INTO uzytkownicy (login, password, nauczyciel) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssi', $login, $password, $role);
        mysqli_stmt_execute($stmt);
        $user_ids[$suffix] = (int)mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO test (nazwa, max_pkt) VALUES (?, 10)');
    mysqli_stmt_bind_param($stmt, 's', $prefix);
    mysqli_stmt_execute($stmt);
    $test_id = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    foreach (['student_a', 'student_b'] as $student) {
        $stmt = mysqli_prepare($conn, 'INSERT INTO wyniki (id_testu, id_uzytkownik, ocena) VALUES (?, ?, 7)');
        mysqli_stmt_bind_param($stmt, 'ii', $test_id, $user_ids[$student]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $teacher_a = browser();
    $teacher_b = browser();
    $student = browser();
    $anonymous = browser();
    foreach (['teacher_a' => $teacher_a, 'teacher_b' => $teacher_b, 'student_a' => $student] as $suffix => $handle) {
        $page = request($handle, 'index.php', ['action' => 'login', 'login' => $prefix . '_' . $suffix, 'password' => $password]);
        check($page['status'] === 200 && strpos($page['body'], 'dashboard-page') !== false, "$suffix can log in");
    }
    $page = request($teacher_a, 'main.php');
    check(strpos($page['body'], $prefix . '_student_a') === false && strpos($page['body'], $prefix . '_student_b') === false,
        'Teacher without classes cannot see platform scores');
    $class_a = create_class($teacher_a, 'Class A');
    $class_a2 = create_class($teacher_a, 'Class A2');
    $class_b = create_class($teacher_b, 'Class B');
    $page = change_class($teacher_a, $class_a, 'add_student', ['login' => $prefix . '_student_a']);
    check(strpos($page['body'], 'Uczeń został dodany') !== false, 'Teacher can add an existing student by login');
    change_class($teacher_a, $class_a2, 'add_student', ['login' => $prefix . '_student_a']);
    change_class($teacher_b, $class_b, 'add_student', ['login' => $prefix . '_student_b']);
    $page = request($teacher_a, 'main.php');
    check(substr_count($page['body'], $prefix . '_student_a') === 1, 'Student in two classes has each score shown once');
    check(strpos($page['body'], $prefix . '_student_b') === false, 'Teacher A cannot see teacher B student scores');
    $page = request($teacher_b, 'main.php');
    check(strpos($page['body'], $prefix . '_student_b') !== false && strpos($page['body'], $prefix . '_student_a') === false,
        'Teacher B sees only their own student scores');
    check(count(teacher_scores($conn, $user_ids['teacher_a'], $class_b)) === 0, 'Score query rejects another teacher class');
    $page = request($teacher_a, 'main.php?class_id=' . $class_a);
    check($page['status'] === 200 && strpos($page['body'], $prefix . '_student_a') !== false, 'Owned class filter shows scores');
    check(request($teacher_a, 'main.php?class_id=' . $class_b)['status'] === 404, 'Foreign class result URL is rejected');
    check(request($teacher_a, 'classes.php?id=' . $class_b)['status'] === 404, 'Foreign class roster URL is rejected');
    $token = csrf(request($teacher_a, 'classes.php'));
    foreach (['add_student', 'remove_student', 'delete_class'] as $action) {
        $page = request($teacher_a, 'classes.php?id=' . $class_b, [
            'action' => $action, 'csrf' => $token, 'student_id' => $user_ids['student_b'],
            'login' => $prefix . '_student_a',
        ]);
        check($page['status'] === 404, "Foreign class $action is rejected");
    }
    check(request($teacher_a, 'classes.php', ['action' => 'create_class', 'nazwa' => 'Bad', 'csrf' => 'bad'])['status'] === 403,
        'Class changes reject invalid CSRF token');
    $page = change_class($teacher_a, $class_a, 'add_student', ['login' => $prefix . '_teacher_b']);
    check(strpos($page['body'], 'Nie znaleziono konta ucznia') !== false, 'Teacher accounts cannot be added as students');
    $page = change_class($teacher_a, $class_a, 'add_student', ['login' => $prefix . '_missing']);
    check(strpos($page['body'], 'Nie znaleziono konta ucznia') !== false, 'Unknown student is rejected');
    $page = change_class($teacher_a, $class_a, 'add_student', ['login' => $prefix . '_student_a']);
    check(strpos($page['body'], 'Ten uczeń jest już w klasie') !== false, 'Duplicate membership gives a useful error');
    $page = request($teacher_a, 'classes.php', ['action' => 'create_class', 'nazwa' => 'Class A', 'csrf' => $token]);
    check(strpos($page['body'], 'Masz już klasę o tej nazwie') !== false, 'Duplicate class name gives a useful error');
    $page = request($teacher_a, 'classes.php', ['action' => 'create_class', 'nazwa' => ' ', 'csrf' => $token]);
    check(strpos($page['body'], 'Podaj nazwę klasy') !== false, 'Blank class name is rejected');
    $page = request($student, 'classes.php');
    check(strpos($page['url'], 'main.php') !== false && strpos($page['body'], 'classroom-roster') === false, 'Student cannot manage classrooms');
    $page = request($student, 'classes.php', ['action' => 'create_class', 'nazwa' => 'Student class', 'csrf' => $token]);
    check(strpos($page['url'], 'main.php') !== false, 'Student POST cannot create classrooms');
    $page = request($anonymous, 'classes.php');
    check(strpos($page['url'], 'index.php') !== false, 'Anonymous user must log in');

    change_class($teacher_a, $class_a, 'remove_student', ['student_id' => $user_ids['student_a']]);
    check(count(teacher_scores($conn, $user_ids['teacher_a'])) === 1, 'Membership in another owned class preserves visibility');
    check(count(teacher_scores($conn, $user_ids['teacher_a'], $class_a)) === 0, 'Empty class filter does not leak other class scores');
    change_class($teacher_a, $class_a2, 'remove_student', ['student_id' => $user_ids['student_a']]);
    $page = request($teacher_a, 'main.php');
    check(strpos($page['body'], $prefix . '_student_a') === false, 'Removing last membership revokes teacher access to scores');
    $page = request($student, 'main.php');
    check(strpos($page['body'], 'Twoje ostatnie wyniki') !== false && substr_count($page['body'], htmlspecialchars($prefix)) >= 2,
        'Student retains their personal score history');
    change_class($teacher_b, $class_b, 'delete_class');
    check(count(teacher_scores($conn, $user_ids['teacher_b'])) === 0, 'Deleting class revokes teacher access to its scores');
    $result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM wyniki WHERE id_testu = ' . $test_id);
    check((int)mysqli_fetch_assoc($result)['total'] === 2, 'Removing memberships and classes preserves stored scores');
    $escaped_name = '<script>alert(1)</script>';
    $escaped_class = create_class($teacher_a, $escaped_name);
    $page = request($teacher_a, 'classes.php?id=' . $escaped_class);
    check(strpos($page['body'], htmlspecialchars($escaped_name)) !== false && strpos($page['body'], $escaped_name) === false,
        'Class names are HTML escaped');
    echo "\n$checks checks passed.\n";
} finally {
    foreach ($user_ids as $id) {
        mysqli_query($conn, 'DELETE FROM uzytkownicy WHERE id = ' . (int)$id);
    }
    if ($test_id) {
        mysqli_query($conn, 'DELETE FROM test WHERE id = ' . (int)$test_id);
    }
    echo "Temporary test accounts, classes and scores removed.\n";
}
