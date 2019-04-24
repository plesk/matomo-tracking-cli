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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//use Symfony\Component\HttpFoundation\Request;


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
Columns map. 
    Format: matomoApiArgumentName1|matomoApiArgumentName2|...
    Example: url|action_name|ua
EOD
            );


        $this->addOption(
            'delimeter',
            'd',
            InputOption::VALUE_OPTIONAL,
            <<<'EOD'
Columns delimeter.
    Format: s - character, \digits - the character with the given decimal code.
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

        //require_once __DIR__ . '/../piwikBootstrap.php';

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

        $output->writeln("<info>idsite = $idsite, columns = " . json_encode($columns) . ", delimeter = $delimeter, batchsize = $batchsize</info>");

        $stdin = <<<'EOD'
1|Test|http://test.com#1|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
1|Test2 asdasd|http://test.com/sfsdfsdf/ssss|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
1|Test3|http://test.com/sdasda#3|Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36
EOD;

        //$output->writeln(var_export(explode("\n", $stdin), true));

        $tracker    = new Tracker();
        $handler  = Handler\Factory::make();
        $requestSet = new RequestSet();
        //setTokenAuth($tokenAuth)

        $requests = [];
        foreach (explode("\n", $stdin) as $rowstring) {
            $output->writeln($rowstring);
            $row = explode('|', $rowstring);

            $requests[] = [
                'rec' => 1,
                'apiv' => 1,
                'send_image' => 0,
                'idsite' => $row[0],
                'action_name' => $row[1],
                'url' => $row[2],
                'ua' => $row[3],
                'cdt' => '2019-04-24 05:44:00',
            ];

            /*$request = new Request(
                [],
                [
                    'rec' => 1,
                    'apiv' => 1,
                    'send_image' => 0,
                    'idsite' => $row[0],
                    'action_name' => $row[1],
                    'url' => $row[2],
                    'ua' => $row[3],
                    'cdt' => '2019-04-24 05:44:00',
                ],
                [],
                [],
                [],
                []
            );
            $request->overrideGlobals();*/

            //require __DIR__ . '/../../../piwik.php';
            //require '/home/dshiryaev/Repo/Vendor/matomo/matomo/piwik.php';
        }

        $requestSet->setRequests($requests);

        //$handler->init($tracker, $requestSet);

        $tracker->track($handler, $requestSet);

        //$response = $tracker->main($handler, $requestSet);
    }
}
