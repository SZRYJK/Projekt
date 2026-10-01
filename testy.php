<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Wykonywanie testów</title>
</head>
<body>
    <header><a href="main.php">Powrót do wyboru testów</a></header>
    <main>
        <?php 
        $id = (int) $_GET["id"];
        $conn = new mysqli('localhost', 'root', '', 'Testy');
        $conn->set_charset("utf8mb4");

        $sql = "SELECT * FROM test WHERE id = '$id'";
        $table = $conn->query($sql);
        $row = $table->fetch_assoc();
        
        if ($row) {
            echo "<h2>{$row['nazwa']}</h2><p>{$row['opis']}</p>";
            echo "<form action='wynik.php' method='POST'>";
            echo "<input type='hidden' name='id_testu' value='$id'>";
        } else {
            echo "Brak testu o podanym ID.";
        }

        $sql1 = "SELECT * FROM pytania WHERE id_testu = $id";
        $table1 = $conn->query($sql1);
        
        while ($pyt = $table1->fetch_assoc()) {
            $pytania = "SELECT id_odp, tresc, literka FROM odpowiedzi WHERE id_pytania = {$pyt['id']}";
            $odp = $conn->query($pytania);
            
            echo "<h3>{$pyt['tresc']}</h3>";
            
            while ($p = $odp->fetch_assoc()) {
                // ZMIANA: type='checkbox' oraz nazwa jako tablica name='odp[id_pytania][]' z wartością id_odp
                echo "<label><input type='checkbox' name='odp[{$pyt['id']}][]' value='{$p['id_odp']}'> {$p['literka']}) {$p['tresc']}</label><br>"; 
            }
        }
        
        if ($row) {
            echo "<br><input type='submit' value='Wyślij odpowiedzi'>";
            echo "</form>";
        }
        ?>
    </main>
</body>
</html>     