<?
//this expects u to have a real file in the path
//   - "/home/usr/library/david/random-scripts/readme.txt"
//   or you could have a "/" slash at the end to indicate thats a folder
//   - "/home/usr/library/david/random-scripts/"
function createFolderForPath( $path ){
        if ( is_dir($path) ){
            return array($path);
        }
        //$path_parts = explode( "/" , $path );
        $array = array();

        $file_strcture = explode( '/' , $path );
        $folder_path = implode( '/' ,array_slice( $file_strcture, 0 , count($file_strcture)-1 ) );
        //for every file check every folder nested
        //TODO: save folders that is already checked
        for ( $i = 1; $i < count($file_strcture); $i++ ){
            $paths = implode( '/' ,array_slice( $file_strcture, 0 , $i ) );
            if ( $paths != ""){
                if (!file_exists($paths)) {
                    mkdir($paths, 0777);
                    chmod($paths, 0777);
                }
            }
            $array[] = $paths;
        }
        return $array;
    }

?>