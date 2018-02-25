<?php
namespace GIGeodataOverride\Utils;

class Db
{
  use FileSystemMixins;

  private const GI_GEODATA_TABLE = 'giimport_geodata';
  private const OVERRIDES_TABLE = 'giimport_geodata_override';
  private const WITH_CACHING = '/*qc=on*/'.'/*qc_ttl=30*/';

  public static function get_overrides( $limit = 25 )
  {
    global $wpdb;

    $select = $wpdb->prepare(
      "
      SELECT `wp`.`override_seq` AS `id`,
             `wp`.`author`,
             UNIX_TIMESTAMP( `wp`.`insert_on` ) AS `insert_ts`,
             `gi`.`state_id`,
             `gi`.`province_id`,
             `gi`.`municipality_id`,
             `gi`.`neighborhood_id`,
             `gi`.`neighborhood_name` AS `original_name`,
             `wp`.`override`
      FROM `". $wpdb->base_prefix . self::OVERRIDES_TABLE ."` as `wp`
      INNER JOIN `". $wpdb->base_prefix . self::GI_GEODATA_TABLE ."` as `gi`
              ON ( `wp`.`gi_state_id`        = `gi`.`state_id`
               AND `wp`.`gi_province_id`     = `gi`.`province_id`
               AND `wp`.`gi_municipality_id` = `gi`.`municipality_id`
               AND `wp`.`gi_neighborhood_id` = `gi`.`neighborhood_id`
              )
      LIMIT %d;
      ",
      $limit
    );
    return $wpdb->get_results( $select, ARRAY_A );
  }

  public static function insert_override( $data )
  {
    global $wpdb;
    return $wpdb->insert(
        $wpdb->base_prefix . self::OVERRIDES_TABLE,
        array(
          'author'             => (wp_get_current_user())->display_name,
          'gi_state_id'        => $data[ 'state_id' ],
          'gi_province_id'     => $data[ 'province_id' ],
          'gi_municipality_id' => $data[ 'municipality_id' ],
          'gi_neighborhood_id' => $data[ 'neighborhood_id' ],
          'override'           => $data[ 'to' ]
        ),
        array( '%s', '%d', '%d', '%d', '%d', '%s' )
      );
  }

  public static function delete_overrides( $overrides )
  {
    global $wpdb;
    return $wpdb->query(
      "
      DELETE FROM `". $wpdb->base_prefix . self::OVERRIDES_TABLE ."`
      WHERE `override_seq` IN (". join(',', $overrides ) .");
      "
    );
  }

