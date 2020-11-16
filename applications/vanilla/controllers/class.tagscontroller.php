<?php
/**
 * Tags controller
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Class TagsController
 */
class TagsController extends VanillaController {

    /** @var TagModel */
    private $tagModel;

    /**
     * TagsController constructor.
     *
     * @param TagModel $tagModel
     */
    public function __construct(TagModel $tagModel) {
        parent::__construct();
        $this->tagModel = $tagModel;
    }
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
        $categoryID = getIncomingValue('CategoryID');
        $options["categoryID"] = $categoryID;
        $data = $this->tagModel->search($q, $id, $parent, $type, $options);
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
