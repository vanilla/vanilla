<?php

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\ViewInterface;

class CsvView implements ViewInterface
{
    public const META_HEADINGS = "csv_headings";

    /**
     * {@inheritdoc}
     */
    public function render(Data $data)
    {
        $data->renderCsv();
    }
}
