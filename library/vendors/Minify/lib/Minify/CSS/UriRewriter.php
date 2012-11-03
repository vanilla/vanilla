<?php
/**
 * Class Minify_CSS_UriRewriter  
 * @package Minify
 */

/**
 * Rewrite file-relative URIs as root-relative in CSS files
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_CSS_UriRewriter {
    
    /**
     * Defines which class to call as part of callbacks, change this
     * if you extend Minify_CSS_UriRewriter
     * @var string
     */
    protected static $className = 'Minify_CSS_UriRewriter';
    
    /**
     * rewrite() and rewriteRelative() append debugging information here
     * @var string
     */
    public static $debugText = '';
    
    /**
     * Rewrite file relative URIs as root relative in CSS files
     * 
     * @param string $css
     * 
     * @param string $currentDir The directory of the current CSS file.
     * 
     * @param string $docRoot The document root of the web site in which 
     * the CSS file resides (default = $_SERVER['DOCUMENT_ROOT']).
     * 
     * @return string
     */
    public static function rewrite($css, $currentDir, $docRoot = null, $prependPath = null) 
    {
       
        self::$_docRoot = self::_realpath(
            $docRoot ? $docRoot : $_SERVER['DOCUMENT_ROOT']
        );
        self::$_currentDir = self::_realpath($currentDir);
        self::$_prependPath = $prependPath ? $prependPath : '';
        
        self::$debugText .= "docRoot    : " . self::$_docRoot . "\n"
                          . "currentDir : " . self::$_currentDir . "\n";
//        if (self::$_symlinks) {
//            self::$debugText .= "symlinks : " . var_export(self::$_symlinks, 1) . "\n";
//        }
        self::$debugText .= "\n";
        
        $css = self::_trimUrls($css);
        
        // rewrite
        $css = preg_replace_callback('/@import\s+(?:url)(.+);/'
            ,array(self::$className, '_processImportUriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array(self::$className, '_processUriCB'), $css);

        return $css;
    }
    
    /**
     * Prepend a path to relative URIs in CSS files
     * 
     * @param string $css
     * 
     * @param string $path The path to prepend.
     * 
     * @return string
     */
    public static function prepend($css, $path)
    {
        self::$_prependPath = $path;
        
        $css = self::_trimUrls($css);
        
        // append
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'
            ,array(self::$className, '_processUriCB'), $css);
        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
            ,array(self::$className, '_processUriCB'), $css);

        self::$_prependPath = null;
        return $css;
    }
    
    
    /**
     * @var string directory of this stylesheet
     */
    private static $_currentDir = '';
    
    /**
     * @var string DOC_ROOT
     */
    private static $_docRoot = '';
    
    /**
     * @var array directory replacements to map symlink targets back to their
     * source (within the document root) E.g. '/var/www/symlink' => '/var/realpath'
     */
