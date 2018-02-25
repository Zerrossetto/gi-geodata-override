<?php
/**
 * @package GI_Geodata_Override
 * @version 0.1
 */
/*
 * Plugin Name: GI Geodata Override
 * Plugin URI: http://blackwhitestudio.it/
 * Description: Control panel to override GI geodata to fit RealHomes theme
 * Author: Black & White Studio
 * Version: 1.0
 * Author URI: http://zerrosset.to/
 */

if ( !defined( 'ABSPATH' ) || !function_exists( 'add_action' ) ) {
    exit; // exit when accessed directly or outside plugin scope
}

// Define GISYNCCP_FILE
if (!defined( 'GIGEOOVERRIDE_FILE' )) {
    define( 'GIGEOOVERRIDE_FILE', __FILE__  );
}

(function () {

    // PHP-FIG PSR-4 specification compliant autoloader
    spl_autoload_register( function ( $class ) {
        if (class_exists( $class )) {
            return;
        }
        $prefix = 'GIGeodataOverride\\';
        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }
        $class = strtolower( substr( $class, $len ) );
        $path = array_merge(
            preg_split( '#/#', plugin_dir_path( GIGEOOVERRIDE_FILE ), -1, PREG_SPLIT_NO_EMPTY ),
            array( 'includes' ),
            preg_split( '#\\\\#', $class, -1, PREG_SPLIT_NO_EMPTY )
        );
        $last = count( $path ) - 1;
        $path[ $last ] = 'gigo-' . $path[ $last ] . '.class.php';
        $file = '/' . implode( '/', $path );
        if ( file_exists( $file ) ) {
            require_once( $file );
        }
    } );

    $gi_geodata_override_plugin = new GIGeodataOverride\Plugin( GIGeodataOverride\Plugin::WITH_HOOKS_BINDING );
})();
