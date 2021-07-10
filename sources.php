<?php

class Sources
{
    //Источники, которые заполняются данными от пользователя
    public static $user_defined = array(
        '$_GET',
        '$_POST',
        '$_REQUEST',
        '$_SERVER',
        '$_COOKIE',
        '$_FILES'
    );

    public static $user_defined_functions = array (
        "apache_request_headers",
        "getallheaders",
        "http_get",
        "getenv",

    );

    //Токены, которые можно использовать при конкатенации
    public static $string_constructing = array(
        "T_CONSTANT_ENCAPSED_STRING",
        "T_VARIABLE",
        "T_LNUMBER",
        "T_PLUS",
        "T_MULTIPLE",
        "T_CONCAT_OP"
    );

}

class VulnFunctions
{
    public static $PVF = array (
        'assert' => array( 2, 1),
        'eval'=> array(1, 1),
        'exec' => array(3, 1),
        'system' => array (2, 1),
        'passthru' => array(2, 1),
        'shell_exec' => array (1, 1),
        'backticks' => array (1 ,1),
        'popen' => array (2, 1),
        'proc_open' => array(6, 1),
        'pcntl_exec' => array(3, 1),
        "backtick" => array(1,1),
    );

    //
    public static $pcre_functions = array (
        'mb_ereg_replace' => array(4, 1, 4),
        "mb_eregi"=> array(3, 1),
        "preg_replace"=>array(5, 1),
        "mb_eregi_replace"=>array(4, 1, 4),
        "preg_filter "=>array(5,1),
    );

    public static $file_read_functions = array(
      "fopen" => array (4, 1),
    );


    public  static $files_functions = array(
      "fopen",
      "fpassthru"
    );

    public static $file_uploading = array(
        "copy" => 0,
        "move_uploaded_file" => 0,
    );

    public static $file_write_functions = array(
        //"fopen" => array (4,1),
        "fwrite" => array(3, 2),
        "fputs" => array(3, 2),
        "file_put_contents"=>array(4, 1),
    );

    public static $callbackable_almost = array (
        'preg_replace_callback',
        "array_walk",
        "ArrayIterator::uasort",
        "ArrayIterator::uksort",
        "ArrayObject::uasort",
        "ArrayObject::uksort",
        "array_diff_uassoc",
        "array_diff_ukey",
        "array_intersect_uassoc",
        "array_intersect_ukey",
        "array_udiff_uassoc",
        "array_udiff",
        "array_uintersect",
        "array_uintersect_uassoc",
        "array_walk_recursive",
        "call_user_func",
        "call_user_func_array",
        "Closure::fromCallable",
        "Ds\Deque::apply",
        "Ds\Deque::filter",
        "Ds\Deque::map",
        "Ds\Deque::reduce",
        "Ds\Map::apply",
        "Ds\Map::filter",
        "Ds\Map::map",
        "Ds\Map::reduce",
        "Ds\Sequence::apply",
        "Ds\Sequence::filter",
        "Ds\Sequence::map",
        "Ds\Sequence::reduce",
        "Ds\Set::filter",
        "Ds\Vector::apply",
        "Ds\Vector::filter",
        "Ds\Vector::map",
        "Ds\Vector::reduce",
        "Ev::run",
        "EventBufferEvent::setCallbacks",
        "EventHttp::setDefaultCallback",
        "EventHttpConnection::setCloseCallback",
        "EventListener::setCallback",
        "EventListener::setErrorCallback",
        "EvLoop::run",
        "EvWatcher::invoke",
        "EvWatcher::setCallback",
        "fann_create_train_from_callback",
        "fann_set_callback",
        "forward_static_call_array",
        "GearmanClient::setClientCallback",
        "GearmanClient::setCompleteCallback",
        "GearmanClient::setCreatedCallback",
        "GearmanClient::setDataCallback",
        "GearmanClient::setExceptionCallback",
        "GearmanClient::setFailCallback",
        "GearmanClient::setStatusCallback",
        "GearmanClient::setWarningCallback",
        "GearmanClient::setWorkloadCallback",
        "GearmanWorker::addFunction",
        "Generator::__wakeup",
        "header_register_callback",
        "ibase_set_event_handler",
        "mb_ereg_replace_callback",
        "mb_output_handler",
        "MongoLog::getCallback ",
        "MongoLog::setCallback ",
        "mysqlnd_ms_set_user_pick_server",
        "mysqlnd_qc_set_is_select",
        "mysqlnd_qc_set_user_handlers",
        "OAuthProvider::consumerHandler",
        "ob_gzhandler",
        "oci_register_taf_callback",
        "Parle\Lexer::callout",
        "Parle\RLexer::callout",
        "PDO::sqliteCreateCollation",
        "PDO::sqliteCreateAggregate",
        "PDO::sqliteCreateFunction",
        "preg_replace_callback",
        "preg_replace_callback_array",
        "readline_callback_handler_install",
        "readline_callback_handler_remove",
        "readline_callback_read_char",
        "register_shutdown_function",

    );

    public static $callbackable = array (
        "array_diff_uassoc" => array (0, 0),
        "array_diff_ukey" => array (0, 0),
        "array_intersect_uassoc" => array(0, 0),
        "array_intersect_ukey" => array(0, 0),
        "array_udiff_uassoc" => array(0, 0),
        "array_udiff" => array(0, 0),
        "array_uintersect" => array(0, 0),
        "array_uintersect_uassoc" => array(0, 0),
        "array_walk_recursive" => array(3, 2),
        "call_user_func" => array(0, 1),
        "call_user_func_array" => array(2, 1),
        "header_register_callback" => array(1, 1),
        "mb_ereg_replace_callback" => array (4, 2),
        "preg_replace_callback" => array(6, 2),
        "preg_replace_callback_array" => array(5, 2),
        "array_filter" => array(3,2),
        "array_map"=> array(0,1),
        "array_walk"=>array(3,2),
        "array_walk_recursive"=>array(3,2),
        "forward_static_call_array"=>array(2,1),
        "array_udiff_assoc"=>array(0,0),
        "array_intersect_uassoc"=>array(0,0),
        "array_intersect_ukey"=>array(0,0),
        "register_tick_function "=>array(0,1),
        "fillter_var" => array(1,3),
        "yaml_parse" => array(0, 0),
        "uasort"=>array(2,2),
        "ob_start"=>array(3,1),
        "register_shutdown_function"=>array(0, 1),
        "register_tick_function" => array(0, 1),
        "assert_options" => array(2,2),
        "reflectionfunction" => array(1,1),
        "createfunction" => array(4, 2),
        "fetchall" => array(3, 2)
    );

    public static $db_functions = array (
        "mysqli_select_db" => array (1, 1),
        "mysql_connect" => array(2, 2),
        "mysqli_connect" => array(5,1),
        "pg_connect" => array(2, 1)
    );

    public static $var_filters = array(
      "fillter_var" => array(1,3),
    );

    public static $ini_set_settings = array(
        'allow_url_include',
    );

    public static $coding_decoding = array(
        "base64_decode",
        "bzdecompress",
        "chr",
        "convert_uudecode",
        "gzdecode",
        "gzinflate",
        "gzuncompress",
        "imap_base64",
        "inflate_add",
        "pack",
        "str_rot13",
    );

    public static $suspicious = array(
        "create_function",
        "function_exists",
        "getenv",
        'call_user_func',
        'call_user_func_array',
        'invoke',
        'invokeArgs',
        "ob_start",
        'create_function',
    );
}