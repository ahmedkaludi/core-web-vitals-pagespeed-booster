<?php

namespace WebPConvert\Convert\Exceptions;

use WebPConvert\Exceptions\WebPConvertException;

class ConversionFailedException extends WebPConvertException
{
    public $description = '';
}
