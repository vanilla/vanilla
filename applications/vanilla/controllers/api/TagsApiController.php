<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;

/**
 * API Controller for the `/tags` resource.
 */
class TagsApiController extends AbstractApiController {


    /** @var TagModel */
    private $tagModel;

    /**
     * TagsApiController constructor.
     *
     * @param TagModel $tagModel
     */
    public function __construct(TagModel $tagModel) {
        $this->tagModel = $tagModel;
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'id:i',
                'name:s',
                'urlCode:s?'
            ]);
        }

        return $schema;
    }

    /**
     * Get tags from a query string.
     *
     * @param array $query
     * @return array
     */
    public function get(array $query) {
        $this->permission();
        $in = $this->schema(
            [
                'query:s?',
            ]
        );

        $query = $in->validate($query);

        $out = $this->schema([':a?' => $this->fullSchema()], 'out');
        $options['extraFields'] = true;

        $tags = [];
        $searchTerm = $query['query'] ?? '';
        if ($searchTerm) {
            $tags = $this->tagModel->search(
                $searchTerm,
                true,
                false,
                "default",
                $options
            );
        }

        $tags = $this->normalizeTags($tags);
        $tags = $out->validate($tags);

        return $tags;
    }

    /**
     * Normalize Tag data for the api.
     *
     * @param array $tags
     * @return array
     */
    private function normalizeTags(array &$tags): array {
        foreach ($tags as $key => &$tag) {
            // we should remove tags that aren't user generated.
            // in-case they some how are returned by the search.
            $type = $tag['type'] ?? '';
            if ($type !== '') {
                array_splice($tags, $key, 1);
                continue;
            }
            $tag['urlCode'] = $tag['name'] ?? [];
            $tag['name'] = $tag['fullName'] ?? [];
        }
        return $tags;
    }
}
