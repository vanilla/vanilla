Any javascript files that are specific to this application can be placed in this
folder. They should be named after the application, controller, or controller
method that they are required for. For example:

entry.js: should be included in every page of the "entry" controller.
entry_apply.js: should be included only on the entry.apply() page.

Note 1: these are simply guidelines - you can name any file whatever you want and
include it anywhere you want.

Note 2: The $Head object becomes a property of the controller, so you can add a
js file to the controller with:
   $this->Head->AddScript('/appname/js/filename.js');