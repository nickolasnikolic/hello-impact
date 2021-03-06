<?php
require 'vendor/autoload.php';

//for amazon
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;
use ApaiIO\Operations\BrowseNodeLookup;
use ApaiIO\Operations\Lookup;
use ApaiIO\Operations\SimilarityLookup;
use ApaiIO\Operations\CartAdd;
use ApaiIO\Operations\CartCreate;
//for mail
use Mailgun\Mailgun;


error_reporting(-1);//tell me stuff

$app = new \Slim\Slim();

$app->get('/home', function(){

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT lists.id, lists.name, lists.list_owner, users.nickname FROM lists JOIN users ON lists.list_owner = users.userid;');
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );

});

$app->post('/home', function(){

  if( !empty($_POST['username']) && !empty($_POST['password']) ){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $url["host"];
    $user = $url["user"];
    $pass = $url["pass"];
    $database = substr($url["path"], 1);

    $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
    $stmtUserId = $db->prepare('SELECT userid, email FROM users WHERE email = :administrator AND password = :password;');
    $stmtUserId->bindParam( ':administrator', $username );
    $stmtUserId->bindParam( ':password', $password );
    $stmtUserId->execute();
    $resultUserId = $stmtUserId->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultUserId);

  }else{
    echo 0;
  }
});

$app->get('/shared/:userid/:listid', function($userid,$listid){

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE id = :listid LIMIT 1;');

  $stmtClasses->bindParam(':listid', $listid);
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);


  //get the books from that class
  $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
  $stmtBooks->bindParam( ':classid', $listid );
  $stmtBooks->execute();
  $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);

  //then attach them to the appropriate class for this administrator
  $resultClasses['items'] = $resultBooks;

  //return as json
  echo json_encode( $resultClasses );

});

$app->post('/password', function(){
  //get email from post info
  $email = $_POST['email'];
  //get password from db
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtPassword = $db->prepare('SELECT password FROM users WHERE email = :email;');
  $stmtPassword->bindParam( ':email', $email );

  $stmtPassword->execute();
  $resultPassword = $stmtPassword->fetchAll(PDO::FETCH_ASSOC);

  //email password to email given, if exists
  if(isset($resultPassword[0]['password'])) {

    $password = $resultPassword[0]['password'];

    # First, instantiate the SDK with your API credentials and define your domain.
    $msg = new Mailgun(getenv("MAILGUN_API_KEY"));
    $domain = getenv('MAILGUN_DOMAIN');

    # Now, compose and send your message.
    $msg->sendMessage($domain, array(
        'from'    => 'info@quick.plus',
        'to'      => $email,
        'subject' => 'Your quick.plus password request.',
        'text'    => "Your requested password is: \n{$password}"
    ));

    echo 'done';
  }else{
    echo 'nogo';
  }

});

$app->post('/home/register/', function(){

  if( !empty($_POST['email']) && !empty($_POST['password']) ){

    $username = $_POST['email'];
    $password = $_POST['password'];
    $nickname = $_POST['nickname'];


    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $url["host"];
    $user = $url["user"];
    $pass = $url["pass"];
    $database = substr($url["path"], 1);

    $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
    $stmtUserId = $db->prepare('INSERT INTO users ( email, password, nickname ) VALUES (:email, :password, :nickname)');
    $stmtUserId->bindParam( ':email', $username );
    $stmtUserId->bindParam( ':password', $password );
    $stmtUserId->bindParam( ':nickname', $nickname );
    $stmtUserId->execute();

    echo 1; //all is well
  }else{
    echo 0; //everything sucks and I quit...
  }

});

