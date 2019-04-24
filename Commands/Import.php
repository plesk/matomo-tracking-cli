<?php
/**
 * @copyright 2019. Plesk International GmbH.
 * @link https://www.plesk.com
 * @license https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

namespace Piwik\Plugins\TrackingCLI\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Tracker;
use Piwik\Tracker\RequestSet;
use Piwik\Tracker\Handler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SplFileObject;


/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class Import extends ConsoleCommand
{
    public $defaultDelimeter = '\29';
    public $defaultBatchsize = 100;


    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $this->setName('trackingcli:import');
        $this->setDescription('Import tracking data to Matomo via CLI and stdin');

        $this->addOption('idsite', 's', InputOption::VALUE_REQUIRED, 'Matomo site ID');


        $this->addOption(
            'columns',
            'c',
            InputOption::VALUE_REQUIRED,
            <<<'EOD'
Columns map 
    Format: matomoApiArgumentName1|matomoApiArgumentName2|...
    Example: url|action_name|ua
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

        $this
            ->addArgument(
                'inputfile',
                InputArgument::OPTIONAL,
                'Path to input file or \'-\' for stdin',
                '-'
            );
    }

    /**
     * The actual task is defined in this method. Here you can access any option or argument that was defined on the
     * command line via $input and write anything to the console via $output argument.
     * In case anything went wrong during the execution you should throw an exception to make sure the user will get a
     * useful error message and to make sure the command does not exit with the status code 0.
     *
     * Ideally, the actual command is quite short as it acts like a controller. It should only receive the input values,
     * execute the task by calling a method of another class and output any useful information.
     *
     * Execute the command like: ./console trackingcli:import --name="The Piwik Team"
     */
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
        //setTokenAuth($tokenAuth)

        $file->setFlags(
            SplFileObject::SKIP_EMPTY |
            SplFileObject::DROP_NEW_LINE
        );
        while (!$file->eof()) {

            $requests = [];
            for ($i = 0; $i < $batchsize && !$file->eof(); $i++) {
                $rowstring = $file->fgets();

                $row = explode($delimeter, $rowstring);

                $request = [
                    'idsite' => $idsite,
                    'rec' => 1,
                    'apiv' => 1,
                    'send_image' => 0,
                ];
                foreach ($columns as $i => $column) {
                    $request[$column] = $row[$i];
                }
                $requests[] = $request;
            }

            if ($requests) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $output->writeln(json_encode($requests));
                }
                $requestSet->setRequests($requests);
                $tracker->track($handler, $requestSet);
            }
        }

        $output->writeln('<info>Success</info>');
        $output->writeln('Memory peak usage: ' . memory_get_peak_usage(true));
    }
}
