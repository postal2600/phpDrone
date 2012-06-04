<?php
//Main
$CACHE_DIR = "./tmpl_cache";
$DEBUG= True;
$COMPRESS_HTML = False;
$CODE_LANGUAGE = "en";
$TEMPLATE_DIR = "templates/";


//Modules
$USE_DATABASE = True;
$USE_FORM = True;
$USE_TEMPLATE = True;
$USE_MAIL = True;
$USE_WIDGETS = True;
$USE_I18N = True;
$USE_ADMIN = True;
$USE_PROFILER = True;
$USE_CONTROLLER = False;

// Database. This options will work only if $USE_DATABASE is set to True
$SQL_ENGINE = "mysql";
$SQL_SERVER = "localhost";
$SQL_USER = "";
$SQL_PASSWORD = "";
$SQL_DATABASE = "";


// Admin. This options will work only if $USE_ADMIN is set to True
$ADMIN_USER = "";
$ADMIN_PASS = "";
$ADMIN_PASS_HASH = "";


// SMTP. This options will work only if $USE_MAIL is set to True
$SMTP_HOST = "";
$SMTP_PORT = "";
$SMTP_USER = "";
$SMTP_PASSWORD = "";


// Controller. This options will work only if $USE_CONTROLLER is set to True
$DEFAULT_CONTROLLER = "index";
$DEFAULT_METHOD = "index";

?>
