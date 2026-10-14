# Using WP-CLI

## Overview

WP-CLI executes WordPress commands via the console. These commands should only be run if you have the appropriate technical knowledge. The plugin provides a wide range of commands for quickly managing objects in the database.

# Main command

Display a list of available commands for this plugin:

`wp cfprop`

# Commands

`wp cfprop check_object <ID>`
=> checks whether the Propstack API delivers a specific object for the import, without importing anything. Shows also whether one of your restrictions would prevent its import. `<ID>` is the Propstack-ID of the object; use `--field=unit_id` or `--field=exposee_id` to search by another ID, and `--full-scan` to check with exactly the requests the import uses

`wp cfprop clear_queue`
=> clears the queue without processing it

`wp cfprop delete_imported_files`
=> deletes all files imported from Propstack

`wp cfprop delete_objects`
=> deletes all objects imported from Propstack

`wp cfprop import_files`
=> imports any object files that have not yet been imported

`wp cfprop import_objects`
=> imports objects from Propstack

`wp cfprop process_queue`
=> processes queue entries

`wp cfprop reset_plugin`
=> resets the entire plugin

# Additional commands in the Pro plugin

`wp cfprop import_object_states`
`wp cfprop delete_object_states`
`wp cfprop import_broker`
`wp cfprop delete_broker`

# Note

Depending on your hosting system, these commands must be executed within the user context of your website.
