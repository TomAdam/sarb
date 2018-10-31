<?php

/**
 * Static Analysis Results Baseliner (sarb).
 *
 * (c) Dave Liddament
 *
 * For the full copyright and licence information please view the LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace DaveLiddament\StaticAnalysisBaseliner\Framework\Command;

use DaveLiddament\StaticAnalysisBaseliner\Core\Analyser\BaseLineResultsRemover;
use DaveLiddament\StaticAnalysisBaseliner\Core\BaseLiner\BaseLineImporter;
use DaveLiddament\StaticAnalysisBaseliner\Core\Common\FileName;
use DaveLiddament\StaticAnalysisBaseliner\Core\HistoryAnalyser\HistoryFactory;
use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\Exporter;
use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\Importer;
use DaveLiddament\StaticAnalysisBaseliner\Core\ResultsParser\StaticAnalysisResultsParser;
use DaveLiddament\StaticAnalysisBaseliner\Framework\Command\internal\AbstractCommand;
use DaveLiddament\StaticAnalysisBaseliner\Framework\Container\HistoryFactoryRegistry;
use DaveLiddament\StaticAnalysisBaseliner\Framework\Container\StaticAnalysisResultsParsersRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveBaseLineFromResultsCommand extends AbstractCommand
{
    const COMMAND_NAME = 'remove-baseline-results';

    const OUTPUT_RESULTS_FILE = 'output-results-file';
    const FAILURE_ON_ANALYSIS_RESULT = 'failure-on-analysis-result';

    /**
     * @var string
     */
    protected static $defaultName = self::COMMAND_NAME;

    /**
     * @var BaseLineResultsRemover
     */
    private $baseLineResultsRemover;

    /**
     * @var Importer
     */
    private $resultsImporter;

    /**
     * @var Exporter
     */
    private $resultsExporter;

    /**
     * @var BaseLineImporter
     */
    private $baseLineImporter;

    /**
     * CreateBaseLineCommand constructor.
     *
     * @param StaticAnalysisResultsParsersRegistry $staticAnalysisResultsParserRegistry
     * @param HistoryFactoryRegistry $historyFactoryRegistry
     * @param BaseLineResultsRemover $baseLineResultsRemover
     * @param BaseLineImporter $baseLineImporter
     * @param Importer $resultsImporter
     * @param Exporter $resultsExporter
     */
    public function __construct(
        StaticAnalysisResultsParsersRegistry $staticAnalysisResultsParserRegistry,
        HistoryFactoryRegistry $historyFactoryRegistry,
        BaseLineResultsRemover $baseLineResultsRemover,
        BaseLineImporter $baseLineImporter,
        Importer $resultsImporter,
        Exporter $resultsExporter
    ) {
        parent::__construct(
            self::COMMAND_NAME,
            $staticAnalysisResultsParserRegistry,
            $historyFactoryRegistry
        );
        $this->baseLineResultsRemover = $baseLineResultsRemover;
        $this->baseLineImporter = $baseLineImporter;
        $this->resultsExporter = $resultsExporter;
        $this->resultsImporter = $resultsImporter;
    }

    protected function configureHook(): void
    {
        $this->setDescription('Creates a baseline of the static analysis results for the specified static analysis tool');

        $this->addArgument(
            self::OUTPUT_RESULTS_FILE,
            InputArgument::REQUIRED,
            'Output file (with baseline results removed)'
        );

        $this->addOption(
            self::FAILURE_ON_ANALYSIS_RESULT,
            'f',
            InputOption::VALUE_NONE,
            'Return error code if there any static analysis results after base line removed (useful for CI)'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function executeHook(
        InputInterface $input,
        OutputInterface $output,
        StaticAnalysisResultsParser $staticAnalysisResultsParser,
        FileName $resultsFileName,
        FileName $baseLineFileName,
        HistoryFactory $historyFactory
    ): int {
        $outputResultsFile = $this->getFileName($input, self::OUTPUT_RESULTS_FILE);

        $inputAnalysisResults = $this->resultsImporter->importFromFile($staticAnalysisResultsParser, $resultsFileName);

        $baseLine = $this->baseLineImporter->import(
            $staticAnalysisResultsParser,
            $historyFactory->newHistoryMarkerFactory(),
            $baseLineFileName
        );

        $outputAnalysisResults = $this->baseLineResultsRemover->pruneBaseLine(
            $inputAnalysisResults,
            $baseLine,
            $historyFactory->newHistoryAnalyser($baseLine->getHistoryMarker())
        );

        $this->resultsExporter->exportAnalysisResults(
            $outputAnalysisResults,
            $staticAnalysisResultsParser,
            $outputResultsFile
        );

        $errorsAfterBaseLine = count($outputAnalysisResults->getAnalysisResults());
        $errorsBeforeBaseLine = count($inputAnalysisResults->getAnalysisResults());
        $errorsInBaseLine = count($baseLine->getAnalysisResults()->getAnalysisResults());

        $output->writeln("<info>Errors before baseline $errorsBeforeBaseLine</info>");
        $output->writeln("<info>Errors in baseline $errorsInBaseLine</info>");
        $output->writeln("<info>Errors introduced since baseline $errorsAfterBaseLine</info>");

        if (true === $input->getOption(self::FAILURE_ON_ANALYSIS_RESULT)) {
            return (0 === count($outputAnalysisResults->getAnalysisResults())) ? 0 : 1;
        }

        return 0;
    }
}
