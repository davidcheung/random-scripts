<?  
    include_once "config/database.php";
    //---------------------------------------------
    //      RUN TIME
    DEFINE( DATABASE_JSON_EXPORT_FILE_1 , 'data/current-localhost.json' );
    DEFINE( DATABASE_JSON_EXPORT_FILE_2 , 'data/dataschema-newest.json' );


    if ( $_POST['action'] == "run_query" ){

        extract( $_POST );
        
        $conn =mysql_pconnect(  DATABASE_HOST, DATABASE_USER,DATABASE_PASSWORD ) or die("Unable to connect with server") ;
        mysql_select_db( DATABASE_DATABASE, $conn) or die("Unable to select database");

        mysql_query(  $query );

        if ( $affected_rows = mysql_affected_rows() ){
            $info['result'] = "success";
            $info['data'] = $affected_rows;
        }
        header( "Content-type : application/json");
        echo json_encode( $info);

        exit();
    }
    else if ( $_POST['action'] == "self_export"){
        
        $conn =mysql_pconnect(  DATABASE_HOST, DATABASE_USER,DATABASE_PASSWORD ) or die("Unable to connect with server") ;
        mysql_select_db( DATABASE_DATABASE, $conn) or die("Unable to select database");

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
        $fp = fopen ( DATABASE_JSON_EXPORT_FILE_1 , "w");
        fwrite( $fp, export_as_json_string( $data ) );
        fclose( $fp );
    }

    $benchmarkTimeStar = microtime(1);

    if ( !file_exists(DATABASE_JSON_EXPORT_FILE_1) ){
        //die( "missing " . DATABASE_JSON_EXPORT_FILE_1 );
    }
    if ( !file_exists(DATABASE_JSON_EXPORT_FILE_2) ){
        //die( "missing " . DATABASE_JSON_EXPORT_FILE_2 );
    }

    $database_1 = json_decode( file_get_contents(DATABASE_JSON_EXPORT_FILE_1), true);
    $database_2 = json_decode( file_get_contents(DATABASE_JSON_EXPORT_FILE_2), true);

    //echo "<pre>" . print_r( $database_1 , true) . "</pre>"; 
    $list_of_differences = array();
    $list_of_differences_of_dmb = array();
    if ($database_1)
    foreach ( $database_1 as $k=>$v ){

        if( !($ifExist = searchTwoDimensionalArr( $database_2, "Name", $v['Name'] )) ){
            $database_1[$k]['OnlyExistHere'] = true;
        }
        else{
            //exist in both
            foreach( $v['definition'] as $column_key =>$column ){
                if ( !($columnExist = searchTwoDimensionalArr( $database_2[$ifExist[0]]['definition'], "Field", $column['Field'] ))  ){
                    $database_1[$k]['definition'][$column_key]['OnlyExistHere'] = true;
                    $column['original_table_key'] = $k;
                    $column['original_key'] = $column_key;

                    $list_of_differences[$database_1[$k]['Name']][] = $column;
                }
                else{

                }
            }
            $database_1[$k]['nums_of_rows_differences'] = count($v['definition']) . " - " . count($database_2[$ifExist[0]]['definition']);
        }
    }
    //echo "<pre>" . print_r( $list_of_differences , true) . "</pre>"; exit();
    if ($database_2)
    foreach ( $database_2 as $k=>$v ){
        if( !($ifExist = searchTwoDimensionalArr( $database_1, "Name", $v['Name'] )) ){
            $database_2[$k]['OnlyExistHere'] = true;
        }else{
            foreach( $v['definition'] as $column_key =>$column ){
                if ( !($columnExist = searchTwoDimensionalArr( $database_1[$ifExist[0]]['definition'], "Field", $column['Field'] ))  ){
                    $database_2[$k]['definition'][$column_key]['OnlyExistHere'] = true;
                    $column['original_key'] = $column_key;
                    $column['original_table_key'] = $k;
                    $list_of_differences_of_dmb[$database_2[$k]['Name']][] = $column;
                }
                else{

                }
            }
            $database_2[$k]['nums_of_rows_differences'] = count($v['definition']) . " - " . count($database_1[$ifExist[0]]['definition']);
        }

    }






    //?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Sync</title>
        <link href='http://fonts.googleapis.com/css?family=Limelight' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.2.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.2.1/css/bootstrap-responsive.min.css">
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
        <style>
        body, .btn{
            font-family: 'Limelight', cursive;
            font-size:0.9em;
        }
        .btn{
            font-weight:bold;font-variant : small-caps;
        }
        #refetch-btn{
            position:fixed; top: 10px;left:10px;margin: 10px; z-index:1;
        }
        .containment{
            position: relative;
            float:left;
            border:1px dashed #ddd;
            padding:5px;
        }
        .extra{
            background-color:lightgreen;
        }
        .table_of_tables{
            margin:10px;
            border:1px solid #c3c3c3;
        }
        .query{background:#f3f3f3;font-size:0.8em;}
        .tablename{
            background-color:lightblue;
        }
        </style>
        
        <script>
            function runQuery( query ){
                if ( confirm( 'Run "'+query+'"?' ) ){
                    run();
                }
                function run(){
                    $.ajax({
                        data: {
                            action : 'run_query',
                            query : query
                        },
                        type: "POST",
                        success:function(res){
                            console.log( 'sent ');
                            refetchDataSchema();
                        }
                    });
                }
            }

            function refetchDataSchema(){
                $.ajax({
                    beforeSend:function(){
                        $("#refetch-btn").attr('disabled','disabled').text('Fetching...');
                    },
                    data: {
                        action : 'self_export'
                    },
                    type: "POST",
                    success:function(res){
                        $("#refetch-btn").removeAttr('disabled').text('Refetch Local Schema');
                        //$("#refetch-btn").reset().button('Refetch Schema');
                        console.log( 'refetched dataschema ');
                    }
                });
            }
        </script>
    </head>
    <body>
        <button id="refetch-btn" data-loading-text="Refetching schema ..." class="btn btn-success"  onclick="refetchDataSchema();">Refetch Local Schema</button>
        <!-- List of tables Localhost has  -->
        <div class="containment">
            <legend>List of tables Database 1 have</legend>
            <sub><?=DATABASE_JSON_EXPORT_FILE_1?></sub>

            <table border="1" class="table_of_tables" cellspacing="0">
            <? //---------------------------------------------
            if ( $database_1 )
            foreach( $database_1 as $k=>$v ){
                ?>
                <tr>
                    <td class="<?=($v['OnlyExistHere']?"extra":"")?>"><?=$v['Name']?></td>
                    <td><?=$v['nums_of_rows_differences']?></td>
                </tr>
                <?
            }
            //---------------------------------------------
            ?>
            </table>
        </div>
    

        <!-- List of tables DMB has  -->
        <div class="containment">
            <legend>List of tables Database2 has</legend>
            <sub><?=DATABASE_JSON_EXPORT_FILE_2?></sub>
            <table border="1" class="table_of_tables" cellspacing="0">
            <? //---------------------------------------------

            foreach( $database_2 as $k=>$v ){
                ?>
                <tr>
                    <td class="<?=($v['OnlyExistHere']?"extra":"")?>"><?=$v['Name']?></td>
                    <td><?=$v['nums_of_rows_differences']?></td>
                </tr>
                <?
            }
            //---------------------------------------------
            ?>
            </table>
        </div>

        <!-- List of field Database1 has extra -->
        <div class="containment">
        <legend>List of field Database1 has extra</legend>
        <sub><?=DATABASE_JSON_EXPORT_FILE_1?></sub>
        <table style="width:400px;" border="1" class="table_of_tables" cellspacing="0">
        <? //---------------------------------------------
            foreach( $list_of_differences as $table_name=>$arr_of_fields ){
                ?>
                <tr>
                    <th colspan="2" class="tablename"><?=$table_name?></th>
                </tr>
                <?
                foreach ( $arr_of_fields as $fieldKey=>$eachField){
                    ?>
                    <tr>
                        <td width="20%" style="padding:5px;text-align:center;">
                            <button disabled class="btn btn-warning" onclick="runQuery('<?=addslashes(create_field($table_name, $eachField, 1 ))?>')">Run</button>
                        </td>
                        <td><?=$eachField['Field']?></td>
                    </tr>
                    <tr>
                        <td class="query" colspan="2">
                            <span><?=create_field($table_name, $eachField, 1)?></span>
                        </td>
                    </tr>
                    <?
                }
            }
            //---------------------------------------------
        ?>
        </table>
        </div>

        <!-- List of field Database1 has extra -->
        <div class="containment">
        <legend>List of field Database2 has extra</legend>
        <sub><?=DATABASE_JSON_EXPORT_FILE_2?></sub>
        <table style="width:400px;" border="1" class="table_of_tables" cellspacing="0">
        <? //---------------------------------------------
            foreach( $list_of_differences_of_dmb as $table_name=>$arr_of_fields ){
                ?>
                <tr>
                    <th colspan="2" class="tablename"><?=$table_name?></th>
                </tr>
                <?
                foreach ( $arr_of_fields as $fieldKey=>$eachField){
                    ?>
                    <tr>
                        <td width="20%" style="padding:5px;text-align:center;">
                            <button class="btn btn-warning" onclick="runQuery('<?=addslashes(create_field($table_name, $eachField, 2))?>')">Run</button>
                        </td>
                        <td><?=$eachField['Field']?></td>
                    </tr>
                    <tr>
                        <td class="query" colspan="2">
                            <span><?=create_field($table_name, $eachField, 2)?></span>
                        </td>
                    </tr>
                    <?
                }
            }
            //---------------------------------------------
        ?>
        </table>
        </div>
        <? $benchmarkTimeEnd=microtime(1);$timespan=round(1000*($benchmarkTimeEnd-$benchmarkTimeStar),4)."ms";?>
        <div> Timetaken : <?=$timespan?></div>

    </body>
    </html>
<?php 
function searchTwoDimensionalArr( $arr, $field, $value ){
    $exist = false;
    if ( is_array($arr) ){
        foreach( $arr as $k=>$v){
            if ( $v[$field] == $value ){
                $exist = array(
                    $k
                );
                break;
            }
        }
    }
    return $exist;
}

function create_field( $tablename, $fieldArr, $fromDatabase = 1 ){
    global $database_1,$database_2;
    //echo "<pre>" . print_r( $allFields , true) . "</pre>"; exit();
    if ( $fromDatabase == 1){
        $after = $database_1[$fieldArr['original_table_key']]['definition'][($fieldArr['original_key']-1)]['Field'];
    }else{
        $after = $database_2[$fieldArr['original_table_key']]['definition'][($fieldArr['original_key']-1)]['Field'];
    }
    if ( $fieldArr['Default']  == "CURRENT_TIMESTAMP" ){
         $default_msg = " DEFAULT CURRENT_TIMESTAMP ";
    }else if ( $fieldArr['Default'] ){
        $default_msg = " DEFAULT '".$fieldArr['Default']."' ";
    }else{
        $default_msg  ="";
    }
    
    ( $fieldArr['Default'] ? " DEFAULT '".$fieldArr['Default']."'"                         :        "" );
    return "ALTER TABLE `$tablename` ADD COLUMN `{$fieldArr[Field]}` {$fieldArr[Type]} " 
    . ( $fieldArr['Null']=="NO"     ? "NOT NULL"                                                    :     "NULL")
    . $default_msg
    . ( $fieldArr['Comment']        ? " COMMENT '".mysql_escape_string($fieldArr['Comment'])."'"     :        "" )
    . " AFTER `$after`"
    . ";";
}
?>
