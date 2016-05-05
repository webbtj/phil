<?php

define('RED_TEXT', "\033[0;31m");
define('GREEN_TEXT', "\033[0;32m");
define('YELLOW_TEXT', "\033[1;33m");
define('BLUE_TEXT', "\033[0;36m");
define('END_TEXT', "\033[0m");

class Phil{
    public function __construct($args){
        if(count($args) == 1 || $args[1] == '-help'){
            $this->help();
            exit;
        }

        if($args[1] == '-set'){
            $this->set_key($args);
            exit;
        }

        if($args[1] == '-get'){
            $this->get_key($args);
            exit;
        }

        if($args[1] == '-unset'){
            $this->unset_key($args);
            exit;
        }

        if($args[1] == '-init'){
            $this->init_config();
            exit;
        }

        if($args[1] == '-update'){
            $this->update();
            exit;
        }

        if($args[1] == '-v' || $args[1] == '-version'){
            $this->version();
            exit;
        }

        if(count($args) == 3){
            $this->setup_domain($args);
        }
    }

    function check_config(){
        $config_filename = dirname(__FILE__) . '/config.json';
        $config = @file_get_contents($config_filename);

        if(!$config)
            throw_error("Error! Config file not found: " . $config_filename);

        try {
            $config = json_decode($config);
            if(!$config)
                throw_error("Error! Invalid JSON in config file: " . $config_filename);
        } catch (Exception $e) {
            throw_error("Error! Invalid JSON in config file: " . $config_filename);
        }

        $required_params = ['hosts', 'vhosts'];
        foreach($required_params as $required_param){
            if(!isset($config->$required_param))
                throw_error("Error! Missing required param: " . $required_param);
        }

        return $config;
    }

    function init_config(){
        $config_filename = dirname(__FILE__) . '/config.json';
        @unlink($config_filename);

        $dummy = new stdClass;
        $defaults = (array) $this->defaults($dummy);

        foreach($defaults as $k => $v){
            $this->set_key(['phil.php', '-set', $k, $v], true);
        }

        if(!file_exists(dirname(__FILE__) . '/template.hosts.txt')){
            $template = file_get_contents(dirname(__FILE__) . '/default.hosts.txt');
            file_put_contents('template.hosts.txt', $template);
        }

        if(!file_exists(dirname(__FILE__) . '/template.vhost.txt')){
            $template = file_get_contents(dirname(__FILE__) . '/default.vhost.txt');
            file_put_contents('template.vhost.txt', $template);
        }

        output("Successfully initialized default config " . random_emoji(), GREEN_TEXT);
    }

    function set_key($args, $silent = false){
        $config_filename = dirname(__FILE__) . '/config.json';
        $config = @file_get_contents($config_filename);
        $config = json_decode($config);
        if(!$config)
            $config = new stdClass;

        if(count($args) < 4)
            throw_error("Not enough arguments, please see help by running `phil -help` for usage.");
        if(count($args) > 4)
            throw_error("Too many arguments, please see help by running `phil -help` for usage.");

        $key = preg_replace('/[^a-z_]/', '', strtolower($args[2]));
        $value = $args[3];

        $config->$key = $value;

        $config = json_encode($config, JSON_PRETTY_PRINT);

        file_put_contents($config_filename, $config);

        if(!$silent)
            output("Successfully set `$key` to '$value' " . random_emoji(), GREEN_TEXT);
    }

    function get_key($args){
        $config_filename = dirname(__FILE__) . '/config.json';
        $config = @file_get_contents($config_filename);
        $config = json_decode($config);
        if(!$config)
            $config = new stdClass;

        if(count($args) < 2)
            throw_error("Not enough arguments, please see help by running `phil -help` for usage.");
        if(count($args) > 3)
            throw_error("Too many arguments, please see help by running `phil -help` for usage.");

        if(count($args) == 3){
            $key = preg_replace('/[^a-z]/', '', strtolower($args[2]));
            $value = null;
            if(isset($config->$key))
                $value = $config->$key;

            if($value)
                output("$value", GREEN_TEXT);
            else
                throw_error("`$key` is not currently defined");
        }else{
            $config = (array) $config;
            output("Below are all currently defined config vars ", GREEN_TEXT);
            foreach($config as $k => $v){
                output("    $k: $v", BLUE_TEXT);
            }
        }
    }

