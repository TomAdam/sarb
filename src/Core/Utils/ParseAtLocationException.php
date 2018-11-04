<?php

/**
 * Static Analysis Results Baseliner (sarb).
 *
 * (c) Dave Liddament
 *
 * For the full copyright and licence information please view the LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace DaveLiddament\StaticAnalysisResultsBaseliner\Core\Utils;

use DaveLiddament\StaticAnalysisResultsBaseliner\Core\Common\SarbException;

/**
 * Holds a parsing exception along with details of where the parsing failed.
 */
class ParseAtLocationException extends SarbException
{
    public function __construct(string $location, SarbException $locationException)
    {
        parent::__construct($location, 0, $locationException);
    }
}
