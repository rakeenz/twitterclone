
<!-- reference for this code: https://www.youtube.com/watch?v=15hVqug7bjM&list=PLBOh8f9FoHHhRk0Fyus5MMeBsQ_qwlAzG&index=10 -->

<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php session_start();

error_reporting(E_ALL ^ E_WARNING);

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
echo    "<div class='title'><a href='home.php'><img src='twitter.png' alt='Twitter' style='width:80px;height:80px;'></a></div>";
echo "<div class='yourpage'><br /></div><br />";
echo "<div class='yourpage'>You are logged in as " . $_SESSION['use'] . ". <a href='logout.php'>Logout</a></div><br />";


echo "</div>";




//initializing username variable
$user = "";

//this checks the profile you are on to see if it is you or someone else
if (isset($_GET['username']))
{
  if (query('SELECT username FROM users WHERE username=:username', array(':username'=>$_GET['username']))){

    //sets follower equal to you, and username equal to whoevers profile you are on
    $user = query('SELECT username FROM users WHERE username=:username', array(':username'=>$_GET['username']))[0]['username'];
    $follower = $_SESSION['use'];
    $blocker = $_SESSION['use'];
    $username = $user;

    //if page you are on is not yours, it will let you follow

    if (isset($_POST['follow']))
    {
      if (!query('SELECT follower FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower))){
        query('INSERT INTO followers VALUES (:user, :follower)', array(':user'=>$user, 'follower'=>$follower));
      }
      else {
        echo "you are following this person";
      }
      $following = True;
    }
    if (isset($_POST['unfollow']))
    {
      if (query('SELECT follower FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower))){
        query('DELETE FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower));
      }
      $following = False;
    }

    if (query('SELECT follower FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower))){
      $following = True;
    }

    //block functionality

    if (isset($_POST['unblock']))
    {
      if (query('SELECT blocker FROM blockers WHERE user=:username AND blocker=:blocker', array(':username'=>$user, 'blocker'=>$blocker))){
        query('DELETE FROM blockers WHERE user=:username AND blocker=:blocker', array(':username'=>$user, 'blocker'=>$blocker));
      }
      $blocking = False;
    }
    if (isset($_POST['block']))
    {
      if (query('SELECT follower FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower))){
        query('DELETE FROM followers WHERE user=:username AND follower=:follower', array(':username'=>$user, 'follower'=>$follower));
      }
      $following = False;
      if (!query('SELECT blocker FROM blockers WHERE user=:username AND blocker=:blocker', array(':username'=>$user, 'blocker'=>$blocker))){
        query('INSERT INTO blockers VALUES (:user, :blocker)', array(':user'=>$user, 'blocker'=>$blocker));
      }

      $blocking = True;
    }
    if (query('SELECT blocker FROM blockers WHERE user=:username AND blocker=:blocker', array(':username'=>$user, 'blocker'=>$blocker))){
      $blocking = True;
    }

  //for posting tweets, kinda straightforward


  $post = "";
  if (isset($_POST['tweet'])){
    $tweet = $_POST['post'];
    $profileuser = $_SESSION['use'];

    if (strlen($tweet) < 150 && strlen($tweet) >= 1)
    {
      query('INSERT INTO posts VALUES (null, :user, :post, datetime(), 0, 0, null)', array(':user'=>$profileuser, ':post'=>$tweet));
    }
    else {
      echo "post too small or too big";
    }


  }
// like functionality
  if (isset($_POST['like']))
  {
    if (!query('SELECT user FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
    {
      query('UPDATE posts SET likes=likes+1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
      query('INSERT INTO postlikes VALUES (:postid, :user)', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
    }
  }
   if (isset($_POST['unlike']))
   {
    {
      query('UPDATE posts SET likes=likes-1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
      query('DELETE FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
    }
    }

  }
//delete functionality
  if (isset($_POST['delete']))
  {
    if (query('SELECT postid FROM posts WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
    {
      query('DELETE FROM posts WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
      query('DELETE FROM postlikes WHERE postid=:postid', array(':postid'=>$_GET['postid']));
    }


  }
//retweet functionality
  if (isset($_POST['retweet']))
  {
    if (!query('SELECT user FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
    {
      $selectedpost = query('SELECT * FROM posts WHERE postid=:postid', array(':postid'=>$_GET['postid']));
      $p = "";
      foreach($selectedpost as $t)
      {
         $p .= "<div style='opacity:0.5;'> Retweeted tweet from: "."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a> </div>"."&nbsp;&nbsp;&nbsp;&nbsp;".$t['body'];
         query('INSERT INTO posts VALUES (null, :user, :post, datetime(), 0, 0, :retweetpostid)', array(':user'=>$_SESSION['use'], ':post'=>$p, ':retweetpostid'=>$t['postid']));
         query('UPDATE posts SET retweets=retweets+1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
         query('INSERT INTO postretweets VALUES (:postid, :user)', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
      }

    }
  }
  if (isset($_POST['unretweet']))
  {
    if (query('SELECT user FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
    {
      query('UPDATE posts SET retweets=retweets-1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
      query('DELETE FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
      query('DELETE FROM posts WHERE retweetpostid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
    }
  }

//this is what makes the posts show up, basically the same as the code in home.php
  $sentposts = query('SELECT * FROM posts WHERE user=:user ORDER BY time DESC', array(':user'=>$user));
  $post = "";
  foreach($sentposts as $t){

    if (query('SELECT user FROM posts WHERE user=:user AND postid=:postid', array('user'=>$_SESSION['use'], ':postid'=>$t['postid'])))
    {
      if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
      {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='like' value='Like❤'>
        </form>
        "."retweets:".$t['retweets']."
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='delete' value='Delete'>
        </form>
        </div>
        <br></br><hr /></br />";
      }
      else {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='unlike' value='Unlike'>
        </form>
        "."retweets:".$t['retweets']."

        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='delete' value='Delete'>
        </form>

        </div>
        <br></br><hr /></br />";
      }
    }
    else if (!query('SELECT postid FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
    {
      if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
      {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='like' value='Like❤'>
        </form>
        "."retweets:".$t['retweets']."
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='retweet' value='Retweet🔄'>
        </form>
        </div>
        <br></br><hr /></br />";
      }
      else {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='unlike' value='Unlike'>
        </form>
        "."retweets:".$t['retweets']."
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='retweet' value='Retweet🔄'>
        </form>
        </div>
        <br></br><hr /></br />";
      }
    }
    else
    {
      if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
      {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='like' value='Like❤'>
        </form>
        "."retweets:".$t['retweets']."
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='unretweet' value='Unretweet'>
        </form>
        </div>
        <br></br><hr /></br />";
      }
      else {
        $post .= $t['body']."</br /></br />".$t['time']."<br></br>"."likes:".$t['likes']."
        <div class='likebutton' style='display: inline;'>
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='unlike' value='Unlike'>
        </form>
        "."retweets:".$t['retweets']."
        <form action='profile.php?username=$user&postid=".$t['postid']."' method='post' style='display: inline;'>
          <input type='submit' name='unretweet' value='Unretweet'>
        </form>
        </div>
        <br></br><hr /></br />";
      }
    }




  }

}



?>

<!-- counts followers -->
<br />
<div class= "profilename">
<h1> <?php echo $user; ?>'s profile</h1>
<?php $count = query('SELECT COUNT(follower) as nFollowers FROM followers WHERE user=:username', array(':username'=>$username)); ?>
<h3>Follower count: <?php echo $count[0]["nFollowers"]; ?></h3><br />
</div>

<!-- form for follow/unfollow button -->
<div class="margins">
<form action="profile.php?username=<?php echo $user; ?>" method="post">
  <?php
    if($follower != $username)
    {
      if ($following == True && $blocking == false) {
        echo '<input type="submit" name="unfollow" value ="unfollow">';
      }
      else if ($following == false && $blocking == false)
      {
        echo '<input type="submit" name="follow" value ="follow">';
      }
      else
      {
        echo '';
      }
    }

   ?>
</form>
<br />
<div class="block">
<form action="profile.php?username=<?php echo $user; ?>" method="post">
  <?php
    if($blocker != $username)
    {
      if ($blocking) {
        echo '<input type="submit" name="unblock" value ="unblock">';
      }
      else {
        echo '<input type="submit" name="block" value ="block">';
      }
    }

   ?>
</form>
</div>
</div>
<br />
<!-- form for posting tweets -->

<div class="margins">
<form action="profile.php?username=<?php echo $user; ?>" method="post">
  <?php
  if($follower == $username)
  {
    echo "<textarea name='post' rows='10' cols='100'></textarea><br />";
    echo "<input type='submit' name='tweet' value='Post a tweet'><br></br>";
  }

  ?>
</form>
</div>



<div class= "margins">
<div class="posts">
  <?php echo $post; ?>
</div>
</div>
<script>
function dark() {
  var element = document.body;
  element.classList.toggle("darkmode");

}
</script>
<br /><br />
<button onclick="dark()">Change Theme</button>
<br /><br />


</body>
</html>
