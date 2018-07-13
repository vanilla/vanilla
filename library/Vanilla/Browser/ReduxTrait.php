<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Browser;

use Garden\Web\Data;
use Vanilla\Attributes;

/**
 * Utility code for passing initial client state from the server.
 */
trait ReduxTrait {
    /**
     * Add a client state property.
     *
     * @param string $name The name of the property.
     * @param mixed $value The value of the state property.
     * @return $this
     */
    public function addClientState(string $name, $value) {
        $this->addMeta('clientState', $name, $value);
        return $this;
    }

    /**
     * Merge a client state array.
     *
     * This method is useful for setting the entire client state.
     *
     * @param array $state The state to set.
     * @return $this
     */
    public function mergeClientState(array $state) {
        $this->mergeMetaArray(['clientState' => $state]);
        return $this;
    }

    /**
     * Add a client action.
     *
     * Sometimes it is easier to send actions instead of state to the client so that more complex reducer code can stay
     * on the client only. Client actions are all dispatched once the state is constructed.
     *
     * @param string $type The action type.
     * @param mixed $payload The action payload.
     * @return $this
     */
    public function addClientAction(string $type, $payload, array $meta = []) {
        $action = ['type' => $type, 'payload' => $payload];

        if (!empty($meta)) {
            $action['meta'] = $meta;
        }

        $this->addMeta('clientActions',  $action);
        return $this;
    }

    /**
     * Add an action that represents the result of an API call.
     *
     * This method is in place to enforce conventions and allow us to add more than just the data to the payload in the future.
     *
     * @param string $type The action type.
     * @param Data $data The result of the API call.
     * @return $this
     */
    public function addClientApiAction(string $type, Data $data) {
        $meta = [];
        if ($data->getStatus() !== 200) {
            $meta['status'] = $data->getStatus();
        }
        $this->addClientAction($type, [
            'data' => $data->getData(),
            'status' => $data->getStatus(),
            'headers' => $data->getHeaders()
        ]);

        return $this;
    }

    /**
     * Render the initial client state to a javascrpt string.
     *
     * The string does not include script tags.
     *
     * @return string Returns a the javascript string.
     */
    public function renderClientState(): string {
        return 'window.__STATE__='.json_encode($this->getMeta('clientState', (object)[])).";\n".
            'window.__ACTIONS__='.json_encode($this->getMeta('clientActions', [])).";\n";
    }

    /**
     * Get a single item from the meta array.
     *
     * @param string $name The key to get from.
     * @param mixed $default The default value if no item at the key exists.
     * @return mixed Returns the meta value.
     */
    abstract public function getMeta($name, $default = null);

    /**
     * Add a sub-item to a meta array.
     *
     * This method can take two forms.
     *
     * 1. `$o->addMeta('name', $value)` assumes that the item at **'name'** is a numeric array and adds **$value** to the end.
     * 2. `$o->addMeta('name', 'key', $value)` adds **$value** to the array at  **'name'** and uses **'key'** as the key.
     *     This may result in an existing item being overwritten.
     *
     * @param string $name The name of the meta key.
     * @param mixed[] $value Either a single value or a key then a value to set.
     * @return $this
     */
    abstract public function addMeta($name, ...$value);

    /**
     * Merge another meta array with this one.
     *
     * @param array $meta The meta array to merge.
     * @return $this
     */
    abstract public function mergeMetaArray(array $meta);
}
