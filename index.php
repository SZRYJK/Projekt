<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <span>Zaloguj sie</span>
    <main>
        <div id="loginModal">
        <form action="index.php" method="post">
        <label for="login">Login:</label>
        <input type="text" name="login" id="login-txt"><br>
        <br>
        <label for="password">Hasło:</label>
        <input type="password" name="password" id="password-txt"><br><br>
        <input type="submit" value="Zaloguj się" id="login-btn">
        </form>
    </div>

    <?php
        if(!empty($_POST["login"]) && !empty( $_POST["password"])) {
            $log = $_POST["login"];
            $pass = $_POST["password"];

            $conn = mysqli_connect("localhost","root","","testy");
            $req = mysqli_query($conn,"SELECT password FROM uzytkownicy Where login = '$log'");
            if(mysqli_num_rows($req) > 0) {
            $has = mysqli_fetch_array($req);
                if($pass == $has[0]) {
                    header("Location:main.php");
                    exit();
                }
                else {
                    header("Location:index.php?error=2");
                    exit();
                }
            }
            else{
                header("Location:index.php?error=1");
                exit();
            }
        }
    ?>
    </main>
    <script>
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('error') === '0'){
        const passwordInput = document.getElementById('login-txt');
        const passwordInputP = document.getElementById('password-txt');
        passwordInput.classList.add('error');
        passwordInputP.classList.add('error');
    }
    if(urlParams.get('error') === '1'){
        const passwordInput = document.getElementById('login-txt');
        passwordInput.classList.add('error');
    }
    if(urlParams.get('error') === '2'){
        const passwordInput = document.getElementById('password-txt');
        passwordInput.classList.add('error');
    }
    setTimeout(() => {
        passwordInput.classList.remove('error');
        passwordInputP.classList.remove('error');
    }, 600);
});

</script>
</body>
</html>