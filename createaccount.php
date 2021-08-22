
<!-- these functions are just setting up the database stuff -->

<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php
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

$pdo = new PDO('sqlite:mydb.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//header

echo "<div class='header'>";
echo    "<div class='title'><img src='twitter.png' alt='Twitter' style='width:80px;height:80px;'></div>";
echo "</div>";


//saving variables from the form, and inserting into database if the criteria for username/password is met

if (isset($_POST['create'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $email = $_POST['email'];

  if (!query('SELECT username FROM users WHERE username=:username', array(':username'=>$username)))
  {
    if (strlen($username) > 0 && strlen($username) < 32)
    {
      if (strlen($password) > 0 && strlen($password) < 32)
      {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
          query('INSERT INTO users VALUES (null, :username, :password, :email)', array(':username'=>$username, ':password'=>password_hash($password, PASSWORD_BCRYPT), ':email'=>$email));
          echo "Success! Your account has been created.";
        }
        else {
          echo 'enter a valid email';
        }
      }
      else {
        echo "make sure your password is not too long";
      }

    }
    else {
      echo 'make sure your username is not too long';
    }

  }
  else
  {
  echo "Username already exists! Try another one.";
  }
}
?>

<!-- this is the form for creating an account -->
<div class="loginpage">
<h2>Register for an account</h2>
<br />
<form class="create-account.php" method="post">
  <input type="text" name="username" value="" placeholder="type username...">
  <input type="password" name="password" value="" placeholder="type password...">
  <input type="email" name="email" value="" placeholder="type email...">
  <input type="submit" name="create" value="Create Account">
</form>
<br></br>
<a href="login.php">Already have an account? Click here to login</a>
</div>
</body>
</html>
