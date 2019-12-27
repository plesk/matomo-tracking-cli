<?php
/**
 * @copyright 2019. Plesk International GmbH.
 * @link https://www.plesk.com
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Piwik\Plugins\TrackingCLI\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\TrackingCLI\lib\Exception;
use Piwik\Plugins\TrackingCLI\lib\AuthenticatedRequest;
use Piwik\Tracker;
use Piwik\Tracker\RequestSet;
use Piwik\Tracker\Handler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SplFileObject;


/**
 * @see https://developer.matomo.org/api-reference/tracking-api Matomo tracking API
 * @see http://developer.piwik.org/guides/piwik-on-the-command-line Piwik Console guide
 * @see http://symfony.com/doc/current/components/console/index.html Symfony Console guide
 */
class Import extends ConsoleCommand
{
    public $defaultDelimeter = '\29';
    public $defaultBatchsize = 100;


    protected function configure()
    {
        $this->setName('trackingcli:import');
        $this->setDescription(
            'Import tracking data to Matomo via CLI and stdin. Data rows are delimited by Unix new line character.'
        );

        $this->addOption(
            'idsite',
            's',
            InputOption::VALUE_REQUIRED,
            'Matomo site ID'
        );


        $this->addOption(
            'columns',
            'c',
            InputOption::VALUE_REQUIRED,
            <<<'EOD'
Columns map 
    Format: matomoApiArgumentName1|matomoApiArgumentName2|...
    Example: url|action_name|ua
    See https://developer.matomo.org/api-reference/tracking-api for details
EOD
            );


        $this->addOption(
            'delimeter',
            'd',
            InputOption::VALUE_OPTIONAL,
            <<<'EOD'
Columns delimeter
    Format: s - character, \digits - the character with the given decimal code
    Example: |
    Example: \0
EOD
            ,
            $this->defaultDelimeter
        );


        $this->addOption(
            'batchsize',
            'z',
            InputOption::VALUE_OPTIONAL,
            'Batch size when importing',
            $this->defaultBatchsize
        );

        $this->addOption(
            'fail-no-data',
            'e',
            InputOption::VALUE_NONE,
            "Fail if no rows imported"
        );

        $this
            ->addArgument(
                'inputfile',
                InputArgument::OPTIONAL,
                'Path to input file or \'-\' for stdin',
                '-'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkAllRequiredOptionsAreNotEmpty($input);

        $idsite = $input->getOption('idsite');

        $columns = explode(
            '|',
            $input->getOption('columns')
        );

        $delimeter = $input->getOption('delimeter');
        if (preg_match('#\\\\(\d+)#', $delimeter, $matches)) {
            $delimeter = chr($matches[1]);
        }

        $batchsize = $input->getOption('batchsize');

        $inputfile = $input->getArgument('inputfile');
        if ($inputfile == '-') {
            $inputfile = 'php://stdin';
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
            $output->writeln("<info>\idsite = $idsite, columns = " . json_encode($columns) . ", delimeter = $delimeter, batchsize = $batchsize, inputfile = $inputfile</info>");
        }

        $file = new SplFileObject($inputfile);
        $tracker    = new Tracker();
        $handler  = Handler\Factory::make();
        $requestSet = new RequestSet();

        while (!$file->eof()) {

            $requests = [];
            for ($i = 0; $i < $batchsize && !$file->eof(); $i++) {
                // SplFileObject::DROP_NEW_LINE drops all rest of the string after null ASCII character
                $rowstring = rtrim($file->fgets(), "\n");
                if (!$rowstring) {
                    continue;
                }
                $row = explode($delimeter, $rowstring);

                $request = [
                    'idsite' => $idsite,
                    'rec' => 1,
                    'apiv' => 1,
                    'send_image' => 0,
                ];
                foreach ($columns as $j => $column) {
                    $request[$column] = $row[$j];
                }
                $requests[] = new AuthenticatedRequest($request);
            }

            if ($requests) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln(
                        json_encode(
                            array_map(
                                function (AuthenticatedRequest $request) {
                                    return $request->getParams();
                                },
                                $requests
                            )
                        )
                    );
                }
                $requestSet->setRequests($requests);
                $tracker->track($handler, $requestSet);
            }
        }

        if (!$tracker->getCountOfLoggedRequests() &&
            $input->getOption('fail-no-data')
        ) {
            throw new Exception("No rows have been imported");
        }

        $output->writeln('<info>Success</info>');
        $output->writeln('Requests imported: ' . $tracker->getCountOfLoggedRequests());
        $output->writeln('Memory peak usage: ' . memory_get_peak_usage(true));
    }
}
