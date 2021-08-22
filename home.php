<!DOCTYPE html>
<html>
<link rel="stylesheet" href="style.css">
</head>
<body>


<?php session_start();

//the "session" thing is what lets you log in/stay logged in

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

//header for website with a bunch of divs for the css file
echo "<div class='header'>";
echo "<div class='header2'>";
echo "<div class='title'><img src='twitter.png' alt='Twitter' style='width:80px;height:80px;'></div>";
echo "<div class='yourpage'><br /></div><br />";
echo "<div class='loggedinas'>";
echo "<div class='yourpage'>You are logged in as " . $_SESSION['use'] . ".</div><br />";
echo "</div>";
echo "<div class='yourpage'><a href='profile.php?username=" .$_SESSION['use']. "'>Your Page</a></div><br />";
echo "<div class='yourpage'><a href='logout.php'>Logout</a></div><br />";
echo "</div>";
echo "</div>";

 ?>

<!-- form for searching for a user -->
  <br></br>
<div class="margins">
 <h3>Search for a user</h3>
</div>
 <?php
 echo "<div class='margins'>";
 echo "<form action='home.php' method='post'>";
 echo "<input type='text' name='username'>";
 echo "<input type='submit' name='search' value='Search'>";
 echo "</form>";
 echo "</div";
 echo "<br></br>";

 if (isset($_POST['search'])) {
   $user = $_POST['username'];
   if (query('SELECT username FROM users WHERE username=:username', array(':username'=>$user))){
     $profile = "Location:profile.php?username=";
     $profile .= $user;
     if ($user != null){
       header($profile);
     }
   }
   else {
     echo "user does not exist";
   }
 }

//tweet functionality
 if (isset($_POST['tweet'])){
   $tweet = $_POST['post'];
   $profileuser = $_SESSION['use'];

   if (strlen($tweet) < 150 && strlen($tweet) >= 1)
   {
     query('INSERT INTO posts VALUES (null, :user, :post, datetime(), 0, 0, null)', array(':user'=>$profileuser, ':post'=>$tweet));
     header("Location:home.php");
   }
   else {
     echo "post too small or too big";
   }

 }

//like functionality
 if (isset($_POST['like']))
 {
   if (!query('SELECT user FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
   {
     query('UPDATE posts SET likes=likes+1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
     query('INSERT INTO postlikes VALUES (:postid, :user)', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
     header("Location:home.php");
   }
 }
  if (isset($_POST['unlike']))
  {
   {
     query('UPDATE posts SET likes=likes-1 WHERE postid=:postid', array(':postid'=>$_GET['postid']));
     query('DELETE FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
     header("Location:home.php");
   }
   }

//delete functionality
   if (isset($_POST['delete']))
   {
     if (query('SELECT postid FROM posts WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use'])))
     {
       query('DELETE FROM posts WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
       query('DELETE FROM postlikes WHERE postid=:postid', array(':postid'=>$_GET['postid']));
       query('DELETE FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$_GET['postid'], 'user'=>$_SESSION['use']));
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

//this is what makes the tweets show up
$posts = query('SELECT DISTINCT posts.body, posts.time, posts.user, posts.likes, posts.retweets, posts.postid FROM posts, followers WHERE (posts.user=followers.user AND followers.follower=:user) OR (posts.user=:user) ORDER BY time DESC', array(':user'=>$_SESSION['use']));
$post = "";
foreach($posts as $t){
  $userlink = $t['user'];
  $user = $_SESSION['use'];

  //if the tweet is your own (lets you like/unlike + delete)
  if (query('SELECT user FROM posts WHERE user=:user AND postid=:postid', array('user'=>$_SESSION['use'], ':postid'=>$t['postid'])))
  {
    if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
    {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='like' value='Like❤'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='delete' value='Delete'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
    else {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='unlike' value='Unlike'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='delete' value='Delete'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
  }
  //if the post is not your own and has not been retweeted yet (needs to be like this because of the like functionality)
  else if (!query('SELECT postid FROM postretweets WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
  {
    if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
    {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='like' value='Like❤'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='retweet' value='Retweet🔄'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
    else {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='unlike' value='Unlike'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='retweet' value='Retweet🔄'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
  }
  //if the tweet is not your own and has been retweeted
  else
  {
    if (!query('SELECT postid FROM postlikes WHERE postid=:postid AND user=:user', array(':postid'=>$t['postid'], 'user'=>$_SESSION['use'])))
    {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='like' value='Like❤'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='unretweet' value='Unretweet'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
    else {
      $post .= $t['body']."</br /></br />"."<a href='profile.php?username=".$t['user']."'>".$t['user']."</a>"." ".$t['time']."&nbsp;&nbsp;&nbsp;&nbsp;"."likes:".$t['likes']."
      <div class='likebutton' style='display: inline;'>
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='unlike' value='Unlike'>
      </form>
      "."retweets:".$t['retweets']."
      <form action='home.php?&postid=".$t['postid']."' method='post' style='display: inline;'>
        <input type='submit' name='unretweet' value='Unretweet'>
      </form>
      </div>
      <br></br><hr /></br />";
    }
  }

 }

//all the forms and stuff (dark mode at the bottom)
 ?>

 <form action="home.php" method="post">

     <textarea name='post' rows='10' cols='100'></textarea><br />
     <input type='submit' name='tweet' value='Post a tweet'>
     <br></br>

 </form>

 <div class="posts">
   <div class="tweetsheader">
     <h1>Tweets<br></br></h1>
   </div>
   <?php echo $post; ?>
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
