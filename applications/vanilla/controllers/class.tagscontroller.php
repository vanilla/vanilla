<?php
/**
 * Tags controller
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
        $categorySearch = c('Vanilla.Tagging.CategorySearch', false);
        if ($categorySearch) {
            $categoryID = getIncomingValue('CategoryID');
        }

        if ($parent && !is_numeric($parent)) {
            $parent = Gdn::sql()->getWhere('Tag', ['Name' => $parent])->value('TagID', -1);
        }

        $query = $q;
        $data = [];
        $database = Gdn::database();
        if ($query || $parent || $type !== 'default') {
            $tagQuery = Gdn::sql()
                ->select('*')
                ->from('Tag')
                ->limit(20);

            if ($query) {
                $tagQuery->like('FullName', str_replace(['%', '_'], ['\%', '_'], $query), strlen($query) > 2 ? 'both' : 'right');
            }

            if ($type === 'default') {
                $defaultTypes = array_keys(TagModel::instance()->defaultTypes());
                $tagQuery->where('Type', $defaultTypes); // Other UIs can set a different type
            } elseif ($type) {
                $tagQuery->where('Type', $type);
            }

            // Allow per-category tags
            if ($categorySearch) {
                $tagQuery->where('CategoryID', $categoryID);
            }

            if ($parent) {
                $tagQuery->where('ParentTagID', $parent);
            }

            // Run tag search query
            $tagData = $tagQuery->get();

            foreach ($tagData as $tag) {
                $data[] = ['id' => $id ? $tag->TagID : $tag->Name, 'name' => $tag->FullName];
            }
        }
        // Close the db before exiting.
        $database->closeConnection();
        // Return the data
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
