<?php

namespace Iammati\Synchro;

use Symfony\Component\Yaml\Yaml;

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
    public function __construct(string $host, string $ddev)
    {
        $this->ddevConfig = Yaml::parseFile($ddev . '/config.yaml');

        $this->host = $host;
    }

    /**
     * @param array $excludes
     * @param mixed $sources
     */
    public function create(array $excludes, ...$sources): self
    {
        $start = microtime(true);

        $projectName = $this->ddevConfig['name'];
        $dateTimeStamp = date('Y-m-d') . '_' . date('H-i-s');

        $name = $projectName . '_' . $dateTimeStamp;

        $excludesStr = '';

        foreach ($excludes as $exclude) {
            $excludesStr .= ' --exclude ' . $exclude;
        }

        foreach ($sources as $source) {
            echo "\nCreating new '$name.tar.gz' for source '$source'...\n";

            $sourceName = $name . '_' . $source;

            $sourceName = str_replace('..', '', $sourceName);
            $sourceName = str_replace('.', '', $sourceName);
            $sourceName = str_replace('/', '-', $sourceName);

            try {
                exec("tar -zcvf $sourceName.tar.gz$excludesStr $source /dev/null 2>&1");

                exec("ssh dev@{$this->host} 'mkdir /home/dev/ddev-projects/$projectName' && rm -rf /home/dev/ddev-projects/$projectName/*.tar.gz /dev/null 2>&1");
                exec("rsync -av *.tar.gz dev@{$this->host}:/home/dev/ddev-projects/$projectName/");

                unlink("$sourceName.tar.gz");
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $timeElapsedSecs = microtime(true) - $start;
                echo "Done after " . round($timeElapsedSecs, 2) . " seconds.\n";
                $start = microtime(true);
            }
        }

        return $this;
    }
}
