<?php

class SpoolReader
{
    private $spoolDir;

    private $messages = array();

    public function __construct($dir)
    {
        if (is_dir($dir)) {
            $this->spoolDir = $dir;
        } else {
            throw new Exception("Folder $dir not found");
        }
    }

    /**
     * Reads all the messages in the spool and returns then in an array
     *
     * @return array
     */
    public function run($limit = null)
    {
        $this->messages = array();

        // Get all the files from spool dir
        $files = glob($this->spoolDir . '/*');
        if ($files === false) {
            return $this->messages;
        }

        $tmp = array();
        // Sort the files by time
        foreach ($files as $fullPath) {
            if (!is_file($fullPath)) {
                continue;
            }

            $mtime = filemtime($fullPath);
            if ($mtime === false) {
                continue;
            }

            $tmp[basename($fullPath)] = $mtime;
        }

        arsort($tmp);
        $files = array_keys($tmp);

        $maxItems = null;
        if (is_numeric($limit)) {
            $maxItems = (int) $limit;
            if ($maxItems < 1) {
                $maxItems = 1;
            }
        }

        foreach ($files as $file) {
            $fullPath = $this->spoolDir . '/' . $file;
            if (!is_file($fullPath)) {
                continue;
            }

            try {
                $message = $this->parseFile($fullPath);
                $this->messages[] = $message;
            } catch (Exception $e) {
                // Skip unreadable/corrupt spool files without breaking the whole inbox.
                continue;
            }

            if ($maxItems !== null && count($this->messages) >= $maxItems) {
                break;
            }
        }

        return $this->messages;
    }

    /**
     * Deletes all messages in the spool directory
     */
    public function clear()
    {
        // Get all the files from spool dir
        $files = glob($this->spoolDir . '/*');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Parse a spool email and return it's contents as an array
     *
     * @param string $filename Absolute path to the file
     *
     * @throws Exception
     * @return array
     */
    private function parseFile($filename)
    {
        if (file_exists($filename)) {
            $file = file_get_contents($filename);
            if ($file === false) {
                throw new Exception("Unable to read file $filename");
            }
        } else {
            throw new Exception("File $filename not found");
        }

        /* @var $swiftMessage Swift_Message */
        $swiftMessage = @unserialize($file);
        if (!$swiftMessage instanceof Swift_Message) {
            throw new Exception("Invalid spool message in $filename");
        }

        // Initialize the array that will hold our parsed message
        $messageHeaders = array();

        foreach ($swiftMessage->getHeaders()->getAll() as $header) {
            $messageHeaders[$header->getFieldName()] = $header->getFieldBodyModel();
        }

        $message = array(
            'headers' => $messageHeaders,
            'body' => $swiftMessage->getBody(),
        );

        return $message;
    }
}
