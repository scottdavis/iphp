iphp - Interactive PHP Shell
============================
iphp is an interactive php shell that solves a number of painful problems with normal php shells:

* Fatal Error handling - iphp doesn't die even if your code does.
* optional readline support
* readline support includes: autocomplete, history, line editing
* support ctags *tags* files
* implemented as a class for integration with your framework
* support for require/include; you can load php files within iphp

Example:

    php> new ArrayObject(array(1,2))
    
    => ArrayObject Object
    (
        [0] => 1
        [1] => 2
    )
    
    php> $_[0] + 1
    => 2
