jQuery(document).ready(function($) {
  /**
   * Set intro after initial set up of the plugin on list view in backend.
   */
  $('body.post-type-cfprop_object.cfprop-show-intro .table-view-list').each(function() {

    // create the intro tour.
    let intro = introJs.tour().setOptions( {
      nextLabel: cfPropIntroJsVars.button_title_next,
      prevLabel: cfPropIntroJsVars.button_title_back,
      doneLabel: cfPropIntroJsVars.button_title_done,
      exitOnEsc: false,
      exitOnOverlayClick: false,
      disableInteraction: true,
      scrollToElement: false,
      steps: [
        {
          title: cfPropIntroJsVars.step_1_title,
          intro: cfPropIntroJsVars.step_1_intro,
        },
        {
          element: document.querySelector( 'tr.type-cfprop_object:first-child'),
          title: cfPropIntroJsVars.step_2_title,
          intro: cfPropIntroJsVars.step_2_intro
        },
        {
          element: document.querySelector( 'tr.type-cfprop_object:first-child .column-cfprop-thumbnail' ),
          title: cfPropIntroJsVars.step_3_title,
          intro: cfPropIntroJsVars.step_3_intro
        },
        {
          element: document.querySelector( '#screen-meta' ),
          title: cfPropIntroJsVars.step_4_title,
          intro: cfPropIntroJsVars.step_4_intro
        },
        {
          element: document.querySelector( '.cfprop-import-hint' ),
          title: cfPropIntroJsVars.step_5_title,
          intro: cfPropIntroJsVars.step_5_intro
        },
        {
          element: document.querySelector( '#wp-admin-bar-archive' ),
          title: cfPropIntroJsVars.step_6_title,
          intro: cfPropIntroJsVars.step_6_intro
        },
        {
          element: document.querySelector( '#menu-settings a[href="options-general.php?page=connector-for-propstack"]' ),
          title: cfPropIntroJsVars.step_7_title,
          intro: cfPropIntroJsVars.step_7_intro
        },
        {
          title: cfPropIntroJsVars.step_8_title,
          intro: cfPropIntroJsVars.step_8_intro,
          tooltipClass: 'intro-width'
        }
      ]
    } );

    // prepare every step before it is shown: toggle the screen options and the settings flyout, and scroll to the element.
    intro.onBeforeChange( async function ( targetElement ) {
      let panel = $( '#screen-options-wrap' );
      let button = $( '#screen-options-link-wrap' ).find( 'button' );
      let is_screen_meta = targetElement && 'screen-meta' === targetElement.id;

      // open the screen options for their step, close them for every other step.
      if ( is_screen_meta !== panel.is( ':visible' ) ) {
        button.trigger( 'click' );

        // wait until the slide animation of WordPress has been finished.
        await panel.promise();
      }

      // open the flyout of the settings menu if the target is inside of it, close it otherwise.
      let settings_menu = $( '#menu-settings' );
      settings_menu.toggleClass( 'opensub', !! targetElement && $.contains( settings_menu[0], targetElement ) );

      // scroll to the element, for steps without an element to the floating helper element of Intro.js.
      if ( targetElement ) {
        targetElement.scrollIntoView( { block: 'center', inline: 'nearest' } );
      }

      return true;
    } );

    // set exit handler, it also runs after a skip.
    intro.onExit( function() {
      // close the settings flyout if the tour ends on its step.
      $( '#menu-settings' ).removeClass( 'opensub' );

      cfprop_intro_exit();
    } );

    // start the tour.
    intro.start();
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
