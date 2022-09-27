<?php
/**
 * Class PclZipAdapter
 */

class PclZipAdapter
{
    /**
     * @var PclZip
     */
    public $PclZip = null;
    public $numFiles = null;
    protected $_Contents = null;
    protected $_Names = null;

    public function close()
    {
        if ($this->PclZip) {
            $this->PclZip = 0;
        }
    }

    public function deleteName($name)
    {
        $index = $this->_Names[$name]["index"];
        $this->PclZip->deleteByIndex($index);
    }

    public function extractTo($path, $names)
    {
        $indexes = [];
        // Convert the name(s) to indexes.
        foreach ((array) $names as $name) {
            if (!isset($this->_Names[$name])) {
                continue;
            }
            $indexes[] = $this->_Names[$name]["index"];
        }
        $indexesStr = implode(",", $indexes);
        $result = $this->PclZip->extractByIndex($indexesStr, $path);
        return $result != 0;
    }

    public function open($path)
    {
        $this->PclZip = new PclZip($path);
        $result = $this->_Contents = $this->PclZip->listContent();
        if (!$result) {
            return ZipArchive::ER_READ;
        }
        $this->_Names = [];
        foreach ($this->_Contents as $content) {
            $this->_Names[$content["filename"]] = $content;
        }
        $this->numFiles = count($this->_Contents);
        return true;
    }

    public function statIndex($index)
    {
        $content = $this->_Contents[$index];
        $result = [
            "name" => $content["filename"],
            "index" => $content["index"],
            "crc" => $content["crc"],
            "size" => $content["size"],
            "mtime" => $content["mtime"],
            "comp_size" => $content["compressed_size"],
            "comp_method" => false,
        ];
        return $result;
    }
}
