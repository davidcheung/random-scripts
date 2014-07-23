<?



if ( $_POST['export']){

    session_start();
    /* ----------------------------------------------------------------
        HTTP AUTH
     ---------------------------------------------------------------- */
    $valid_passwords = array ("admin" => "davidcheung!!!!");
    $valid_users = array_keys($valid_passwords);

    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];

    $validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

    if (!$validated) {
      header('WWW-Authenticate: Basic realm="My Realm"');
      header('HTTP/1.0 401 Unauthorized');
      die ("Not authorized");
    }


    $benchmarkTimeStar = microtime(1);
    //config file needs to define constants used below
    include_once "../source/includes/configure.php";
    $conn =mysql_pconnect(  DB_SERVER, DB_SERVER_USERNAME,DB_SERVER_PASSWORD ) or die("Unable to connect with server") ;
    mysql_select_db( DB_DATABASE, $conn) or die("Unable to select database");

// ----------------------------------
    function fetch( $query,$conn ){
        $res = mysql_query( $query, $conn );
        $count = 0;
        $data = array();
        while( $row = mysql_fetch_assoc($res) ){
            $data[$count] = $row;
            $count++;
        }
        return $data;
    }
// ----------------------------------
    function export_as_json_string( $data ){
        return json_encode( $data );
    }

// --------------------------------------------------------------------
//      RUN TIME
// --------------------------------------------------------------------
    $data = fetch("SHOW TABLE status",$conn);
    foreach ( $data as $k=>$table ){
        $data[$k]['definition'] = fetch("show full columns from {$table[Name]}",$conn);
    }
    if ( $_POST['auto_save'] ){
        
    }else{
        $filename = $_SERVER['HTTP_HOST']."-".date("Y-m-d",time()).".json";
        header( "Content-Description: File Transfer");
        header ( 'Content-Disposition: attachment; filename="'.$filename.'"' );
        //echo "<textarea>".  (export_as_json_string( $data )) . "</textarea>";
        echo ( export_as_json_string( $data ) );
    }

    // session_destroy(); 
}else{

    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Export</title>
        <!--
            Using bootstrap as a UI framework because `Export` button is important to be styled,
            and button needs to be responsive because its the web 2.0 era
         -->
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.2.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.2.1/css/bootstrap-responsive.min.css">
        <style>
            body{
                padding:10px;
            }
        </style>
    </head>
    <body>
        <form action="" METHOD="POST">
            <input type="hidden" name="export" value="export">
            <input type="submit" value="Export" class="btn">
        </form>
    </body>
    </html>
    <?
}

?>
