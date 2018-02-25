<?php
/**
 * Instantiate GI Geodata Override on WP
 *
 * @since 0.1
 *
 * @package GISync_cp
 */
 namespace GIGeodataOverride;

class Plugin
{
    use Utils\Logging;
    use Utils\FileSystemMixins;

    const PREFIX = 'gigo';

    const VERSION = '1.0.0';

    const WITH_HOOKS_BINDING = TRUE;

    const MIN_PHP_VERSION = '5.6.0';

    private const NOTICE_BOX = '<div class="%s"><p>%s</p></div>';

    /**
      * The current version of the plugin.
      *
      * @since    1.0.0
      *
      * @var string The current version of the plugin.
      */
    protected $version;

    /**
     * An instance of type *ViewModel
     *
     * @since 1.0.0.
     *
     * @var stdClass The view model instance for the setting page
     */
    public $model;

    /**
      * Define the core functionality of the plugin.
      *
      * Set the plugin name and the plugin version that can be used throughout the plugin.
      * Load the dependencies, define the locale, and set the hooks for the admin area and
      * the public-facing side of the site.
      *
      * @since    1.0.0
      */
    public function __construct( $do_hooks_binding = FALSE )
    {

        $this->version = Plugin::VERSION;

        if ( $do_hooks_binding ) {
            $this->bind_hooks();
        }
    }

    public static function activation()
    {

        if (version_compare( PHP_VERSION, self::MIN_PHP_VERSION ) < 0) {
            trigger_error(
              'This plugin requires at least PHP '.self::MIN_PHP_VERSION.' version',
              E_USER_ERROR
            );
        }

        $version_key = self::prefix( 'version' );
        $installed_version = get_option( $version_key );
        Plugin::debug( 'plugin version ' .  $installed_version ?: 'fist install' );

        if( strcmp( $installed_version, Plugin::VERSION ) < 0 ) {
            Plugin::debug( 'Starting plugin structure upgrade' );

            \GIGeodataOverride\Utils\Db::create_gi_geodata_table();
            \GIGeodataOverride\Utils\Db::create_overrides_table();
            \GIGeodataOverride\Utils\Db::upload_gi_geodata();

            add_option( $version_key, Plugin::VERSION );
        }
    }

    public static function deactivation()
    {
        //should do nothing
    }

    public static function uninstall()
    {
        // Drop in reverse order due to foreign key constraints
        \GIGeodataOverride\Utils\Db::drop_overrides_table();
        \GIGeodataOverride\Utils\Db::drop_gi_geodata_table();
        delete_option( self::prefix( 'version' ) );
    }

    public function override_panel()
    {
        if( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' && array_key_exists( 'action', $_POST ) ) {
          switch( strtolower( $_POST[ 'action' ] ) ) {
            case 'add':
              $this->add_override( $_POST );
              break;
            case 'delete':
              $this->delete_overrides( $_POST );
              break;
          }
        }

        require_once( self::page_path( 'gigo-panel' ) );
    }

    public function admin_menu()
    {
        add_management_page(
            'GI Geodata Override',
            'GI Geodata Override',
            'manage_options',
            $this->prefix( 'settings' ),
            array( $this, 'override_panel' )
        );
    }

    public function enqueue_scripts()
    {
        $prefix = $this->prefix( 'gigo-panel' );
        $minified = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : 'min';
        $screen = get_current_screen();

        if (property_exists($screen, 'id') && $screen->id === 'tools_page_gigo_settings') {
          wp_enqueue_style(
            $this->prefix( 'style' ),
            self::stylesheet( 'gigo-style' ),
            array(),
            $this->version,
            'all'
          );
          wp_enqueue_script(
            $this->prefix( 'typeahead' ),
            self::javascript( 'typeahead.bundle' . $minified ),
            array(),
            $this->version
          );
          $panel_handle = $this->prefix( 'panel' );
          wp_register_script(
            $panel_handle,
            self::javascript( 'gigo-panel' ),
            array(),
            $this->version
          );
          wp_localize_script(
            $panel_handle,
            'gigoSettings',
            array(
              'nonce' => wp_create_nonce( 'wp_rest' ),
              'noResultsMessage' => 'unable to find any match for this location'
            )
          );
          wp_enqueue_script( $panel_handle );
        }
    }

    public function rest_endpoint()
    {
        register_rest_route(
          self::prefix( 'v1', '/' ),
          '/gi-geodata',
          array(
            'methods' => 'POST',
            'callback' => array( 'GIGeodataOverride\Rest', 'query_gi_geodata' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
          )
        );
    }

    private function bind_hooks()
    {
        if ( is_admin() ) {
            foreach ( array( 'activation', 'deactivation', 'uninstall' ) as &$hook )
            {
              $hook_function = 'register_'. $hook .'_hook';
              $hook_function ( GIGEOOVERRIDE_FILE, array( __CLASS__, $hook ) );
            }
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
            add_action( 'admin_menu', array( $this, 'admin_menu') );
        }
        add_action( 'rest_api_init', array( $this, 'rest_endpoint' ) );
    }

    public static function prefix($setting_name, $sep = '_')
    {
        return self::PREFIX . $sep . $setting_name;
    }

    private static function form_field_not_exists( $field, $form )
    {
      if( array_key_exists( $field, $form ) ) {
        return is_array( $form[ $field ] ) ? empty( $form[ $field ] ) : empty( trim( $form[ $field ] ) );
      }
      return FALSE;
    }

    private static function print_notice( $msg,  $level = 'error' )
    {
      printf( self::NOTICE_BOX, esc_attr( 'notice notice-'. $level .' is-dismissable' ), $msg );
    }

    private function add_override( $form )
    {
      if( self::form_field_not_exists( 'to', $form ) ) {
        self::print_notice( 'Occorre compilare il campo di remap', 'warning' );
        return;
      }

      foreach( array( 'state_id', 'neighborhood_id', 'municipality_id', 'neighborhood_id' ) as &$field ) {
        if( self::form_field_not_exists( $field, $form ) ) {
          self::print_notice( 'Errore tecnico, campo "'. $field .'" mancante. Prego riprovare' );
          return;
        }
      }

      if( \GIGeodataOverride\Utils\Db::insert_override( $form ) ) {
        self::print_notice( 'Override inserito', 'success' );
      } else {
        $err_msg = 'Errore tecnico, inserimento non riuscito.';

        if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
          global $wpdb;
          $err_msg .= '<h4>SQL error</h4>'. $wpdb->last_error;
          $err_msg .= '<h4>Last query</h4>'. $wpdb->last_query;
        }

        self::print_notice( $err_msg );
      }
    }

    private function delete_overrides( $form )
    {
      if( self::form_field_not_exists( 'override', $form ) ) {
        self::print_notice( 'Seleziona almeno un elemento da cancellare', 'warning' );
        return;
      }
      \GIGeodataOverride\Utils\Db::delete_overrides( $form[ 'override' ] );
    }
}
