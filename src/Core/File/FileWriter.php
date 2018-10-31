<?php

/**
 * Static Analysis Results Baseliner (sarb).
 *
 * (c) Dave Liddament
 *
 * For the full copyright and licence information please view the LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace DaveLiddament\StaticAnalysisBaseliner\Core\File;

use DaveLiddament\StaticAnalysisBaseliner\Core\Common\FileName;
use DaveLiddament\StaticAnalysisBaseliner\Core\Utils\JsonParseException;
use DaveLiddament\StaticAnalysisBaseliner\Core\Utils\JsonUtils;

class FileWriter
{
    /**
     * Write $contents to the file.
     *
     * @param FileName $fileName
     * @param array $contents
     *
     * @throws JsonParseException
     * @throws FileAccessException
     */
    public function writeArrayToFile(FileName $fileName, array $contents): void
    {
        $asString = JsonUtils::toString($contents);
        $this->writeFile($fileName, $asString);
    }

    /**
     * Write $contents to the file.
     *
     * @param FileName $fileName
     * @param string $contents
     *
     * @throws FileAccessException
     */
    public function writeFile(FileName $fileName, string $contents): void
    {
        $result = file_put_contents($fileName->getFileName(), $contents);
        if (false === $result) {
            throw FileAccessException::writeFileException();
        }
    }
}
