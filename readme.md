# Phil

## Intro
Phil is a simple script to automate adding new entries to you /etc/hosts file and you vhosts file.
This can be useful for developers using Apache and (optionally) PHP.
Phil is super easy to get started with, you can download the install.php file and run it or, for a simple
one command install you can run the following in your terminal:

```
php -r "eval(file_get_contents('http://goo.gl/8bWMsW'));"
```

Once Phil is installed (and you've followed the on screen instructions, including adding a `sudo` alias in your
`~/.bash_profile` and re-sourcing your `~/.bash_profile`) you'll want to run `phil -init` to initialize your
templates and config file. See the Phil Commands section below for more usage details.

## From the help file

Phil automates setting up local dev domains for use with Apache.

### Setting up config variables:
    To set up a new config variable run `phil -set var_key var_value` where
    `var_key` is the name of the variable to set and `var_value` is the value.
    Note that only letters are accepted for the `var_key` argument."

### Required config variables:
    hosts -- the location of your hosts file, this is almost always '/etc/hosts'
    vhosts -- the location of your vhosts or httpd.conf file

### Phil Commands:
    -init -- initialize the config file, you must do this before using Phil
             this will overwrite any existing config params
    -set [key] [value] -- sets a param in the config file
    -get [key] -- gets the value of a given config param
    -get -- gets the value of all config params
    -unset -- removes a config param
    [domain] [folder] -- sets up a new domain by writing to `hosts` and `vhosts`
                         note that you will likely need to sudo this command
    -help -- read this help screen

Updating Phil: phil is tracked with git to update Phil to the latest version, simply cd into ~/.phil and run `git pull`

Customizing Output: to customize the output to your hosts and vhosts files
    edit the template.hosts.txt and template.vhost.txt files in ~/.phil after running `phil -init`

Made by @webbtj ~ Lazy AF

Tested in OSX 10.11.3 with PHP 5.6.8