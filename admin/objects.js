/**
 * Initiate the import of objects.
 */
function propstack_connector_object_import( process_id ) {
  propstack_connector_start_ajax_process( {
    'start_action': 'cfprop_import_objects',
    'start_nonce': propstackConnectorImportJsVars.start_nonce,
    'info_action': 'cfprop_process_info',
    'info_nonce': propstackConnectorImportJsVars.process_info_nonce,
    'process_title': propstackConnectorImportJsVars.title,
    'process_id': process_id
  });
}

/**
 * Initiate the import of files, optional for a single object.
 */
function propstack_connector_object_files_import( process_id, post_id ) {
  propstack_connector_start_ajax_process( {
    'start_action': 'cfprop_import_object_files',
    'start_nonce': propstackConnectorImportJsVars.start_files_nonce,
    'info_action': 'cfprop_process_info',
    'info_nonce': propstackConnectorImportJsVars.process_info_nonce,
    'process_title': propstackConnectorImportJsVars.files_title,
    'post': post_id,
    'process_id': process_id
  });
}

/**
 * Process the queue.
 */
function propstack_connector_queue_process( process_id, post_id ) {
  propstack_connector_start_ajax_process( {
    'start_action': 'cfprop_queue_process',
    'start_nonce': propstackConnectorImportJsVars.start_queue_processing_nonce,
    'info_action': 'cfprop_process_info',
    'info_nonce': propstackConnectorImportJsVars.process_info_nonce,
    'process_title': propstackConnectorImportJsVars.queue_title,
    'post': post_id,
    'process_id': process_id
  });
}


/**
 * Initiate the deletion of objects.
 */
function propstack_connector_object_delete( process_id ) {
  propstack_connector_start_ajax_process( {
    'start_action': 'cfprop_delete_objects',
    'start_nonce': propstackConnectorImportJsVars.delete_start_nonce,
    'info_action': 'cfprop_process_info',
    'info_nonce': propstackConnectorImportJsVars.process_info_nonce,
    'process_title': propstackConnectorImportJsVars.delete_title,
    'process_id': process_id
  });
}

/**
 * Initiate the deletion of object files.
 */
function propstack_connector_object_file_delete( process_id ) {
  propstack_connector_start_ajax_process( {
    'start_action': 'cfprop_delete_object_files',
    'start_nonce': propstackConnectorImportJsVars.delete_files_start_nonce,
    'info_action': 'cfprop_process_info',
    'info_nonce': propstackConnectorImportJsVars.process_info_nonce,
    'process_title': propstackConnectorImportJsVars.delete_files_title,
    'process_id': process_id
  });
}

let import_running = false;
// marker for progress timeout.
let propstack_connector_progress_timeout = false;

/**
 * Function to handle any AJAX process.
 */
function propstack_connector_start_ajax_process( config ) {
  // start the process.
  jQuery.ajax({
    type: "POST",
    url: propstackConnectorImportJsVars.ajax_url,
    data: {
      'action': config.start_action,
      'nonce': config.start_nonce,
      'post': config.post,
      'process_id': config.process_id
    },
    beforeSend: function() {
      // show progress.
      let dialog_config = {
        detail: {
          className: 'cfprop-dialog',
          title: config.process_title,
          progressbar: {
            active: true,
            progress: 0,
            id: 'progress',
            label_id: 'progress_status'
          },
        }
      }
      propstack_connector_create_dialog( dialog_config );

      // mark in JS as running.
      import_running = true;

      // get info about progress.
      propstack_connector_progress_timeout = setTimeout(function() { propstack_connector_ajax_process( config ) }, 1000 );
    },
    success: function( response ) {
      if( response.load_more ) {
        clearInterval(propstack_connector_progress_timeout);
        propstack_connector_start_ajax_process( config );
      }
      else {
        // mark import as not running.
        import_running = false;
      }
    },
    error: function( jqXHR, textStatus, errorThrown ) {
      // mark import as not running.
      import_running = false;
      propstack_connector_ajax_error_dialog( errorThrown )
    }
  });
}

/**
 * Get info until import is done.
 */
function propstack_connector_ajax_process( config ) {
  jQuery.ajax( {
    type: "POST",
    url: propstackConnectorImportJsVars.ajax_url,
    data: {
      'action': config.info_action,
      'nonce': config.info_nonce,
      'process_id': config.process_id
    },
    error: function( jqXHR, textStatus, errorThrown ) {
      propstack_connector_ajax_error_dialog( errorThrown )
    },
    success: function (data) {
      let count = parseInt( data[0] );
      let max = parseInt( data[1] );
      let running = parseInt( data[2] );
      let status = data[3];
      let dialog_config = JSON.parse( data[4] );

      // show progress.
      jQuery( '#progress' ).attr( 'value', (count / max) * 100 );
      jQuery( '#progress_status' ).html( status );

      /**
       * If import is still running, get next info in 500ms.
       * If import is not running and error occurred, show the error.
       * If import is not running and no error occurred, show ok-message.
       */
      if (running >= 1 || import_running) {
        setTimeout( function () {
          propstack_connector_ajax_process( config )
        }, 500 );
      } else {
        propstack_connector_create_dialog( dialog_config );
      }
    }
  } )
}
