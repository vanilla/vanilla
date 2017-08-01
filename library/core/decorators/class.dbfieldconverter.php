<?php
/**
 *
 */

/**
 * Class DBFieldConverter
 *
 * Convert data types between the database and PHP.
 */
class DBFieldConverter extends AbstractDecorator {

    /** @var Gdn_Database */
    protected $object;

    private $conversionSchema;

    public function __construct($object, DBFieldConversionSchemaProvider $conversionProvider) {
        parent::__construct($object);
        $this->conversionSchema = $conversionProvider;
    }

    /**
     * Executes a string of SQL. Returns a Gdn_DataSet object.
     *
     * @param string $sql A string of SQL to be executed.
     * @param array $inputParameters An array of values with as many elements as there are bound parameters in the SQL statement being executed.
     * return Gdn_DataSet
     */
    public function query($sql, $inputParameters = null, $options = []) {
        // Determine if the query is inserting or getting informations.


        $this->object->query($sql, $inputParameters, $options);
    }

}
