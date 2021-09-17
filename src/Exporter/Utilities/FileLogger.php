<?php

declare(strict_types=1);

namespace App\Exporter\Utilities;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * A wrapper for OutputInterface that allows us to log the output in a file that
 * Javascript can use to get the progress.
 */
class FileLogger
{
    /**
     * The data to be logged
     *
     * @var array
     */
    public $data = [];

    /**
     * The path including the filename to store the log data
     *
     * @var string
     */
    private $logFilePath = '';

    /**
     * The OutputInterface use for the console
     *
     * @var OutputInterface
     */
    private $output = null;

    public function __construct(
        OutputInterface $output,
        string $logFilePath
    ) {
        $this->output = $output;
        $this->logFilePath = $logFilePath;
        if (file_exists($this->logFilePath)) {
            unlink($this->logFilePath);
        }
    }

    /**
     * Log a message that has no error code
     *
     * @param string $message The message to log
     *
     * @return void
     */
    public function log(string $message): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => false,
            'isError' => false,
            'message' => $message,
            'timestamp' => $now->getTimestamp(),
        ];
        $this->data[] = $content;
        $this->save();
        $this->output->writeln(
            $content['timestamp'].' : '.$message
        );
    }

    /**
     * Log that the script is completed
     *
     * @param string $processName the name of the process that has completed
     *
     * @return void
     */
    public function logFinished(string $processName): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => true,
            'isError' => false,
            'message' => 'FINISHED',
            'timestamp' => $now->getTimestamp(),
        ];
        $this->data[] = $content;
        $this->save();
        $this->output->writeln(
            $content['timestamp'].' : '.$processName.' has completed!'
        );
    }

    /**
     * Log a error that has an error code
     *
     * @param string $message The message to log
     *
     * @return void
     */
    public function logError(string $message): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => false,
            'isError' => true,
            'message' => $message,
            'timestamp' => $now->getTimestamp(),
        ];
        $this->data[] = $content;
        $this->save();
        $this->output->writeln(
            $content['timestamp'].' : '.$message.' [ERROR]'
        );
    }

    /**
     * Save to the file
     */
    private function save(): void
    {
        file_put_contents($this->logFilePath, json_encode($this->data));
    }
}
