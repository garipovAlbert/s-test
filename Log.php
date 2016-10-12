<?php

use Kassner\LogParser\LogParser;

/**
 * @author Albert Garipov <bert320@gmail.com>
 */
class Log
{

    /**
     * See http://httpd.apache.org/docs/1.3/logs.html#combined
     */
    const LOG_COMBINED_FORMAT = '%h %l %u %t "%r" %>s %b "(?P<referer>[^\"]+)" "(?P<useragent>[^\"]+)"';

    public $total = 0;
    public $statuses = [];
    public $files = [];
    public $referers = [];
    public $useragents = [];

    public function __construct($filename)
    {
        /* Parse data */
        $parser = new LogParser(self::LOG_COMBINED_FORMAT);

        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new Exception('Wrong file name!');
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $entry = $parser->parse(trim($line));

            $this->statuses[$entry->status] = isset($this->statuses[$entry->status]) ? $this->statuses[$entry->status] + 1 : 1;

            // getting filename from the request
            preg_match('#(?P<request>(?:(?:[A-Z]+) (?P<url>.+?) HTTP/1.(?:0|1))|-|)#', $entry->request, $m);
            $file = $m['url'];
            $this->files[$file] = isset($this->files[$file]) ? $this->files[$file] + 1 : 1;

            $this->referers[$entry->referer] = isset($this->referers[$entry->referer]) ? $this->referers[$entry->referer] + 1 : 1;

            $this->useragents[$entry->useragent] = isset($this->useragents[$entry->useragent]) ? $this->useragents[$entry->useragent] + 1 : 1;

            $this->total++;
        }
        fclose($handle);
    }

    public function printReport()
    {
        print("Total number of entries: {$this->total}\n");

        print("Statuses (code: number of entries)\n");
        foreach ($this->statuses as $code => $count) {
            print("  {$code}: {$count}\n");
        }

        $size = 5;

        print("Top {$size} files (url: number of entries)\n");
        $this->printTop($this->files, $size);

        print("Top {$size} referers (url: number of entries)\n");
        $this->printTop($this->referers, $size);

        print("Top {$size} useragents (name: number of entries)\n");
        $this->printTop($this->useragents, $size);
    }

    private function printTop($array, $size)
    {
        arsort($array);
        $array = array_slice($array, 0, min([count($array), $size]));
        foreach ($array as $name => $count) {
            $percent = round($count / $this->total * 100, 2);
            print("  \"{$name}\": {$count} ({$percent}%)\n");
        }
    }

}