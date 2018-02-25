<?php

if ( !current_user_can( 'manage_options' ) )
{
  wp_die( __( 'Sorry, you are not allowed to access this page.' ), 403, array( 'back_link' => true ) );
}
?>
<div class="wrap">

    <h2>GI Geodata Override</h2>
    <div id="gigo-tt-form">
      <form name="locationform" method="post">
        <div class="gigo-form-field">
          <h3><label for="from">Location to remap</label></h3>
          <input type="text" name="from" class="gigo-typeahead" placeholder="Type here" />
        </div>
        <div class="gigo-form-field">
          <h3><label for="to">Mapped name</label></h3>
          <input type="text" name="to" class="gigo-text">
        </div>
        <div class="gigo-form-field">
          <h3><label for="action">&nbsp;</label></h3>
          <input type="submit" name="action" class="page-title-action" value="Add">
        </div>
        <input type="hidden" name="state_id" />
        <input type="hidden" name="province_id" />
        <input type="hidden" name="municipality_id" />
        <input type="hidden" name="neighborhood_id" />
      </form>
    </div>
    <div class="gigo-table-area">
      <form name="overrides-filter" method="post">
        <div class="tablenav top">
          <div class="alignleft actions bulkactions">
          <label for="bulk-action-selector-top" class="screen-reader-text">Seleziona l'azione di gruppo</label>
          <select name="action" id="bulk-action-selector-top">
            <option value="-1">Azioni di gruppo</option>
	          <option value="delete">Cancella</option>
          </select>
          <input id="doaction" class="button action" value="Applica" type="submit">
        </div>
        <div class="alignleft actions">
          <label for="filter-by-date" class="screen-reader-text">Filtra per data</label>
          <select name="m" id="filter-by-date">
            <option selected="selected" value="0">Tutte le date</option>
            <option value="201801">gennaio 2018</option>
          </select>
          <input name="filter_action" id="post-query-submit" class="button" value="Filtra" type="submit">
        </div>
      </div>
        <table class="wp-list-table widefat fixed striped pages">
          <thead>
            <tr>
              <td class="manage-column column-cb check-column">
                <label class="screen-reader-text" for="cb-select-all-1">Seleziona tutto</label>
                <input id="cb-select-all-1" type="checkbox">
              </td>
              <th class="manage-column" scope="col">Autore</th>
              <th class="manage-column" scope="col">Cod. Stato</th>
              <th class="manage-column" scope="col">Cod. Provincia</th>
              <th class="manage-column" scope="col">Cod. Città</th>
              <th class="manage-column" scope="col">Cod. Zona</th>
              <th class="manage-column" scope="col">Zona</th>
              <th class="manage-column" scope="col">Sovrascrittura</th>
              <th class="manage-column" scope="col">Data</th>
            </tr>
          </thead>
          <tbody>
<?php foreach( \GIGeodataOverride\Utils\Db::get_overrides() as &$override ) : ?>
            <tr>
              <th class="check-column" scope="row">
                <label class="screen-reader-text" for="cb-select-<?= $override[ 'id' ] ?>">Seleziona (senza titolo)</label>
                <input id="cb-select-<?= $override[ 'id' ] ?>" name="override[]" value="<?= $override[ 'id' ] ?>" type="checkbox">
              </th>
              <td><?= $override[ 'author' ] ?></td>
              <td><?= $override[ 'state_id' ] ?></td>
              <td><?= $override[ 'province_id' ] ?></td>
              <td><?= $override[ 'municipality_id' ] ?></td>
              <td><?= $override[ 'neighborhood_id' ] ?></td>
              <td><?= $override[ 'original_name' ] ?></td>
              <td><?= $override[ 'override' ] ?></td>
              <td><?= date_i18n( get_option( 'date_format' ) .', '. get_option( 'time_format' ), $override[ 'insert_ts' ] ) ?>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="manage-column column-cb check-column">
                <label class="screen-reader-text" for="cb-select-all-1">Seleziona tutto</label>
                <input id="cb-select-all-1" type="checkbox">
              </td>
              <th class="manage-column" scope="col">Autore</th>
              <th class="manage-column" scope="col">Cod. Stato</th>
              <th class="manage-column" scope="col">Cod. Provincia</th>
              <th class="manage-column" scope="col">Cod. Città</th>
              <th class="manage-column" scope="col">Cod. Zona</th>
              <th class="manage-column" scope="col">Zona</th>
              <th class="manage-column" scope="col">Sovrascrittura</th>
              <th class="manage-column" scope="col">Data</th>
            </tr>
          </tfoot>
        </table>
      </form>
    </div>
</div>