    function unset_key($args){
        $config_filename = dirname(__FILE__) . '/config.json';
        $config = @file_get_contents($config_filename);
        $config = json_decode($config);
        if(!$config)
            $config = new stdClass;

        if(count($args) < 3)
            throw_error("Not enough arguments, please see help by running `phil -help` for usage.");
        if(count($args) > 3)
            throw_error("Too many arguments, please see help by running `phil -help` for usage.");

        $key = preg_replace('/[^a-z]/', '', strtolower($args[2]));

        unset($config->$key);

        $config = json_encode($config, JSON_PRETTY_PRINT);

        file_put_contents($config_filename, $config);

        output("Successfully unset `$key` " . random_emoji(), GREEN_TEXT);
    }

    function setup_domain($args){
        $config = $this->check_config();
        $config = $this->defaults($config);

        $config->document_root = str_replace('//', '/', $config->document_root . '/' . $args[2]);
        $config->domain = $args[1];
        
        $this->write_hosts($config);
        $this->write_vhost($config);

        if($config->apachectl){
            output("Attempting to restart Apache, this will not affect the success status of the operation!", YELLOW_TEXT);
            exec("sudo " . $config->apachectl . " restart 2>/dev/null");
        }else{
            output("You haven't set `apachectl` yet. If you set it, I can try to automatically restart Apache for you too.", YELLOW_TEXT);
        }

        output("Successfully setup " . $config->domain . " pointing to " . $config->document_root . " " . random_emoji(), GREEN_TEXT);
        output("    \"Tiny onions swimming in a sea of cream sauce.\"", BLUE_TEXT);
    }

    function defaults($config){
        $defaults = [
            'port' => '80',
            'server_admin' => 'email@example.com',
            'upload_max_filesize' => '128M',
            'post_max_size' => '256M',
            'short_open_tag' => 'on',
            'options' => 'FollowSymlinks',
            'rewrite_engine' => 'on',
            'display_errors' => 'on',
            'document_root' => '/var/www/vhosts',
            'ip' => '127.0.0.1',
            'hosts' => '/etc/hosts',
            'vhosts' => '/etc/httpd/conf/httpd.conf',
            'apachectl' => ''
        ];
        foreach($defaults as $k => $v){
            if(!isset($config->$k))
                $config->$k = $v;
        }

        return $config;
    }

    function write_hosts($config){
        $output = file_get_contents(dirname(__FILE__) . '/template.hosts.txt');
        $config_array = (array) $config;
        foreach($config_array as $k => $v){
            $output = str_replace('{' . $k . '}', $v, $output);
        }

        file_put_contents($config->hosts, $output, FILE_APPEND);
    }

    function write_vhost($config){
        $output = file_get_contents(dirname(__FILE__) . '/template.vhost.txt');
        $config_array = (array) $config;
        foreach($config_array as $k => $v){
            $output = str_replace('{' . $k . '}', $v, $output);
        }

        if(!file_exists($config->document_root)){
            output("Attempting to create " . $config->document_root . " because it does not yet exist", YELLOW_TEXT);
            $create_dir = mkdir($config->document_root, 0755);
            if($create_dir)
                output($config->document_root . " created successfully, note that is it currently owned by root. You may want to `chown` and/or `chmod`.", GREEN_TEXT);
            else
                output("Could not create " . $config->document_root . "; probably not allowed", RED_TEXT);
        }

        file_put_contents($config->vhosts, $output, FILE_APPEND);
    }

