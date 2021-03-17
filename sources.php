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
}

class VulnFunctions
{
    public static $PVF = array (
        'assert',
        'eval',
        'create_function',
        ##########################
        'mb_ereg_replace', //with e modifier
        'mb_eregi_replace', //with e modifier
        "mb_eregi",
        //all pcre functions
        ##############################
        'preg_filter',
        'preg_replace',
        'preg_replace_callback',
        'exec',
        'system',
        'passthru',
        'shell_exec',
        #backticks
        'popen',
        'proc_open',
        'pcntl_exec',
        #so-so
        'call_user_func',
        'call_user_func_array',
        #class ReflectionFunction
        'invoke',
        'invokeArgs',
        "ob_start"
    );

    public static $callbackable = array (
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
        "IntlChar::chr",

    );

    public static $suspicious = array(
        "create_function",
        "function_exists",
    );

}