import { createReduxStore, register, select } from '@wordpress/data';

// only register once.
if ( ! select( 'connector-for-propstack/fields' ) ) {
  const store = createReduxStore( 'connector-for-propstack/fields', {
    reducer( state = { fields: [], loaded: false }, action ) {
      if ( action.type === 'SET_FIELDS' ) {
        return { fields: action.fields, loaded: true };
      }
      return state;
    },
    actions: {
      setFields: ( fields ) => ( { type: 'SET_FIELDS', fields } ),
    },
    selectors: {
      getFields: ( state ) => state.fields,
      isLoaded:  ( state ) => state.loaded,
    },
  } );

  register( store );
}

// only register once.
if ( ! select( 'connector-for-propstack/broker-fields' ) ) {
  const store = createReduxStore( 'connector-for-propstack/broker-fields', {
    reducer( state = { fields: [], loaded: false }, action ) {
      if ( action.type === 'SET_FIELDS' ) {
        return { fields: action.fields, loaded: true };
      }
      return state;
    },
    actions: {
      setFields: ( fields ) => ( { type: 'SET_FIELDS', fields } ),
    },
    selectors: {
      getFields: ( state ) => state.fields,
      isLoaded:  ( state ) => state.loaded,
    },
  } );

  register( store );
}
