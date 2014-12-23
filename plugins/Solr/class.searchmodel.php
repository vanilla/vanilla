<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

class SearchModel extends Gdn_Model {
	/// PROPERTIES ///
   public $Types = array(1 => 'Discussion', 2 => 'Comment');

   /// METHODS ///

   public function Search($Search, $Offset = 0, $Limit = 20) {
      $BaseUrl = C('Plugins.Solr.SearchUrl', 'http://localhost:8983/solr/select/?');
      if (!$BaseUrl)
         throw new Gdn_UserException("The search url has not been configured.");

      if (!$Search)
         return array();

      // Escepe the search.
      $Search = preg_replace('`([][+&|!(){}^"~*?:\\\\-])`', "\\\\$1", $Search);

      // Add the category watch.
      $Categories = CategoryModel::CategoryWatch();
      if ($Categories === FALSE) {
         return array();
      } elseif ($Categories !== TRUE) {
         $Search = 'CategoryID:('.implode(' ', $Categories).') AND '.$Search;
      }

      // Build the search url.
      $BaseUrl .= strpos($BaseUrl, '?') === FALSE ? '?' : '&';
      $Query = array('q' => $Search, 'start' => $Offset, 'rows' => $Limit);
      $Url = $BaseUrl.http_build_query($Query);

      // Grab the data.
      $Curl = curl_init($Url);
      curl_setopt($Curl, CURLOPT_RETURNTRANSFER, 1);
      $CurlResult = curl_exec($Curl);
      curl_close($Curl);

      // Parse the result into the form that the search controller expects.
      $Xml = new SimpleXMLElement($CurlResult);
      $Result = array();

      if (!isset($Xml->result))
         return array();

      foreach ($Xml->result->children() as $Doc) {
         $Row = array();
         foreach ($Doc->children() as $Field) {
            $Name = (string)$Field['name'];
            $Row[$Name] = (string)$Field;
         }
         // Add the url.
         switch ($Row['DocType']) {
            case 'Discussion':
               $Row['Url'] = '/discussion/'.$Row['PrimaryID'].'/'.Gdn_Format::Url($Row['Title']);
               break;
            case 'Comment':
               $Row['Url'] = "/discussion/comment/{$Row['PrimaryID']}/#Comment_{$Row['PrimaryID']}";
               break;
         }
         // Fix the time.
         $Row['DateInserted'] = strtotime($Row['DateInserted']);
         $Result[] = $Row;
      }

      // Join the users into the result.
      Gdn_DataSet::Join($Result, array('table' => 'User', 'parent' => 'UserID', 'prefix' => '', 'Name', 'Photo'));

      return $Result;
	}

}