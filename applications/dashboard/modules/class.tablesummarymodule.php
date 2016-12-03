<?php

/**
 * A module for rending a simple table. Renders the leaderboard-type tables across the dashboard. Constrains rows by
 * the column data.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2016 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */
class TableSummaryModule extends Gdn_Module {

    const CSS_PREFIX = 'table-summary';
    const MAIN_CSS_CLASS = 'table-summary-cell-main';

    /**
     * @var array An array of column-type arrays.
     * A column array has the following keys: key, name, attributes, columnClass
     */
    private $columns;

    /**
     * @var array An array of row-type arrays. Each row array has a cells and attributes key.
     * The cells key holds an array of 'column-key' => 'cell-content' data.
     */
    private $rows;

    /** @var string The table title. */
    private $title;

    public function __construct($title = '') {
        parent::__construct();
        $this->title = $title;
    }

    /**
     * @return array An array of row-type arrays. Each row array has a cells and attributes key.
     * The cells key holds an array of 'column-key' => 'cell-content' data.
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * @return array An array of column-type arrays.
     * A column array has the following keys: key, name, attributes, columnClass.
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @return string The table title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Add a column to the table.
     *
     * @param string $key The key name of the column.
     * @param string $name The display-name of the column.
     * @param array $attributes The attributes for the heading cell.
     * @param string $columnClass The css class to propagate to every cell in this column.
     * @return TableSummaryModule $this
     */
    public function addColumn($key, $name = '', $attributes = [], $columnClass = '') {
        $class = val('class', $attributes) ?
            $attributes['class'].' '.self::CSS_PREFIX.'-heading-'.slugify($key) :
            self::CSS_PREFIX.'-heading-'.slugify($key);

        if ($columnClass) {
            $class .= ' '.$columnClass;
        }
        $attributes['class'] = $class;
        $this->columns[$key] = [
            'key' => $key,
            'name' => $name,
            'attributes' => $attributes,
            'column-class' => $columnClass
        ];

        return $this;
    }

    /**
     * Add a column to the table if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $key The key name of the column.
     * @param string $name The display-name of the column.
     * @param array $attributes The attributes for the th cell.
     * @param string $columnClass The css class to propagate to every cell in this column.
     * @return TableSummaryModule $this
     */
    public function addColumnIf($isAllowed, $key, $name = '', $attributes = [], $columnClass = '') {
        if (!$this->allowed($isAllowed)) {
            return $this;
        }
        return $this->addColumn($key, $name, $attributes, $columnClass);
    }

    /**
     * Adds a row to the table. Constrains our data to the columns we've added.
     *
     * @param array $row An array representing a row where the key matches a column key and the value is either an
     * HTML-formatted string or an object with a toString() method. (Something echo-able.)
     * @param string $key The key for the row. Nothing to do with the column key. Here for expansion, like in
     * case we want to sort items eventually.
     * @param array $attributes The attributes on the tr element.
     * @return TableSummaryModule $this
     */
    public function addRow($row = [], $key = '', $attributes = []) {
        if (!$key) {
            $key = randomString(8);
        } else {
            $key = slugify($key);
        }

        // Match up data with our columns
        $cells = array_intersect_key($row, $this->columns);
        $cellData = [];

        foreach ($cells as $cellKey => $cell) {
            $cellData[$cellKey]['data'] = $cell;
        }
        $this->rows[$key]['cells'] = $cellData;
        $this->rows[$key]['attributes'] = $attributes;

        return $this;
    }

    /**
     * Prepares the media item for rendering. Adds column-type css classes to cells in the table.
     *
     * @return bool Whether to render the module.
     */
    public function prepare() {
        // Add css classes to cells
        foreach ($this->rows as $rowKey => $row) {
            foreach ($row['cells'] as $key => $value) {
                $cellClass = self::CSS_PREFIX.'-cell-'.$key;
                if (isset($this->columns[$key]['column-class'])) {
                    $cellClass .= ' '.$this->columns[$key]['column-class'];
                }
                $this->rows[$rowKey]['cells'][$key]['attributes']['class'] = $cellClass;
            }
        }
        return true;
    }
}
