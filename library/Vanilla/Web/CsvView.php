<?php

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\ViewInterface;

class CsvView implements ViewInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(Data $data)
    {
        $data->renderCsv();
    }
}
