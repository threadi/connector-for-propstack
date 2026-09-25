jQuery(document).ready(function($) {
  /**
   * Set intro after initial set up of the plugin on list view in backend.
   */
  $('body.post-type-cfprop_object.cfprop-show-intro .table-view-list').each(function() {

    // create the intro tour.
    let intro = window.driver.js.driver( {
      nextBtnText: cfPropIntroJsVars.button_title_next,
      prevBtnText: cfPropIntroJsVars.button_title_back,
      doneBtnText: cfPropIntroJsVars.button_title_done,
      popoverClass: 'cfprop-intro',
      disableActiveInteraction: true,
      overlayClickBehavior: function() {}, // ignore clicks on the overlay.
      steps: [
        {
          popover: {
            title: cfPropIntroJsVars.step_1_title,
            description: cfPropIntroJsVars.step_1_intro,
            showButtons: [ 'next', 'close' ]
          }
        },
        {
          element: 'tr.type-cfprop_object:first-child',
          popover: {
            title: cfPropIntroJsVars.step_2_title,
            description: cfPropIntroJsVars.step_2_intro
          }
        },
        {
          element: 'tr.type-cfprop_object:first-child .column-cfprop-thumbnail',
          popover: {
            title: cfPropIntroJsVars.step_3_title,
            description: cfPropIntroJsVars.step_3_intro
          }
        },
        {
          element: '#screen-meta',
          popover: {
            title: cfPropIntroJsVars.step_4_title,
            description: cfPropIntroJsVars.step_4_intro
          }
        },
        {
          element: '.cfprop-import-hint',
          popover: {
            title: cfPropIntroJsVars.step_5_title,
            description: cfPropIntroJsVars.step_5_intro
          }
        },
        {
          element: '#wp-admin-bar-archive',
          popover: {
            title: cfPropIntroJsVars.step_6_title,
            description: cfPropIntroJsVars.step_6_intro
          }
        },
        {
          element: '#menu-settings a[href="options-general.php?page=connector-for-propstack"]',
          popover: {
            title: cfPropIntroJsVars.step_7_title,
            description: cfPropIntroJsVars.step_7_intro
          }
        },
        {
          popover: {
            title: cfPropIntroJsVars.step_8_title,
            description: cfPropIntroJsVars.step_8_intro,
            popoverClass: 'cfprop-intro cfprop-intro-wide'
          }
        }
      ],

      // prepare every step before it is highlighted: toggle the screen options and the settings flyout.
      onHighlightStarted: function( element ) {
        let panel = $( '#screen-options-wrap' );
        let is_screen_meta = !! element && 'screen-meta' === element.id;

        // open the screen options for their step, close them for every other step.
        if ( is_screen_meta !== panel.is( ':visible' ) ) {
          $( '#screen-options-link-wrap' ).find( 'button' ).trigger( 'click' );
        }

        // open the flyout of the settings menu if the target is inside of it, close it otherwise.
        let settings_menu = $( '#menu-settings' );
        settings_menu.toggleClass( 'opensub', !! element && $.contains( settings_menu[0], element ) );
      },

      // recalculate the highlight after the slide animation of the screen options has been finished.
      onHighlighted: function( element, step, opts ) {
        $( '#screen-options-wrap' ).promise().then( function() {
          opts.driver.refresh();
        } );
      },

      // the close button ends the tour at any time.
      onCloseClick: function( element, step, opts ) {
        opts.driver.destroy();
      },

      // the escape key and the done button end the tour only on the last step.
      onDestroyStarted: function( element, step, opts ) {
        if ( opts.driver.isLastStep() ) {
          opts.driver.destroy();
        }
      },

      // save the exit of the tour.
      onDestroyed: function() {
        // close the settings flyout if the tour ends on its step.
        $( '#menu-settings' ).removeClass( 'opensub' );

        cfprop_intro_exit();
      }
    } );

    // start the tour.
    intro.drive();
  });
});

/**
 * Save the exit of the intro.
 */
function cfprop_intro_exit() {
  jQuery.ajax( {
    type: "POST",
    url: cfPropIntroJsVars.ajax_url,
    data: {
      'action': 'cfprop_intro_closed',
      'nonce': cfPropIntroJsVars.intro_closed_nonce
    },
    error: function( jqXHR, textStatus, errorThrown ) {
      propstack_connector_ajax_error_dialog( errorThrown )
    },
  } )
}
