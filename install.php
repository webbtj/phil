<?php

define('RED_TEXT', "\033[0;31m");
define('GREEN_TEXT', "\033[0;32m");
define('YELLOW_TEXT', "\033[1;33m");
define('BLUE_TEXT', "\033[0;36m");
define('END_TEXT', "\033[0m");

function throw_error($text){
    output($text, RED_TEXT);
    exit;
}

function output($text, $color = null){
    if($color)
        echo $color;
    echo $text;
    if($color)
        echo END_TEXT;
    echo "\n";
}

if(isset($argv[1]) && in_array($argv[1], ['-h', '-help', '--h', '--help', 'h', 'help', '-m', '-man', '--m', '--man', 'm', 'man'])){
    output("Welcome to Phil!", GREEN_TEXT);
    output("This will download and install Phil, a tool to automate local domain setup for Apache/PHP development.", BLUE_TEXT);
    output("You need to have git installed to download Phil.", BLUE_TEXT);
    output("For more info visit the github page: https://github.com/webbtj/phil.", BLUE_TEXT);
    output("If you are reinstalling Phil, be sure to run the installer passing `-f`.", BLUE_TEXT);
    output("To install Phil, rerun the previous command without requesting the help or man page.", YELLOW_TEXT);
}

if(isset($argv[1]) && $argv[1] == '-f'){
    output("Deleting and existing copies of Phil becuse you passed `-f`", YELLOW_TEXT);
    exec('rm -rf ~/.phil');
    output("", YELLOW_TEXT);
}

output("Going to use git to download Phil to ~/.phil.", YELLOW_TEXT);
exec("git clone git@github.com:webbtj/phil.git ~/.phil");
output("", YELLOW_TEXT);

output("Going to add the `phil` alias to your ~/.bash_profile.", YELLOW_TEXT);
exec("echo \"alias phil='php ~/.phil/phil.php'\" >> ~/.bash_profile");
output("", YELLOW_TEXT);

output("All done installing Phil!", GREEN_TEXT);
output("Because Phil sometimes needs sudo access, you'll want to make sure you can run `sudo phil`.", BLUE_TEXT);
output("The easiest way to make Phil \"sudo-able\" is to make sure you've added the following alias to you ~/.bash_profile", BLUE_TEXT);
output("alias sudo='sudo '", BLUE_TEXT);
output("                ^ note the space, this is required", BLUE_TEXT);
output("Once you're done you should either re-source your ~/.bash_profile or exit and relaunch your terminal.", GREEN_TEXT);
output("Get to know Phil by running `phil -help` once you restart.", GREEN_TEXT);
