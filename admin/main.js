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
   * Open an entry of the WordPress help via links with the class "cfprop-open-help".
   *
   * The link names the entry via the attribute "data-help-entry" or via an anchor like "#cfprop-help-insufficient_permissions" as href.
   * If the entry is not available on the actual page, the link is followed (it leads to the settings, where the entry is opened).
   */
  $( document ).on( 'click', 'a.cfprop-open-help', function ( e ) {
    let entry = $( this ).data( 'help-entry' );
    if ( ! entry ) {
      let href = $( this ).attr( 'href' ) || '';
      if ( href.indexOf( '#cfprop-help-' ) === 0 ) {
        entry = href.substring( 13 );
      }
    }

    // bail if the entry is not available on this page.
    if ( ! entry || ! cfprop_open_help_entry( entry ) ) {
      return;
    }

    e.preventDefault();
  } );

  /**
   * Open the help entry which is requested via the URL parameter "cfprop_help".
   */
  let requested_help = new URLSearchParams( window.location.search ).get( 'cfprop_help' );
  if ( requested_help && /^[a-z0-9_]+$/.test( requested_help ) ) {
    cfprop_open_help_entry( requested_help );
  }

  /**
   * Copy strings via click.
   */
  $("body.post-type-cfprop_object code, .etfw-transient code, body.settings_page_connector-for-propstack code").on("click", function() {
    $(this).removeClass("copied");
    if( propstack_connector_copy_to_clipboard($(this).html().trim()) ) {
      $(this).addClass("copied");
    }
  });
});

/**
 * Bind events in DataView.
 */
( function ( hooks ) {
  'use strict';

  hooks.addAction(
    'esfw.settingsPage.mounted',
    'connector-pro-propstack/copy',
    function () {
      jQuery( document ).on(
        "click",
        "body.settings_page_connector-for-propstack code",
        function() {
          jQuery(this).removeClass("copied");
          if( propstack_connector_copy_to_clipboard(jQuery(this).html().trim()) ) {
            jQuery(this).addClass("copied");
          }
        }
      );
    }
  );

} )( wp.hooks );

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

/**
 * Open the WordPress help and show the given entry.
 *
 * Opens the help, shows the tab which contains the entry, expands the entry and scrolls to it.
 *
 * @param {string} entry The internal name of the entry.
 * @returns {boolean} True if the entry exists on this page.
 */
function cfprop_open_help_entry( entry ) {
  let entry_obj = document.getElementById( 'cfprop-help-' + entry );

  // bail if the entry is not available on this page.
  if ( ! entry_obj ) {
    return false;
  }

  // get the tab which contains the entry.
  let panel = entry_obj.closest( '.help-tab-content' );
  if ( ! panel ) {
    return false;
  }
  let tab_link = jQuery( '#' + CSS.escape( panel.id.replace( 'tab-panel-', 'tab-link-' ) ) + ' a' );

  // open the help if it is closed.
  if ( ! jQuery( '#contextual-help-wrap' ).is( ':visible' ) ) {
    jQuery( '#contextual-help-link' ).trigger( 'click' );
  }

  // show the tab.
  tab_link.trigger( 'click' );

  // collapse the other entries of this tab, expand the requested entry and mark it.
  panel.querySelectorAll( 'details.cfprop-knowledge-center-entry[open]' ).forEach( function ( details ) {
    if ( details !== entry_obj ) {
      details.open = false;
    }
  } );
  entry_obj.open = true;
  entry_obj.classList.remove( 'cfprop-highlight' );
  void entry_obj.offsetWidth;
  entry_obj.classList.add( 'cfprop-highlight' );

  // scroll to the entry after the help has been opened.
  setTimeout( function () {
    entry_obj.scrollIntoView( { behavior: 'smooth', block: 'start' } );
  }, 300 );
  return true;
}
