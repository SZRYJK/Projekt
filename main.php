<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wykonywanie testów</title>
</head>
<body>
    <?php 
    $conn = mysqli_connect("127.0.0.1","root","","testy");
    mysqli_set_charset($conn, "utf8mb4");

    ?>
    <header>
    <h2>Strona do rozwiązywania testów</h2>
    <a href="logout.php"><img src="logout.png" alt="logout">Wyloguj się</a>
    </header>
    <main>
        <?php 
        $sql = "Select id, nazwa from test";
        $table = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($table)) {
        ?>
            <a class="przycisk" href="testy.php?id=<?php echo $row['id'];?>"><?php echo $row['nazwa'];?></a>
            <br><br>
            <?php
        }
        mysqli_close($conn);?>
    </main>
    <footer></footer>
</body>
</html>