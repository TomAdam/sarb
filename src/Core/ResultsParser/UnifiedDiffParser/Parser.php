<?php

/**
 * Static Analysis Results Baseliner (sarb).
 *
 * (c) Dave Liddament
 *
 * For the full copyright and licence information please view the LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\UnifiedDiffParser;

use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\UnifiedDiffParser\internal\DiffParseException;
use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\UnifiedDiffParser\internal\FileMutationsBuilder;
use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\UnifiedDiffParser\internal\FindFileDiffStartState;

/**
 * Parses a Unified Diff (see docs folder).
 */
class Parser
{
    /**
     * @param string $diffAsString
     *
     * @throws ParseException
     *
     * @return FileMutations
     */
    public function parseDiff(string $diffAsString): FileMutations
    {
        $fileMutationsBuilder = new FileMutationsBuilder();
        $state = new FindFileDiffStartState($fileMutationsBuilder);

        $lines = $this->getLines($diffAsString);

        foreach ($lines as $number => $line) {
            try {
                $state = $state->processLine($line);
            } catch (DiffParseException $e) {
                $lineNumber = $number + 1;
                throw ParseException::fromDiffParseException((string) $lineNumber, $e);
            }
        }

        try {
            $state->finish();
        } catch (DiffParseException $e) {
            throw ParseException::fromDiffParseException(ParseException::UNEXPECTED_END_OF_FILE, $e);
        }

        return $fileMutationsBuilder->build();
    }

    /**
     * @param string $diffAsString
     *
     * @return array<int,string>
     */
    private function getLines(string $diffAsString): array
    {
        $lines = explode(PHP_EOL, $diffAsString);

        // Strip trailing empty lines from diff
        do {
            $finalLineIndex = count($lines) - 1;
            $removeFinalLine = ($finalLineIndex >= 0) && ('' === trim($lines[$finalLineIndex]));
            if ($removeFinalLine) {
                unset($lines[$finalLineIndex]);
            }
        } while ($removeFinalLine);

        return $lines;
    }
}
