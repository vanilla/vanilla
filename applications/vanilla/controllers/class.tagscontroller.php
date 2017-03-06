<?php
/**
 * Tags controller
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class TagssController
 */
class TagsController extends VanillaController {
    /**
     * Search results for tagging autocomplete.
     *
     * @param string $q
     * @param bool $id
     * @param bool $parent
     * @param string $type
     * @throws Exception
     */
    public function search($q = '', $id = false, $parent = false, $type = 'default') {
        // Allow per-category tags
        $CategorySearch = c('Vanilla.Tagging.CategorySearch', false);
        if ($CategorySearch) {
            $CategoryID = GetIncomingValue('CategoryID');
        }

        if ($parent && !is_numeric($parent)) {
            $parent = Gdn::sql()->getWhere('Tag', array('Name' => $parent))->value('TagID', -1);
        }

        $Query = $q;
        $Data = array();
        $Database = Gdn::database();
        if ($Query || $parent || $type !== 'default') {
            $TagQuery = Gdn::sql()
                ->select('*')
                ->from('Tag')
                ->limit(20);

            if ($Query) {
                $TagQuery->like('FullName', str_replace(array('%', '_'), array('\%', '_'), $Query), strlen($Query) > 2 ? 'both' : 'right');
            }

            if ($type === 'default') {
                $defaultTypes = array_keys(TagModel::instance()->defaultTypes());
                $TagQuery->where('Type', $defaultTypes); // Other UIs can set a different type
            } elseif ($type) {
                $TagQuery->where('Type', $type);
            }

            // Allow per-category tags
            if ($CategorySearch) {
                $TagQuery->where('CategoryID', $CategoryID);
            }

            if ($parent) {
                $TagQuery->where('ParentTagID', $parent);
            }

            // Run tag search query
            $TagData = $TagQuery->get();

            foreach ($TagData as $Tag) {
                $Data[] = array('id' => $id ? $Tag->TagID : $Tag->Name, 'name' => $Tag->FullName);
            }
        }
        // Close the db before exiting.
        $Database->closeConnection();
        // Return the data
        header("Content-type: application/json");
        echo json_encode($Data);
        exit();
    }
}