  public static function search_geodata_fulltext( $query, $limit = 10 )
  {
    global $wpdb;

    $select = $wpdb->prepare (
       ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : self::WITH_CACHING ) .
       "
       SELECT `state_id`,
              `province_id`,
              `municipality_id`,
              `neighborhood_id`,
              `province_name`,
              `municipality_name`,
              `neighborhood_name`
       FROM `" . $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
       WHERE MATCH (
         `neighborhood_name`,
         `municipality_name`,
         `province_name`
      ) AGAINST ( %s IN NATURAL LANGUAGE MODE )
         AND ROW( `state_id`,
                  `province_id`,
                  `municipality_id`,
                  `neighborhood_id`
                 ) NOT IN (
                   SELECT `gi_state_id` AS `state_id`,
                          `gi_province_id` AS `province_id`,
                          `gi_municipality_id` AS `municipality_id`,
                          `gi_neighborhood_id` AS `neighborhood_id`
                   FROM `". $wpdb->base_prefix . self::OVERRIDES_TABLE ."`
                 )
      LIMIT %d;
      ",
      $query,
      $limit
    );
    return $wpdb->get_results( $select, ARRAY_A );
  }

  public static function search_geodata_containing( $query, $limit = 10 )
  {
    global $wpdb;

    $select = $wpdb->prepare (
         ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : self::WITH_CACHING ) .
         "
         SELECT 'province' AS `match_type`,
                `state_id`,
                `province_id`,
                NULL AS `municipality_id`,
                NULL AS `neighborhood_id`,
                `province_name` AS `matching_term`
          FROM `" . $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
          WHERE `province_name` LIKE '%%%s%%'
          UNION
          SELECT 'municipality' AS `match_type`,
                 `state_id`,
                 `province_id`,
                 `municipality_id`,
                 NULL AS `neighborhood_id`,
                 `municipality_name` AS `matching_term`
          FROM `" . $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
          WHERE `municipality_name` LIKE '%%%s%%'
          UNION
          SELECT 'neighborhood' AS `match_type`,
                 `state_id`,
                 `province_id`,
                 `municipality_id`,
                 `neighborhood_id`,
                 `neighborhood_name` AS `matching_term`
          FROM `" . $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
          WHERE `neighborhood_name` LIKE '%%%s%%'
          LIMIT %d;
        ",
        $query,
        $query,
        $query,
        $limit
      );
    return $wpdb->get_results( $select, ARRAY_A );
  }

  public static function create_gi_geodata_table()
  {
    global $wpdb;

    $create_table = "
    CREATE TABLE IF NOT EXISTS `". $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
    (
      `geodata_version`         TINYINT      UNSIGNED NOT NULL DEFAULT 0,
      `state_id`                TINYINT      UNSIGNED NOT NULL DEFAULT 110,
      `province_id`             TINYINT      UNSIGNED NOT NULL,
      `municipality_id`         SMALLINT     UNSIGNED NOT NULL,
      `neighborhood_id`         MEDIUMINT    UNSIGNED NOT NULL,
      `istat_municipality_code` VARCHAR(6)            NOT NULL,
      `province_name`           VARCHAR(80)           NOT NULL,
      `municipality_name`       VARCHAR(255)          NOT NULL,
      `neighborhood_name`       VARCHAR(255)          NOT NULL,
      PRIMARY KEY
      (
        `state_id`,
        `province_id`,
        `municipality_id`,
        `neighborhood_id`
      ),
      FULLTEXT KEY
      (
        `province_name`,
        `municipality_name`,
        `neighborhood_name`
      )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";

    self::execute_statement( $create_table );
  }

  public static function create_overrides_table()
  {
    global $wpdb;

    $create_table = "
    CREATE TABLE IF NOT EXISTS`". $wpdb->base_prefix . self::OVERRIDES_TABLE ."`
    (
      `override_seq`       MEDIUMINT    UNSIGNED NOT NULL AUTO_INCREMENT,
      `author`             VARCHAR(100)          NOT NULL,
      `insert_on`          TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `gi_state_id`        TINYINT      UNSIGNED NOT NULL DEFAULT 110,
      `gi_province_id`     TINYINT      UNSIGNED NOT NULL,
      `gi_municipality_id` SMALLINT     UNSIGNED NOT NULL,
      `gi_neighborhood_id` MEDIUMINT    UNSIGNED NOT NULL,
      `override`           VARCHAR(255)          NOT NULL,
      PRIMARY KEY ( `override_seq` ),
      UNIQUE INDEX
      (
        `gi_state_id`,
        `gi_province_id`,
        `gi_municipality_id`,
        `gi_neighborhood_id`
      ),
      FOREIGN KEY
      (
        `gi_state_id`,
        `gi_province_id`,
        `gi_municipality_id`,
        `gi_neighborhood_id`
      )
      REFERENCES `". $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`
      (
        `state_id`,
        `province_id`,
        `municipality_id`,
        `neighborhood_id`
      )
      ON UPDATE CASCADE
      ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";

    self::execute_statement( $create_table );
  }

  public static function drop_gi_geodata_table()
  {
    global $wpdb;
    self::execute_statement( "DROP TABLE `". $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`;" );
  }

  public static function drop_overrides_table()
  {
    global $wpdb;
    self::execute_statement( "DROP TABLE `". $wpdb->base_prefix . self::GI_GEODATA_TABLE ."`;" );
  }

  public static function upload_gi_geodata()
  {
    global $wpdb;

    $sql = preg_replace(
      '/%s/',
      $wpdb->base_prefix . self::GI_GEODATA_TABLE,
      self::read_compressed( 'gi-geodata.sql' ),
      1
    );

    return $wpdb->query( $sql );
  }

  private static function execute_statement( $statement )
  {
    if( !function_exists( 'dbDelta' ) )
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $statement );
  }
}
