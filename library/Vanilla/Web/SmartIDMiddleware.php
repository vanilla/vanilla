<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;

/**
 * Lookup smart IDs in requests.
 *
 * This middleware replaces smart ID expressions in the request with their IDs and passes on the middleware.
 */
class SmartIDMiddleware {
    const SMART = '$';

    /**
     * @var string The base path to match in order to apply smart IDs.
     */
    private $basePath = '/';

    /**
     * @var \Gdn_SQLDriver
     */
    private $sql;

    /**
     * @var array An array of resource directories that apply to a smart ID.
     */
    private $resources = [];

    /**
     * @var array An array of PK columns that are fetched as the result of a smart ID.
     */
    private $pks = [];

    /**
     * @var string a cached regular expression to match a field against the known smart ID PK columns.
     */
    private $pksRegex;

    /**
     * SmartIDMiddleware constructor.
     *
     * @param string $basePath The base path to match in order to apply the middleware.
     * @param \Gdn_SQLDriver $sql Used to look up smart IDs.
     */
    public function __construct(string $basePath = '/', \Gdn_SQLDriver $sql) {
        $this->basePath = $basePath;
        $this->sql = clone $sql;
    }

    /**
     * Add a smart ID.
     *
     * Smart IDs can have a basic implementation where you supply a table name or a custom implementation where you provide a resolver callback.
     *
     * The resolver callback must have the following signature:
     *
     * ```php
     * function resolver(SmartIDMiddleware $sender, string $pk, string $column, string $value)
     * ```
     *
     * The function must return the result of the smart ID lookup.
     *
     * @param string $pk The name of the primary key field of the resource.
     * @param string $resource The directory name of the resource the smart ID applies to (ex. users for /users resource).
     * @param string|array $columns Either an array of valid lookup columns or "*" for any column.
     * @param string|callable $resolver Either a table name or a callback that will resolve the smart ID.
     */
    public function addSmartID(string $pk, string $resource, $columns, $resolver) {
        if ($columns !== '*' && !is_array($columns)) {
            throw new \InvalidArgumentException('The $columns argument must be an array or "*".', 500);
        }
        if (!is_string($resolver) && !is_callable($resolver)) {
            throw new \InvalidArgumentException('The $resolver argument must be an string or callable.', 500);
        }

        if (is_array($columns)) {
            $columns = array_map('strtolower', $columns);
        }

        $this->pks[strtolower($pk)] = [$pk, $columns, $resolver];
        $this->resources[strtolower($resource)] = strtolower($pk);

        $this->pksRegex = null;
    }

    /**
     * Fetch the smart ID value from the database.
     *
     * @param string $table The name of the table to search.
     * @param string $pk The name primary key.
     * @param array $where An array of where statements.
     * @return mixed Returns the value of the smart ID or **0** if it was not found.
     */
    public function fetchValue(string $table, string $pk, array $where) {
        $data = $this->sql->select($pk)->getWhere($table, $where);

        if ((int)$data->count() === 0) {
            throw new NotFoundException("Smart ID not found.");
        } elseif ($data->count() > 1) {
            throw new ClientException('More than one record matches smart ID: '.implodeAssoc(': ', ', ', $where), 409);
        }

        return $data->value($pk, 0);
    }

    /**
     * Invoke the smart ID middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        if (strcasecmp(substr($request->getPath(), 0, strlen($this->basePath)), $this->basePath) === 0) {
            $this->replaceQuery($request);
            $this->replaceBody($request);
            $this->replacePath($request);
        }

        $response = $next($request);
        return $response;
    }

    /**
     * Replace the smart IDs in a request path.
     *
     * @param RequestInterface $request The request to process.
     */
    private function replacePath(RequestInterface $request) {
        $parts = explode('/', $request->getPath());
        $prev = '';
        foreach ($parts as &$part) {
            if ($part && $part[0] === static::SMART) {
                if (substr($part, 1, 6) === 'query:') {
                    // This is a special query string substitution.
                    $field = substr($part, 7);
                    if (!isset($request->getQuery()[$field])) {
                        throw new ClientException("Invalid query field for smart ID: '$field'.", 400);
                    }
                    $replaced = $request->getQuery()[$field];
                } elseif (empty($prev)) {
                    throw new ClientException("No resource specified for smart ID: $part.", 400);
                } elseif (!isset($this->resources[$prev])) {
                    throw new ClientException("Invalid resource for smart ID: $prev/$part.", 400);
                } else {
                    $replaced = $this->replaceSmartID($this->resources[$prev], $part);
                }
                $part = $replaced;
            }

            $prev = strtolower($part);
        }
        $request->setPath(implode('/', $parts));
    }

    /**
     * Replace the smart IDs in a request querystring.
     *
     * @param RequestInterface $request The request to process.
     */
    private function replaceQuery(RequestInterface $request) {
        $query = $this->replaceArray($request->getQuery());
        if ($query !== false) {
            $request->setQuery($query);
        }
    }

    /**
     * Replace the smart IDs in a request body.
     *
     * @param RequestInterface $request The request to process.
     */
    private function replaceBody(RequestInterface $request) {
        if (in_array($request->getMethod(), ['GET', 'OPTIONS'])) {
            return;
        }

        $body = $this->replaceArray($request->getBody());
        if ($body !== false) {
            $request->setBody($body);
        }
    }

    /**
     * Get the regular expression that finds a smart ID primary key field in a field name.
     *
     * @return string Returns a regular expression string.
     */
    private function getPKsRegex() {
        if (!$this->pksRegex) {
            $this->pksRegex = '`('.implode('|', array_keys($this->pks)).')$`i';
        }
        return $this->pksRegex;
    }

    /**
     * Replace the smart IDs in a deeply nested array.
     *
     * @param array $arr The array to replace.
     * @return array|false Returns the replaced array of **false** if no replacements were made.
     */
    private function replaceArray($arr) {
        if (empty($arr)) {
            return false;
        }

        $regex = $this->getPKsRegex();

        array_walk_recursive($arr, function (&$value, $key) use ($regex, &$changed) {
            if ($value && is_string($value) && $value[0] === static::SMART && preg_match($regex, $key, $m)) {
                $value = $this->replaceSmartID($m[1], $value);
                $changed = true;
            }
        });

        if ($changed) {
            return $arr;
        } else {
            return false;
        }
    }

    /**
     * Replace a smart ID with it PK value.
     *
     * @param string $pk The name of the PK column for the smart ID.
     * @param string $smartID The smart ID to lookup.
     * @return mixed Returns the resulting value of the smart ID.
     */
    private function replaceSmartID(string $pk, string $smartID) {
        list($column, $value) = explode(':', ltrim($smartID, static::SMART)) + ['', ''];
        list($pk, $columns, $resolver) = $this->pks[strtolower($pk)];

        if ($columns !== '*' && !in_array(strtolower($column), $columns)) {
            throw new ClientException("Unknown column in smart ID expression: $column.", 400);
        }
        if (is_string($resolver)) {
            // The resolver is a table and is fetched itself.
            $result = $this->fetchValue($resolver, $pk, [strtolower($column) => $value]);
        } else {
            // The resolver is a callback that performs a custom action.
            $result = $resolver($this, $pk, strtolower($column), $value);
        }
        return $result;
    }

    /**
     * Get the base path.
     *
     * @return string Returns the basePath.
     */
    public function getBasePath(): string {
        return $this->basePath;
    }

    /**
     * Set the base path.
     *
     * @param string $basePath The new value.
     * @return $this
     */
    public function setBasePath(string $basePath) {
        $this->basePath = $basePath;
        return $this;
    }
}
