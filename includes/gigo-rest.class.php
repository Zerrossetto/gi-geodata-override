<?php
namespace GIGeodataOverride;

class Rest
{
    use Utils\Logging;

    public static function query_gi_geodata( $request )
    {
      $params = $request->get_json_params();

      if( !array_key_exists( 'query', $params ) )
      {
        return new \WP_REST_Response(
          array(
            'code' => 'GIGO-01',
            'error' => 'Parameter \'query\' not found in request'
          ),
          400
        );
      }

      if( array_key_exists( 'limit', $params ) )
      {
        if( array_key_exists( 'search', $params ) && $params[ 'search' ] === 'contained' )
          $result = Rest::search_containing_term( $params[ 'query' ], $params[ 'limit' ] );
        else
          $result = Rest::search_fulltext_term( $params[ 'query' ], $params[ 'limit' ] );
      }
      else
      {
        if( array_key_exists( 'search', $params ) && $params[ 'search' ] === 'contained' )
          $result = Rest::search_containing_term( $params[ 'query' ]);
        else
          $result = Rest::search_fulltext_term( $params[ 'query' ]);
      }

      return new \WP_REST_Response( $result, 200 );
    }

    private static function encode_unique_hash ( $record )
    {
      return hash(
        'sha256',
        sprintf(
          '%03u%03u%05u%05u',
          $record[ 'state_id' ],
          $record[ 'province_id' ],
          $record[ 'municipality_id' ],
          $record[ 'neighborhood_id' ]
        )
      );
    }

    private static function search_fulltext_term( $query, $limit = 10 )
    {
      $resultset = array();
      $relevance = array(
        'province' => 0,
        'municipality' => 0,
        'neighborhood_name' => 0,
      );

      foreach( \GIGeodataOverride\Utils\Db::search_geodata_fulltext( $query, $limit ) as &$match ) {

        $record = array(
          'match_type'      => 'neighborhood',
          'neighborhood_id' => $match[ 'neighborhood_id' ],
          'municipality_id' => $match[ 'municipality_id' ],
          'province_id'     => $match[ 'province_id' ],
          'state_id'        => $match[ 'state_id' ],
          'matching_term'   => $match[ 'neighborhood_name' ],
          'municipality'    => $match[ 'municipality_name' ],
          'province'        => $match[ 'province_name' ]
        );
        $record[ 'hash' ] = self::encode_unique_hash( $record );
        $resultset[] = $record;
      }

      return $resultset;
    }

    private static function search_containing_term( $query, $limit = 10 )
    {
      $resultset = \GIGeodataOverride\Utils\Db::search_geodata_containing( $query, $limit );
      foreach ( $resultset as &$record )
          $record[ 'hash' ] = self::encode_unique_hash( $record );
      return $resultset;
    }
}
