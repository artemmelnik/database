# Class Database (PDO)

1. Install
 ```shell
  composer require artemmelnik/devstart-db
  ```
  
2. Use

 ```shell
  <?php
  
  require_once __DIR__ . '/vendor/autoload.php';
  
  use DevStart\Database;
  
  $db = new Database([
     'host' => 'localhost',
     'dbname' => 'devstart_db',
     'user' => 'root',
     'password' => 'root',
     'charset' => 'utf8'
  ]);
  
  $query = $db->query('SELECT * FROM posts ORDER BY id DESC');
  
  $db->closeConnection();
  
  ?>
  ```