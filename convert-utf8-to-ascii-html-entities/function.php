<?
function accent_and_chinese_chars_2_html($str){

    $str = htmlspecialchars_decode(htmlentities($str, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
    $str = mb_convert_encoding( $str , 'UTF-32', 'UTF-8');
    $t = unpack("N*", $str);
    $t = array_map(function($n) { return ( $n>127? "&#$n;" : html_entity_decode("&#$n;" , ENT_QUOTES , 'UTF-8') ); }, $t);
    return implode("", $t);
}

?>