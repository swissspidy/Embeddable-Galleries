<?php
/*
Plugin Name: Embeddable Galleries
Plugin URI: http://github.com/swissspidy/embeddable-galleries/
Description: Allows galleries in posts to be embedded by other websites using a specific embed code
Version: 0.1
Author: Pascal Birchler
Author URI: http://pascalbirchler.ch/
*/

include 'src/class-embeddable_galleries.php';

$GLOBALS['embeddable-galleries'] = \Embeddable_Galleries\Embeddable_Galleries::get_instance();