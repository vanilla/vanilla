<?php if (!defined('APPLICATION')) exit();
/**
 * Framework constants.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

// If you want to change where these paths are located on your server, edit these constants.
if (!defined('PATH_CACHE')) {
    define('PATH_CACHE', PATH_ROOT.'/cache');
}
if (!defined('PATH_UPLOADS')) {
    define('PATH_UPLOADS', PATH_ROOT.DS.'uploads');
}

// You should not change these paths.
// The dist directory is no longer purely "/dist" because some way of invalidating all assets is required.
// /dist assets rely on a content hash to invalidate, but there are situations where an item is cached incorrectly
// And the we need to bulk invalidate without
define('PATH_DIST_NAME', 'dist/v1');
define('PATH_DIST', PATH_ROOT.'/'.PATH_DIST_NAME);
define('PATH_ADDONS_THEMES', PATH_ROOT.'/addons/themes');
define('PATH_ADDONS_ADDONS', PATH_ROOT.'/addons/addons');
define('PATH_APPLICATIONS', PATH_ROOT.'/applications');
define('PATH_PLUGINS', PATH_ROOT.'/plugins');
define('PATH_THEMES', PATH_ROOT.'/themes');
define('PATH_LIBRARY', PATH_ROOT.'/library');
define('PATH_LIBRARY_CORE', PATH_LIBRARY.'/core');

// Delivery type enumerators:
define('DELIVERY_TYPE_ALL', 'ALL'); // Deliver an entire page
define('DELIVERY_TYPE_ASSET', 'ASSET'); // Deliver all content for the requested asset
define('DELIVERY_TYPE_VIEW', 'VIEW'); // Deliver only the view
define('DELIVERY_TYPE_BOOL', 'BOOL'); // Deliver only the success status (or error) of the request
/**  @deprecated - Use dashboard\views\utility\raw.twig */
define('DELIVERY_TYPE_NONE', 'NONE'); // Deliver nothing
define('DELIVERY_TYPE_MESSAGE', 'MESSAGE'); // Just deliver messages.
define('DELIVERY_TYPE_DATA', 'DATA'); // Just deliver the data.

// Delivery method enumerators
define('DELIVERY_METHOD_XHTML', 'XHTML');
define('DELIVERY_METHOD_JSON', 'JSON');
define('DELIVERY_METHOD_XML', 'XML');
/**
 * @since 2.1
 */
define('DELIVERY_METHOD_TEXT', 'TXT');
define('DELIVERY_METHOD_PLAIN', 'PLAIN');
define('DELIVERY_METHOD_RSS', 'RSS');
define('DELIVERY_METHOD_ATOM', 'ATOM');

// Handler enumerators:
define('HANDLER_TYPE_NORMAL', 'NORMAL'); // Standard call to a method on the object.
define('HANDLER_TYPE_EVENT', 'EVENT'); // Call to an event handler.
define('HANDLER_TYPE_OVERRIDE', 'OVERRIDE'); // Call to a method override.
define('HANDLER_TYPE_NEW', 'NEW'); // Call to a new object method.

// Dataset type enumerators:
define('DATASET_TYPE_ARRAY', 'array');
define('DATASET_TYPE_OBJECT', 'object');

// Syndication enumerators:
define('SYNDICATION_NONE', 'NONE');
define('SYNDICATION_RSS', 'RSS');
define('SYNDICATION_ATOM', 'ATOM');

// Debug error types.
define('TRACE_INFO', 'info');
define('TRACE_ERROR', 'error');
define('TRACE_WARNING', 'warning');
define('TRACE_NOTICE', 'notice');

if (!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', E_USER_WARNING);
}

define('SPAM', 'SPAM');
define('UNAPPROVED', 'UNAPPROVED');

// Numeric keys for Vanilla's addon types.
define('ADDON_TYPE_PLUGIN', 1);
define('ADDON_TYPE_THEME', 2);
define('ADDON_TYPE_LOCALE', 4);
define('ADDON_TYPE_APPLICATION', 5);
define('ADDON_TYPE_CORE', 10);

// Use this constant if you are sick of looking up how to format dates to go into the database.
const MYSQL_DATE_FORMAT = 'Y-m-d H:i:s';

// Signal we did all this ^.
define('VANILLA_CONSTANTS', true);
