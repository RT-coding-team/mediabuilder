<?php

declare(strict_types=1);

namespace App\Utilities;

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
     * A counter to be used in logging
     *
     * @var int
     */
    private $counter = 0;

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

    /**
     * Build the class
     *
     * @param OutputInterface $output The output interface
     * @param string $logFilePath The path to the JSON log file
     */
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
     * Increment the counter by one
     */
    public function increaseCounter(): void
    {
        ++$this->counter;
    }

    /**
     * Log a message that has no error code
     *
     * @param string $message The message to log
     */
    public function log(string $message): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => false,
            'counter' => $this->counter,
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
     */
    public function logFinished(string $processName): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => true,
            'counter' => $this->counter,
            'isError' => false,
            'message' => $processName.' has completed!',
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
     */
    public function logError(string $message): void
    {
        $now = new \DateTime();
        $content = [
            'completed' => false,
            'counter' => $this->counter,
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
     * Reduce the counter by one
     */
    public function reduceCounter(): void
    {
        --$this->counter;
    }

    /**
     * Reset the counter
     */
    public function resetCounter(): void
    {
        $this->counter = 0;
    }

    /**
     * Save to the file
     */
    private function save(): void
    {
        file_put_contents($this->logFilePath, json_encode($this->data));
    }
}
