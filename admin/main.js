jQuery(document).ready(function($) {
  // add option near to list-headline.
  $( 'body.post-type-cfprop_object:not(.cfprop-hide-buttons) h1.wp-heading-inline, body.settings_page_connector-for-propstack:not(.cfprop-hide-buttons) h1.wp-heading-inline' ).after( '<a class="page-title-action cfprop-pro-hint" href="' + propstackConnectorJsVars.pro_url + '" target="_blank">' + propstackConnectorJsVars.title_get_pro + '</a>' );
  $( 'body.post-type-cfprop_object h1.wp-heading-inline' ).after( '<a class="page-title-action cfprop-import-hint" href="' + propstackConnectorJsVars.import_url + '">' + propstackConnectorJsVars.title_run_import + '</a>' );
  $( 'body.settings_page_propstack-connector h1.wp-heading-inline' ).after( '<a class="page-title-action cfprop-objects" href="' + propstackConnectorJsVars.objects_url + '">' + propstackConnectorJsVars.title_objects + '</a>' );
  $( 'body.post-type-cfprop_object:not(.cfprop-hide-buttons) h1.wp-heading-inline, body.settings_page_connector-for-propstack:not(.cfprop-hide-buttons) h1.wp-heading-inline' ).each( function () {
    let button = document.createElement( 'a' );
    button.className = 'review-hint-button page-title-action';
    button.href = propstackConnectorJsVars.review_url;
    button.innerHTML = propstackConnectorJsVars.title_rate_us;
    button.target = '_blank';
    this.after( button );
  } )

  /**
   * Get the content for the dialog via AJAX for dynamic content-changes.
   */
  $('a.cfprop-import-hint').on('click', function (e) {
    e.preventDefault();
    cfprop_get_import_dialog();
  });

  /**
   * Copy strings via click.
   */
  $("body.post-type-cfprop_object code").on("click", function() {
    $(this).removeClass("copied");
    if( propstack_connector_copy_to_clipboard($(this).html().trim()) ) {
      $(this).addClass("copied");
    }
  });
});

/**
 * Get the import dialog via AJAX.
 */
function cfprop_get_import_dialog() {
  // get the dialog via AJAX.
  jQuery.ajax({
    type: "POST",
    url: propstackConnectorJsVars.ajax_url,
    data: {
      'action': 'cfprop_get_import_dialog',
      'nonce': propstackConnectorJsVars.get_import_dialog_nonce
    },
    error: function( jqXHR, textStatus, errorThrown ) {
      propstack_connector_ajax_error_dialog( errorThrown )
    },
    success: function( result ) {
      propstack_connector_create_dialog( result );
    }
  });
}

/**
 * Helper to create a new dialog with the given config.
 *
 * @param config
 */
function propstack_connector_create_dialog( config ) {
  document.body.dispatchEvent(new CustomEvent("easy-dialog-for-wordpress", config));
}


/**
 * Define dialog for AJAX-errors.
 */
function propstack_connector_ajax_error_dialog( errortext, texts ) {
  if( errortext === undefined || errortext.length === 0 ) {
    errortext = propstackConnectorJsVars.generell_error_text;
  }
  let message = '<p>' + propstackConnectorJsVars.txt_error + '</p>';
  message = message + '<ul>';
  if( texts && texts[errortext] ) {
    message = message + '<li>' + texts[errortext] + '</li>';
  }
  else {
    message = message + '<li>' + errortext + '</li>';
  }
  message = message + '</ul>';

  // show dialog.
  let dialog_config = {
    detail: {
      title: propstackConnectorJsVars.title_error,
      texts: [
        message
      ],
      buttons: [
        {
          'action': 'location.reload();',
          'variant': 'primary',
          'text': propstackConnectorJsVars.lbl_ok
        }
      ]
    }
  }
  propstack_connector_create_dialog( dialog_config );
}

/**
 * Copy a given text to the clipboard.
 *
 * @param text The text to copy.
 */
function propstack_connector_copy_to_clipboard( text ) {
  let helper = document.createElement("textarea");
  document.body.appendChild(helper);
  helper.value = text.replace(/(<([^>]+)>)/gi, "");
  helper.select();
  if( document.execCommand("copy") ) {
    document.body.removeChild(helper);
    return true;
  }
  document.body.removeChild(helper);
  return false;
}
