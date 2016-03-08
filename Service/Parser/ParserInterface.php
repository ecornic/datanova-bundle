<?php

namespace Laposte\DatanovaBundle\Service\Parser;

interface ParserInterface
{
    /**
     * @param string $dataset
     *
     * @return false|array
     */
    public function parse($dataset);
}