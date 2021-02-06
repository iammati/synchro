<?php

namespace Iammati\Synchro;

use CallbackFilterIterator;
use Phar;
use PharData;
use PharException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

class Sync
{
    /**
     * @var array
     */
    protected $ddevConfig;

    /**
     * @var string
     */
    protected $host;

    /**
     * @param string $ddevDir
     */
    public function __construct(string $host, string $ddev = '')
    {
        if ($ddev == '') {
            //
            $ddev = $_SERVER['DOCUMENT_ROOT'] . '/.ddev';
        }

        $this->ddevConfig = Yaml::parseFile($ddev . '/config.yaml');

        $this->host = $host;
    }

    /**
     * @param array|string $sources
     */
    public function create(...$sources): self
    {
        $start = microtime(true);

        $projectName = $this->ddevConfig['name'];
        $dateTimeStamp = date('Y-m-d') . '_' . date('H-i-s');

        $name = $projectName . '_' . $dateTimeStamp;

        foreach ($sources as $source) {
            echo "\nCreating new '$name.tar.gz' for source '$source'...\n";

            $path = realpath($source);

            $sourceName = $name . '_' . $source;

            $sourceName = str_replace('..', '', $sourceName);
            $sourceName = str_replace('.', '', $sourceName);
            $sourceName = str_replace('/', '-', $sourceName);

            // $iterator = new RecursiveIteratorIterator(
            //     new RecursiveDirectoryIterator($path)
            // );

            // $filterIterator = new CallbackFilterIterator($iterator, function ($file) {
            //     return (
            //         strpos($file, 'vendor/') === false &&
            //         strpos($file, 'node_modules/') === false
            //     );
            // });

            try {
                $pharData = new PharData($sourceName . '.tar');

                $pharData->buildFromDirectory('./../Configuration');
                $pharData->compress(Phar::GZ);

                unlink($sourceName . '.tar');

                exec("ssh dev@{$this->host} 'mkdir /home/dev/ddev-projects/$projectName' </dev/null 2>&1");
                exec("rsync -av *.tar.gz dev@{$this->host}:/home/dev/ddev-projects/$projectName/");

                unlink($sourceName . '.tar.gz');
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $timeElapsedSecs = microtime(true) - $start;
                echo "Done after " . \round($timeElapsedSecs, 2) . " seconds.\n";
            }
        }

        return $this;
    }
}
