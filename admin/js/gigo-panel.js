var giGeodataRemote = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('hash'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  identify: function(datum) { return datum.hash; },
  remote: {
    url: '/wp-json/gigo/v1/gi-geodata',
    prepare: function(query, settings) {
      settings.headers = { 'X-WP-Nonce': gigoSettings.nonce };
      settings.type = 'POST';
      settings.dataType = 'json';
      settings.contentType = 'application/json';
      settings.data = JSON.stringify({ query: query, limit: 10 });
      return settings;
    }
  }
});

jQuery(document).ready(function($) {

  var $tt = $('#gigo-tt-form .gigo-typeahead');
  $tt.typeahead({
    minLength: 3,
    highlight: true
  },
  {
    name: 'gi-geodata',
    source: giGeodataRemote,
    display: 'matching_term',
    templates: {
      empty: ['<div class="empty-message">', gigoSettings.noResultsMessage, '</div>'].join('\n'),
      suggestion: function(datum) {
        var locTypeHint = '<span class="tt-hint-location-type">'+
                          datum.municipality +' ('+ datum.province +')'+
                          '</span>';
        return '<div>'+ datum.matching_term +' '+ locTypeHint +'</div>';
      }
    }
  });

  $tt.on('typeahead:select', function(evt, datum) {
    var form = document.forms.locationform;
    form.state_id.value = datum.state_id;
    form.province_id.value = datum.province_id;
    form.municipality_id.value = datum.municipality_id;
    form.neighborhood_id.value = datum.neighborhood_id;
  });


});