    function update(){
        exec('cd ~/.phil; git pull');
    }

    public function version(){
        $local = file_get_contents(dirname(__FILE__) . '/version');
        output("$local", BLUE_TEXT);
        $remote = file_get_contents("https://raw.githubusercontent.com/webbtj/phil/master/version");
        if($local == $remote)
            output("This is the latest version.", GREEN_TEXT);
        else
            output("Phil is out of date. Please update to $remote by running `phil -update`", RED_TEXT);
    }

    function help(){
        output("", BLUE_TEXT);
        output("===============================================================================", BLUE_TEXT);
        output("", BLUE_TEXT);

        output("Phil automates setting up local dev domains for use with Apache.", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Setting up config variables:", BLUE_TEXT);
        output("    To set up a new config variable run `phil -set var_key var_value` where", BLUE_TEXT);
        output("    `var_key` is the name of the variable to set and `var_value` is the value.", BLUE_TEXT);
        output("    Note that only letters are accepted for the `var_key` argument." , BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Required config variables:", BLUE_TEXT);
        output("    hosts -- the location of your hosts file, this is almost always '/etc/hosts'", BLUE_TEXT);
        output("    vhosts -- the location of your vhosts or httpd.conf file", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Phil Commands:", BLUE_TEXT);
        output("    -init -- initialize the config file, you must do this before using Phil", BLUE_TEXT);
        output("             this will overwrite any existing config params", BLUE_TEXT);
        output("    -set [key] [value] -- sets a param in the config file", BLUE_TEXT);
        output("    -get [key] -- gets the value of a given config param", BLUE_TEXT);
        output("    -get -- gets the value of all config params", BLUE_TEXT);
        output("    -unset -- removes a config param", BLUE_TEXT);
        output("    [domain] [folder] -- sets up a new domain by writing to `hosts` and `vhosts`", GREEN_TEXT);
        output("                         note that you will likely need to sudo this command", GREEN_TEXT);
        output("    -help -- read this help screen", BLUE_TEXT);
        output("    -version -- check the current version of Phil and check for updates", BLUE_TEXT);
        output("    -update -- update Phil to the latest version", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Updating Phil: phil is tracked with git to update Phil to the latest version, simply cd into ~/.phil and run `git pull`", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Customizing Output: to customize the output to your hosts and vhosts files", BLUE_TEXT);
        output("    edit the template.hosts.txt and template.vhost.txt files in ~/.phil after running `phil -init`", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("Made by @webbtj ~ Lazy AF", BLUE_TEXT);
        output("", BLUE_TEXT);
        output("===============================================================================", BLUE_TEXT);
        output("", BLUE_TEXT);
    }
}

$void = new Phil($argv);


function throw_error($text, $emoji = true){
    if($emoji)
        $text .= ' ' . random_emoji(true);

    output($text, RED_TEXT);
    exit;
}

function output($text, $color = null, $suppress_nl = false){
    if($color)
        echo $color;
    echo $text;
    if($color)
        echo END_TEXT;
    if(!$suppress_nl)
        echo "\n";
}

function random_emoji($bad = false){
    $emojis = ['🌽', '🌶️', '🍞', '🧀', '🍖', '🍗', '🍔', '🍟', '🍕', '🌭', '🌮', '🌯', '🍳', '🍲', '🍿', '🍱', '🍘', '🍜', '🍝', '🍠', '🍢', '🍣', '🍤', '🍡', '🍦', '🍧', '🍨', '🍩', '🍪', '🎂', '🍰', '🍫', '🍬', '🍭', '🍮', '🍯'];

    if($bad)
        $emojis = ['🍇', '🍈', '🍉', '🍊', '🍋', '🍌', '🍍', '🍎', '🍏', '🍐', '🍑', '🍒', '🍓', '🍅', '🍆'];

    return $emojis[ mt_rand( 0, count($emojis)-1 ) ];
}
