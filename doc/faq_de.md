# Häufig gestellte Fragen

## Wie kann ich prüfen, ob eine bestimme Objekt ID von Propstack bereitgestellt wird?

Mit dem mitgelieferten WP CLI Kommando geht das sofort: `wp cfprop check_object <ID>`

Alternativ manuell WP CLI:

`wp eval '$r = wp_remote_get( apply_filters( "cfprop_api_object_url", "https://api.propstack.de/v1/units?archived=-1&with_meta=1&property_ids=123456" ), array( "headers" => array( "X-API-KEY" => get_option( "propstack_connector_api_key" ) ) ) ); echo wp_remote_retrieve_response_code( $r ), " ", wp_remote_retrieve_body( $r ), PHP_EOL;'`

"123456" ersetzen durch die gesuchte Objekt ID
