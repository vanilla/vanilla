<?php if (!defined('APPLICATION')) exit();
/**
 * Garden Constants.
 */

// If you want to change where the garden libraries are located on your server, edit these paths:
define('PATH_APPLICATIONS', PATH_ROOT . DS . 'applications');
define('PATH_CACHE', PATH_ROOT . DS . 'cache');
define('PATH_LIBRARY', PATH_ROOT . DS . 'library');
define('PATH_PLUGINS', PATH_ROOT . DS . 'plugins');
define('PATH_THEMES', PATH_ROOT . DS . 'themes');
define('PATH_UPLOADS', PATH_ROOT . DS . 'uploads');

// Delivery type enumerators:
define('DELIVERY_TYPE_ALL', 'ALL'); // Deliver an entire page
define('DELIVERY_TYPE_ASSET', 'ASSET'); // Deliver all content for the requested asset
define('DELIVERY_TYPE_VIEW', 'VIEW'); // Deliver only the view
define('DELIVERY_TYPE_BOOL', 'BOOL'); // Deliver only the success status (or error) of the request
define('DELIVERY_TYPE_NONE', 'NONE'); // Deliver nothing
define('DELIVERY_TYPE_MESSAGE', 'MESSAGE'); // Just deliver messages.
define('DELIVERY_TYPE_DATA', 'DATA'); // Just deliver the data.

// Delivery method enumerators
define('DELIVERY_METHOD_XHTML', 'XHTML');
define('DELIVERY_METHOD_JSON', 'JSON');
define('DELIVERY_METHOD_XML', 'XML');

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

// Environment
define('ENVIRONMENT_PHP_VERSION','5.2.0');

if (!defined('E_USER_DEPRECATED'))
   define('E_USER_DEPRECATED', E_USER_WARNING);