<?
@extract($_POST);
if ( $action == 'convert' && isset($_FILES) ){

    $path = $_FILES['file']['tmp_name'];
    $type = $_FILES['file']['type'];
    $size = $_FILES['file']['size'];
    //
    $file = fopen ( $path, 'r' );
    $data = fread( $file, $size );
    $base64 = 'data:' . $type . ';base64,' . base64_encode($data);
    echo $base64;
}
else{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Image to Base64 converter</title>
    </head>
    <body>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="convert">
            <input type="file" name="file">
            <input type="submit">
        </form>
    </body>
    </html>
    <?
}
?>
