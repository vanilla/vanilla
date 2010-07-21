<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

$WebRoot = array_key_exists('PHP_SELF', $_SERVER) ? $_SERVER['PHP_SELF'] : '';
$WebRoot = explode('/', $WebRoot);
// Look for plugins in plugins/Minify/min/index.php to figure out where the web root is.
$Key = array_search('plugins', $WebRoot);
if ($Key !== FALSE)
   $WebRoot = implode('/', array_slice($WebRoot, 0, $Key));

$WebRoot = trim($WebRoot,'/');
if ($WebRoot != '')
   $WebRoot = '//'.$WebRoot.'/';
else
   $WebRoot = '//';
   
return array(
   'globaljs' => array(
      $WebRoot.'js/library/jquery.js',
      $WebRoot.'js/library/jquery.livequery.js',
      $WebRoot.'js/library/jquery.form.js',
      $WebRoot.'js/library/jquery.popup.js',
      $WebRoot.'js/library/jquery.gardenhandleajaxform.js',
      $WebRoot.'js/global.js'
   )
   
    // 'js' => array('//js/file1.js', '//js/file2.js'),
    // 'css' => array('//css/file1.css', '//css/file2.css'),

    // custom source example
    /*'js2' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => create_function('$a', 'return $a;')
        ))
    ),//*/

    /*'js3' => array(
        dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
        // do NOT process this file
        new Minify_Source(array(
            'filepath' => dirname(__FILE__) . '/../min_unit_tests/_test_files/js/before.js',
            'minifier' => array('Minify_Packer', 'minify')
        ))
    ),//*/
);
