/**
 * Show the maps with the location of objects.
 *
 * Each map is a container with the class "cfprop-map" and its configuration in the attribute
 * "data-cfprop-map". The configuration of the map service is in window.cfpropMapConfig.
 *
 * If the consent is required, a placeholder is shown until the visitor agrees. Consent tools
 * could load all maps by dispatching the event "cfprop-maps-consent" on the document or by
 * calling window.cfpropMaps.loadAll().
 *
 * @package
 */
( function () {
	'use strict';

	// the configuration of the map service.
	const config = window.cfpropMapConfig || {};
	const texts = config.texts || {};

	// the key for the consent in the browser of the visitor.
	const consentKey = 'cfprop_maps_consent';

	// the promise for loading Google Maps (it is loaded only once).
	let googlePromise = null;

	// the promise for loading the clusterer of Google Maps.
	let googleClusterPromise = null;

	/**
	 * Return whether the visitor already allowed all maps.
	 *
	 * @return {boolean} True if allowed.
	 */
	function hasConsent() {
		try {
			return '1' === window.localStorage.getItem( consentKey );
		} catch {
			return false;
		}
	}

	/**
	 * Save that the visitor allowed all maps.
	 */
	function saveConsent() {
		try {
			window.localStorage.setItem( consentKey, '1' );
		} catch {
			// the browser does not allow to save it, the visitor has to agree on the next page again.
		}
	}

	/**
	 * Return the settings of the given map container.
	 *
	 * @param {HTMLElement} element The container.
	 * @return {Object|null} The settings or null if they are invalid.
	 */
	function getSettings( element ) {
		try {
			const settings = JSON.parse(
				element.getAttribute( 'data-cfprop-map' ) || '{}'
			);
			if (
				! settings ||
				! Array.isArray( settings.points ) ||
				0 === settings.points.length
			) {
				return null;
			}
			return settings;
		} catch {
			return null;
		}
	}

	/**
	 * Show an error in the given map container.
	 *
	 * @param {HTMLElement} element The container.
	 */
	function showError( element ) {
		element.innerHTML = '';
		const error = document.createElement( 'p' );
		error.className = 'cfprop-map-error';
		error.textContent = texts.error || 'The map could not be loaded.';
		element.appendChild( error );
	}

	/**
	 * Return the content for the popup of a point.
	 *
	 * The content is created as elements, so texts from the objects are never interpreted as HTML.
	 *
	 * @param {Object} point The point.
	 * @return {HTMLElement} The content.
	 */
	function getPopupContent( point ) {
		const content = document.createElement( 'div' );
		content.className = 'cfprop-map-popup';

		// add the image.
		if ( point.image && /^https?:\/\//.test( point.image ) ) {
			const image = document.createElement( 'img' );
			image.src = point.image;
			image.alt = '';
			image.loading = 'lazy';
			content.appendChild( image );
		}

		// add the title.
		const title = document.createElement( 'strong' );
		title.textContent = point.title || '';
		content.appendChild( title );

		// add the hint for approximate locations.
		if ( point.approximate ) {
			const hint = document.createElement( 'span' );
			hint.textContent = texts.approximate || '';
			content.appendChild( hint );
			content.appendChild( document.createElement( 'br' ) );
		}

		// add the link.
		if ( point.url && /^https?:\/\//.test( point.url ) ) {
			const link = document.createElement( 'a' );
			link.href = point.url;
			link.textContent = texts.show_object || point.url;
			content.appendChild( link );
		}

		return content;
	}

	/**
	 * Create the map with Leaflet and OpenStreetMap.
	 *
	 * @param {HTMLElement} element  The container.
	 * @param {Object}      settings The settings of this map.
	 */
	function createLeafletMap( element, settings ) {
		// bail if Leaflet is not loaded.
		if ( 'undefined' === typeof window.L ) {
			showError( element );
			return;
		}
		const L = window.L;

		// create the map.
		element.innerHTML = '';
		const map = L.map( element, { scrollWheelZoom: false } );
		L.tileLayer(
			config.tile_url || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
			{
				maxZoom: 19,
				attribution: config.attribution || '',
			}
		).addTo( map );

		// enable the zoom by scroll wheel only after a click into the map.
		map.once( 'focus', function () {
			map.scrollWheelZoom.enable();
		} );

		// use a cluster for several markers, if available.
		const markers =
			settings.points.length > 1 &&
			'function' === typeof L.markerClusterGroup
				? L.markerClusterGroup()
				: L.layerGroup();
		const bounds = [];

		settings.points.forEach( function ( point ) {
			const position = [
				parseFloat( point.lat ),
				parseFloat( point.lng ),
			];
			let layer;

			// show an area for approximate locations, a marker otherwise.
			if ( point.approximate ) {
				layer = L.circle( position, {
					radius: settings.radius || config.area_radius || 1000,
				} );
				layer.addTo( map );
			} else {
				layer = L.marker( position, {
					title: point.title || '',
					alt: point.title || '',
				} );
				markers.addLayer( layer );
			}

			// add the popup.
			if ( settings.popup ) {
				layer.bindPopup( getPopupContent( point ) );
			}

			bounds.push( position );
		} );
		markers.addTo( map );

		// show all points.
		const zoom = parseInt( settings.zoom, 10 ) || 15;
		if ( 1 === bounds.length ) {
			map.setView(
				bounds[ 0 ],
				settings.points[ 0 ].approximate ? Math.min( zoom, 14 ) : zoom
			);
		} else {
			map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: zoom } );
		}
	}

	/**
	 * Load a script and return a promise, which is resolved after it has been loaded.
	 *
	 * @param {string} src The URL of the script.
	 * @return {Promise} The promise.
	 */
	function loadScript( src ) {
		return new Promise( function ( resolve, reject ) {
			const script = document.createElement( 'script' );
			script.src = src;
			script.async = true;
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild( script );
		} );
	}

	/**
	 * Load Google Maps (only once).
	 *
	 * @return {Promise} The promise.
	 */
	function loadGoogle() {
		// use the loaded instance.
		if ( window.google && window.google.maps && window.google.maps.Map ) {
			return Promise.resolve();
		}

		// use the running request.
		if ( googlePromise ) {
			return googlePromise;
		}

		googlePromise = new Promise( function ( resolve, reject ) {
			window.cfpropMapsGoogleReady = function () {
				resolve();
			};
			let src =
				'https://maps.googleapis.com/maps/api/js?key=' +
				encodeURIComponent( config.google_api_key || '' ) +
				'&loading=async&callback=cfpropMapsGoogleReady';
			if ( config.google_map_id ) {
				src += '&libraries=marker';
			}
			loadScript( src ).catch( reject );
		} );
		return googlePromise;
	}

	/**
	 * Load the clusterer for Google Maps (only once).
	 *
	 * @return {Promise} The promise.
	 */
	function loadGoogleCluster() {
		if ( window.markerClusterer ) {
			return Promise.resolve();
		}
		if ( ! config.cluster_url ) {
			return Promise.reject();
		}
		if ( ! googleClusterPromise ) {
			googleClusterPromise = loadScript( config.cluster_url );
		}
		return googleClusterPromise;
	}

	/**
	 * Create the map with Google Maps.
	 *
	 * @param {HTMLElement} element  The container.
	 * @param {Object}      settings The settings of this map.
	 */
	function createGoogleMap( element, settings ) {
		loadGoogle()
			.then( function () {
				const google = window.google;
				const zoom = parseInt( settings.zoom, 10 ) || 15;

				// create the map.
				element.innerHTML = '';
				const options = {
					zoom,
					center: {
						lat: parseFloat( settings.points[ 0 ].lat ),
						lng: parseFloat( settings.points[ 0 ].lng ),
					},
					gestureHandling: 'cooperative',
				};
				if ( config.google_map_id ) {
					options.mapId = config.google_map_id;
				}
				const map = new google.maps.Map( element, options );
				const useAdvanced = !! (
					config.google_map_id &&
					google.maps.marker &&
					google.maps.marker.AdvancedMarkerElement
				);
				const infoWindow = new google.maps.InfoWindow();
				const bounds = new google.maps.LatLngBounds();
				const markers = [];

				settings.points.forEach( function ( point ) {
					const position = {
						lat: parseFloat( point.lat ),
						lng: parseFloat( point.lng ),
					};
					let layer;

					// show an area for approximate locations, a marker otherwise.
					if ( point.approximate ) {
						layer = new google.maps.Circle( {
							map,
							center: position,
							radius:
								settings.radius || config.area_radius || 1000,
							strokeOpacity: 0.8,
							strokeWeight: 2,
							fillOpacity: 0.2,
							clickable: !! settings.popup,
						} );
					} else if ( useAdvanced ) {
						layer = new google.maps.marker.AdvancedMarkerElement( {
							position,
							title: point.title || '',
						} );
						markers.push( layer );
					} else {
						layer = new google.maps.Marker( {
							position,
							title: point.title || '',
						} );
						markers.push( layer );
					}

					// add the popup.
					if ( settings.popup ) {
						layer.addListener( 'click', function () {
							infoWindow.setContent( getPopupContent( point ) );
							infoWindow.setPosition( position );
							infoWindow.open( { map } );
						} );
					}

					bounds.extend( position );
				} );

				/**
				 * Add the markers to the map without cluster.
				 */
				const addMarkers = function () {
					markers.forEach( function ( marker ) {
						if ( useAdvanced ) {
							marker.map = map;
						} else {
							marker.setMap( map );
						}
					} );
				};

				// add the markers, use a cluster for several markers.
				if ( markers.length > 1 ) {
					loadGoogleCluster()
						.then( function () {
							new window.markerClusterer.MarkerClusterer( {
								map,
								markers,
							} );
						} )
						.catch( addMarkers );
				} else {
					addMarkers();
				}

				// show all points.
				if ( 1 === settings.points.length ) {
					map.setZoom(
						settings.points[ 0 ].approximate
							? Math.min( zoom, 14 )
							: zoom
					);
				} else {
					map.fitBounds( bounds, 30 );
					google.maps.event.addListenerOnce(
						map,
						'idle',
						function () {
							if ( map.getZoom() > zoom ) {
								map.setZoom( zoom );
							}
						}
					);
				}
			} )
			.catch( function () {
				showError( element );
			} );
	}

	/**
	 * Create the map in the given container.
	 *
	 * @param {HTMLElement} element The container.
	 */
	function initMap( element ) {
		// bail if this map is already loaded.
		if ( element.classList.contains( 'cfprop-map-loaded' ) ) {
			return;
		}

		// get the settings.
		const settings = getSettings( element );
		if ( ! settings ) {
			return;
		}
		element.classList.add( 'cfprop-map-loaded' );

		// create the map with the chosen service.
		try {
			if ( 'google' === config.provider ) {
				createGoogleMap( element, settings );
			} else {
				createLeafletMap( element, settings );
			}
		} catch {
			showError( element );
		}
	}

	/**
	 * Load all maps on this page.
	 */
	function loadAll() {
		document
			.querySelectorAll( '.cfprop-map[data-cfprop-map]' )
			.forEach( initMap );
	}

	/**
	 * Show the placeholder for the consent in the given container.
	 *
	 * @param {HTMLElement} element The container.
	 */
	function showConsent( element ) {
		element.innerHTML = '';

		const consent = document.createElement( 'div' );
		consent.className = 'cfprop-map-consent';
		consent.setAttribute( 'role', 'group' );
		consent.setAttribute( 'aria-label', texts.consent_label || '' );

		const text = document.createElement( 'p' );
		text.textContent = config.consent_text || '';
		consent.appendChild( text );

		const buttons = document.createElement( 'div' );
		buttons.className = 'cfprop-map-consent-buttons';

		const load = document.createElement( 'button' );
		load.type = 'button';
		load.className = 'cfprop-map-load';
		load.textContent = texts.load || 'Load map';
		load.addEventListener( 'click', function () {
			initMap( element );
		} );
		buttons.appendChild( load );

		const always = document.createElement( 'button' );
		always.type = 'button';
		always.className = 'cfprop-map-load-always';
		always.textContent = texts.load_always || 'Always load maps';
		always.addEventListener( 'click', function () {
			saveConsent();
			loadAll();
		} );
		buttons.appendChild( always );

		consent.appendChild( buttons );
		element.appendChild( consent );
	}

	/**
	 * Initialize all maps on this page.
	 */
	function init() {
		document
			.querySelectorAll( '.cfprop-map[data-cfprop-map]' )
			.forEach( function ( element ) {
				element.setAttribute( 'role', 'region' );
				element.setAttribute( 'aria-label', texts.map_label || 'Map' );
				if ( config.consent && ! hasConsent() ) {
					showConsent( element );
				} else {
					initMap( element );
				}
			} );
	}

	// make the functions available for consent tools.
	window.cfpropMaps = {
		loadAll,
		init,
	};
	document.addEventListener( 'cfprop-maps-consent', loadAll );

	// initialize the maps.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