//new user
$app->get('/admin/:administrator', function($administrator){
  //get the administrator

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

//add class
$app->post('/admin/add/class/', function(){
  //for this admin
  //pull the class details from the post
  $administrator = $_POST['admin'];
  $class = $_POST['title'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('INSERT INTO lists (name, list_owner) VALUES (:name, :prof);');
  $stmtUserId->bindParam( ':name', $class );
  $stmtUserId->bindParam( ':prof', $administrator );
  $stmtUserId->execute();

  //return the list of classes with generated ids intact
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

//delete class
$app->post('/admin/remove/class/', function(){
  //for this admin
  //pull the class details from the post
  $id = $_POST['id'];

  //set them in the db
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('DELETE FROM lists WHERE id = :id;');
  $stmtUserId->bindParam( ':id', $id );
  $stmtUserId->execute();

});


$app->post('/admin/add/book/', function(){
  //for this admin
  //pull the class details from the post
  $name = $_POST['title'];

  $address1 = $_POST['address1'];

  if(isset($_POST['address2'])) {
    $address2 = $_POST['address2'];
  }else{
    $address2 = '';
  }

  $city = $_POST['city'];
  $state = $_POST['state'];
  $zipcode = $_POST['zipcode'];

  $country = $_POST['country'];

  $phone = $_POST['phone'];

  $description = $_POST['description'];

  $hours_monday = $_POST['hours_monday'];
  $hours_tuesday = $_POST['hours_tuesday'];
  $hours_wednesday = $_POST['hours_wednesday'];
  $hours_thursday = $_POST['hours_thursday'];
  $hours_friday = $_POST['hours_friday'];
  $hours_saturday = $_POST['hours_saturday'];
  $hours_sunday = $_POST['hours_sunday'];

  $classid = $_POST['class'];
  $administrator = $_POST['admin'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('INSERT INTO items (
                                                title,
                                                address1,
                                                address2,

                                                city,
                                                state,
                                                zipcode,

                                                country,

                                                phone,

                                                description,
                                                hours_monday,
                                                hours_tuesday,
                                                hours_wednesday,
                                                hours_thursday,
                                                hours_friday,
                                                hours_saturday,
                                                hours_sunday,
                                                list
                                                ) VALUES (
                                                :title,
                                                :address1,
                                                :address2,

                                                :city,
                                                :state,
                                                :zipcode,

                                                :country,

                                                :phone,

                                                :description,
                                                :hours_monday,
                                                :hours_tuesday,
                                                :hours_wednesday,
                                                :hours_thursday,
                                                :hours_friday,
                                                :hours_saturday,
                                                :hours_sunday,
                                                :class
                                                );');
  $stmtUserId->bindParam( ':title', $name );
  $stmtUserId->bindParam( ':address1', $address1 );
  $stmtUserId->bindParam( ':address2', $address2 );

  $stmtUserId->bindParam( ':city', $city );
  $stmtUserId->bindParam( ':state', $state  );
  $stmtUserId->bindParam( ':zipcode', $zipcode );

  $stmtUserId->bindParam( ':country', $country );

  $stmtUserId->bindParam( ':phone', $phone );

  $stmtUserId->bindParam( ':description', $description  );

  $stmtUserId->bindParam( ':hours_monday', $hours_monday );
  $stmtUserId->bindParam( ':hours_tuesday', $hours_tuesday );
  $stmtUserId->bindParam( ':hours_wednesday', $hours_wednesday );
  $stmtUserId->bindParam( ':hours_thursday', $hours_thursday );
  $stmtUserId->bindParam( ':hours_friday', $hours_friday );
  $stmtUserId->bindParam( ':hours_saturday', $hours_saturday  );
  $stmtUserId->bindParam( ':hours_sunday', $hours_sunday  );
  $stmtUserId->bindParam( ':class', $classid );
  $stmtUserId->execute();

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

$app->post('/admin/update/book/', function(){
  //for this admin
  //pull the class details from the post
  $name = $_POST['title'];

  $address1 = $_POST['address1'];

  if(isset($_POST['address2'])) {
    $address2 = $_POST['address2'];
  }else{
    $address2 = '';
  }

  $city = $_POST['city'];
  $state = $_POST['state'];
  $zipcode = $_POST['zipcode'];

  $country = $_POST['country'];

  $phone = $_POST['phone'];

  $description = $_POST['description'];

  $hours_monday = $_POST['hours_monday'];
  $hours_tuesday = $_POST['hours_tuesday'];
  $hours_wednesday = $_POST['hours_wednesday'];
  $hours_thursday = $_POST['hours_thursday'];
  $hours_friday = $_POST['hours_friday'];
  $hours_saturday = $_POST['hours_saturday'];
  $hours_sunday = $_POST['hours_sunday'];

  $bookid = $_POST['bookid'];

  $administrator = $_POST['admin'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('UPDATE
                                items
                              SET
                                title = :title,
                                address1 = :address1,
                                address2 = :address2,

                                city = :city,
                                state = :state,
                                zipcode = :zipcode,

                                country = :country,

                                phone = :phone,

                                description = :description,
                                hours_monday = :hours_monday,
                                hours_tuesday = :hours_tuesday,
                                hours_wednesday = :hours_wednesday,
                                hours_thursday = :hours_thursday,
                                hours_friday = :hours_friday,
                                hours_saturday = :hours_saturday,
                                hours_sunday = :hours_sunday
                              WHERE
                                id = :bookid;');

  $stmtUserId->bindParam( ':bookid', $bookid );

  $stmtUserId->bindParam( ':title', $name );
  $stmtUserId->bindParam( ':address1', $address1 );
  $stmtUserId->bindParam( ':address2', $address2 );

  $stmtUserId->bindParam( ':city', $city );
  $stmtUserId->bindParam( ':state', $state  );
  $stmtUserId->bindParam( ':zipcode', $zipcode );

  $stmtUserId->bindParam( ':country', $country );

  $stmtUserId->bindParam( ':phone', $phone );

  $stmtUserId->bindParam( ':description', $description  );

  $stmtUserId->bindParam( ':hours_monday', $hours_monday );
  $stmtUserId->bindParam( ':hours_tuesday', $hours_tuesday );
  $stmtUserId->bindParam( ':hours_wednesday', $hours_wednesday );
  $stmtUserId->bindParam( ':hours_thursday', $hours_thursday );
  $stmtUserId->bindParam( ':hours_friday', $hours_friday );
  $stmtUserId->bindParam( ':hours_saturday', $hours_saturday  );
  $stmtUserId->bindParam( ':hours_sunday', $hours_sunday  );

  $stmtUserId->execute();

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

//delete book
$app->post('/admin/remove/book/', function(){
  //for this admin
  //pull the class details from the post
  $bookid = $_POST['bookid'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('DELETE FROM items WHERE id = :bookid;');

  $stmtUserId->bindParam( ':bookid', $bookid );

  $stmtUserId->execute();

});

$app->post('/contact', function(){
  //send message in content
  $message = $_POST['name'];
  $message .= $_POST['phone'];
  $message .= $_POST['message'];

  echo $message;
});

$app->run();
