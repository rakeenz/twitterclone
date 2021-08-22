<?php
class DB {
  private static function connect() {
    $pdo = new PDO('sqlite:mydb.db');
    
    return $pdo;
  }
  private static function query($query, $parameter = array()){

    $statement = self::connect()->prepare($query);
    $statement->execute($parameter);
    //$data = $statement->fetchAll();
    //return $data;




  }




}




?>
