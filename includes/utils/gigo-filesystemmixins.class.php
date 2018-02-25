<?php
namespace GIGeodataOverride\Utils;

trait FileSystemMixins
{

    public static function page_path( $page, $section = 'admin')
    {
        return plugin_dir_path( GIGEOOVERRIDE_FILE ) . $section . '/'. urlencode( $page ) .'.php';
    }

    public static function stylesheet( $resource, $section = 'admin' )
    {
      return plugin_dir_url( GIGEOOVERRIDE_FILE ) . $section . '/css/'. $resource . '.css';
    }

    public static function javascript( $resource, $section = 'admin' )
    {
      return plugin_dir_url( GIGEOOVERRIDE_FILE ) . $section . '/js/'. $resource . '.js';
    }

    public static function archive_path( $resource, $compression = 'tar.gz' )
    {
      return plugin_dir_path( GIGEOOVERRIDE_FILE ) . 'resources/' . $resource . '.' . $compression;
    }

    public static function read_compressed( $resource )
    {
      return file_get_contents( 'phar://'. self::archive_path( $resource ) . '/' . $resource );

    }
}
