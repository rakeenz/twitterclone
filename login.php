
<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php session_start();

function connect() {
  $pdo = new PDO('sqlite:mydb.db');

  return $pdo;
}
function query($query, $parameter = array()){

  $statement = connect()->prepare($query);
  $statement->execute($parameter);

  if(explode(' ', $query)[0] == 'SELECT') {
  $data = $statement->fetchAll();
  return $data;
  }
}

//header
echo "<div class='header'>";
echo    "<div class='title'><img src='twitter.png' alt='Twitter' style='width:80px;height:80px;'></div>";
echo "</div>";

//if you are logged in, this takes you straight to the home page

if(isset($_SESSION['use'])){
  header("Location:home.php");
}

//saves variables from form, if the login info is correct it will log you in
if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

    if (query('SELECT username FROM users WHERE username=:username', array(':username'=>$username))){
      if (password_verify($password, query('SELECT password FROM users WHERE username=:username', array(':username'=>$username))[0]['password'])) {
        $_SESSION['use'] = $username;
        if(isset($_SESSION['use'])){
          header("Location:home.php");
        }
      }
      else{
        echo "<div class='errorflag'>Incorrect Password!</div>";
      }
    }
    else {
      echo "<div class='errorflag'>User not found!</div>";
    }

}

?>

<!-- form for logging in -->
<div class="loginpage">
<h2>Login to your account</h2>
<br />
<form action="login.php" method="post">
<input type="text" name="username" value="" placeholder="type username...">
<input type="password" name="password" value="" placeholder="type password...">
<input type="submit" name="login" value="Login">
</form>
<br></br><a href="createaccount.php">Don't have an account? Click here to create one</a>
</div>


</body>
</html>
