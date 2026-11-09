<?php

namespace app\traits\vo;

use app\core\ValueObject;

trait data
{

    private $data;

    function getData($format = false)
    {
        /** @var ValueObject $this */
        return $this->formatValue($this->getInsert(), 'data', $format);
    }

}