//    private static $_symlinks = array();
    
    /**
     * @var string path to prepend
     */
    private static $_prependPath = null;
    
    private static function _trimUrls($css)
    {
        return preg_replace('/
            url\\(      # url(
            \\s*
            ([^\\)]+?)  # 1 = URI (assuming does not contain ")")
            \\s*
            \\)         # )
        /x', 'url($1)', $css);
    }
    
        private static function _processImportUriCB($m)
    {
       $uri = trim($m[1], '()"\' ');
       
       // We want to grab the import.
       if (strpos($uri, '//') !== false) {
          $path = $uri;
       } elseif ($uri[0] == '/') {
          $path = self::_realpath(self::$_docRoot, $uri);
       } else {
          $path = realpath2(self::$_currentDir.'/'.trim($uri, '/\\'));
          
          if (substr_compare(self::$_docRoot, $path, 0, strlen($path)) != 0) {
            return "/* Error: $uri isn't in the webroot. */\n";
          } elseif (substr_compare($path, '.css', -4, 4, true) != 0) {
             return "/* Error: $uri must end in .css. */\n";
          }
       }
       $css = file_get_contents($path);
       // Not so fast, we've got to rewrite this file too. What's more, the current dir and path are different.
       
       $bak = array(self::$_currentDir, self::$_prependPath, self::$_docRoot, self::$debugText);
       
       self::$debugText = '';
       
       if (IsUrl($path)) {
          $newCurrentDir = $path;
          $newDocRoot = $path;
       } else {
          $newDocRoot = self::$_docRoot;
         $newCurrentDir = realpath2($currentDirBak.realpath2(dirname($uri)));
       }
       
       $css = self::rewrite($css, $newCurrentDir, $newDocRoot);
       
       list(self::$_currentDir, self::$_prependPath, self::$_docRoot, self::$debugText) = $bak;
       
       return "/* @include url('$uri'); */\n".
         $css;
    }
    
    private static function _processUriCB($m)
    {
        // $m matched either '/@import\\s+([\'"])(.*?)[\'"]/' or '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = ($m[0][0] === '@');
        // determine URI and the quote character (if any)
        if ($isImport) {
            $quoteChar = $m[1];
            $uri = $m[2];
        } else {
            // $m[1] is either quoted or not
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"')
                ? $m[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $m[1]
                : substr($m[1], 1, strlen($m[1]) - 2);
        }
        // analyze URI
        if ('/' !== $uri[0]                  // root-relative
            && false === strpos($uri, '//')  // protocol (non-data)
            && 0 !== strpos($uri, 'data:')   // data protocol
        ) {
            // URI is file-relative: rewrite depending on options
           
           if (self::$_prependPath !== null)
              $uri = self::$_prependPath . $uri;
           else
              $uri = self::rewriteRelative($uri, self::$_currentDir, self::$_docRoot);
        }
        $result = $isImport
            ? "@import {$quoteChar}{$uri}{$quoteChar}"
            : "url({$quoteChar}{$uri}{$quoteChar})";
            
         return $result;
    }
    
    /**
     * Rewrite a file relative URI as root relative
     *
     * <code>
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       '../img/hello.gif'
     *     , '/home/user/www/css'  // path of CSS file
     *     , '/home/user/www'      // doc root
     * );
     * // returns '/img/hello.gif'
     * 
     * // example where static files are stored in a symlinked directory
     * Minify_CSS_UriRewriter::rewriteRelative(
     *       'hello.gif'
     *     , '/var/staticFiles/theme'
     *     , '/home/user/www'
     *     , array('/home/user/www/static' => '/var/staticFiles')
     * );
     * // returns '/static/theme/hello.gif'
     * </code>
     * 
     * @param string $uri file relative URI
     * 
     * @param string $realCurrentDir realpath of the current file's directory.
     * 
     * @param string $realDocRoot realpath of the site document root.
     * 
     * @return string
     */
    public static function rewriteRelative($uri, $realCurrentDir, $realDocRoot)
    {
        // prepend path with current dir separator (OS-independent)
        $path = strtr($realCurrentDir, '/', DIRECTORY_SEPARATOR)  
            . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        
        self::$debugText .= "file-relative URI  : {$uri}\n"
                          . "path prepended     : {$path}\n";
        
        $path = substr($path, strlen($realDocRoot));
        
        self::$debugText .= "docroot stripped   : {$path}\n";
        
        // fix to root-relative URI

        $uri = strtr($path, '/\\', '//');

        // remove /./ and /../ where possible
        $uri = str_replace('/./', '/', $uri);
        // inspired by patch from Oleg Cherniy
        do {
            $uri = preg_replace('@/[^/]+/\\.\\./@', '/', $uri, 1, $changed);
        } while ($changed);
      
        self::$debugText .= "traversals removed : {$uri}\n\n";
        
        return $uri;
    }
    
    /**
     * Get realpath with any trailing slash removed. If realpath() fails,
     * just remove the trailing slash.
     * 
     * @param string $path
     * 
     * @return mixed path with no trailing slash
     */
    protected static function _realpath($path)
    {
        $realPath = realpath2($path);
        if ($realPath !== false) {
            $path = $realPath;
        }
        return rtrim($path, '/\\');
    }
}

/**
 * A function similar to realpath, but it doesn't follow symlinks.
 * @param string $path The path to the file.
 * @return string
 */
function realpath2($path) {
   if (substr($path, 0, 2) == '//' || strpos($path, '://'))
      return $path;
   
   $parts = explode('/', str_replace('\\', '/', $path));
   $result = array();

   foreach ($parts as $part) {
      if (!$part || $part == '.')
         continue;
      if ($part == '..')
         array_pop($result);
      else
         $result[] = $part;
   }
   $result = '/'.implode('/', $result);

   // Do a sanity check.
   if (realpath($result) != realpath($path))
      $result = realpath($path);

   return $result;
}