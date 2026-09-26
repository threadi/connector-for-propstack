/**
 * Add a filter for object images in the grid view of the media library.
 */
( function() {
  // bail if the media views are not available.
  if ( typeof wp === 'undefined' || ! wp.media || ! wp.media.view || ! wp.media.view.AttachmentsBrowser ) {
    return;
  }

  // define the filter.
  let CfpropMediaFilter = wp.media.view.AttachmentFilters.extend( {
    id: 'cfprop-media-filter',

    createFilters: function() {
      let all_props = {};
      all_props[ cfpropMediaLibraryJsVars.filter_name ] = '';
      let object_props = {};
      object_props[ cfpropMediaLibraryJsVars.filter_name ] = cfpropMediaLibraryJsVars.filter_value;

      this.filters = {
        all: {
          text: cfpropMediaLibraryJsVars.all,
          props: all_props,
          priority: 10
        },
        objects: {
          text: cfpropMediaLibraryJsVars.objects,
          props: object_props,
          priority: 20
        }
      };
    }
  } );

  // add the filter to the toolbar of the media library.
  let AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
  wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend( {
    createToolbar: function() {
      AttachmentsBrowser.prototype.createToolbar.apply( this, arguments );

      this.toolbar.set( 'cfpropMediaFilterLabel', new wp.media.view.Label( {
        value: cfpropMediaLibraryJsVars.label,
        attributes: {
          'for': 'cfprop-media-filter'
        },
        priority: -76
      } ).render() );

      this.toolbar.set( 'cfpropMediaFilter', new CfpropMediaFilter( {
        controller: this.controller,
        model: this.collection.props,
        priority: -75
      } ).render() );
    }
  } );
} )();
