<?php

namespace Iammati\Synchro;

use Symfony\Component\Yaml\Yaml;

class Sync
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var array
     */
    protected $ddevConfig;

    /**
     * @var string
     */
    protected $projectName;

    /**
     * @param string $host
     * @param string $pathToDdev
     */
    public function __construct(string $host, string $pathToDdev)
    {
        $this->host = $host;

        $this->ddevConfig = Yaml::parseFile($pathToDdev . '/config.yaml');
    }

    /**
     * @param array $excludes
     * @param mixed $sources
     */
    public function create(array $excludes, ...$sources): self
    {
        $start = microtime(true);

        $this->projectName = $this->ddevConfig['name'];
        $dateTimeStamp = date('Y-m-d') . '_' . date('H-i-s');

        $name = $this->projectName . '_' . $dateTimeStamp;

        $excludesStr = '';

        foreach ($excludes as $exclude) {
            $excludesStr .= ' --exclude ' . $exclude;
        }

        $startTime = microtime(true);

        $this->dumpDatabase();

        foreach ($sources as $source) {
            echo "\nCreating new '$name.tar.gz' for source '$source'...\n";
            echo "\n======================================================================================================\n\n";

            $sourceName = $name . '_' . $source;

            $sourceName = str_replace('..', '', $sourceName);
            $sourceName = str_replace('.', '', $sourceName);
            $sourceName = str_replace('/', '-', $sourceName);

            try {
                // Creating the .tar.gz-file respectively to the excluded directories, if any
                exec("tar -zcvf $sourceName.tar.gz$excludesStr $source >/dev/null 2>&1");

                // Creates project directory at remote host
                exec("ssh dev@{$this->host} 'mkdir /home/dev/ddev-projects/{$this->projectName}' >/dev/null 2>&1 && rm -rf /home/dev/ddev-projects/{$this->projectName}/*.tar.gz >/dev/null 2>&1");

                // Rsync of the generated .tar.gz-file to the remote host
                $this->rsync();
            } catch (\Exception $e) {
                throw $e;
            } finally {
                unlink("$sourceName.tar.gz");
                $timeElapsedSecs = microtime(true) - $start;
                echo "\n======================================================================================================\n";
                echo "Done after " . round($timeElapsedSecs, 2) . " second(s).\n";
                $start = microtime(true);
            }
        }

        echo "======================================================================================================\n\n";
        echo "Synchronization finished after " . round(microtime(true) - $startTime) . " total second(s).\n\n";
        echo "======================================================================================================\n\n";

        return $this;
    }

    /**
     * @return Sync
     */
    protected function rsync(): self
    {
        $query = "rsync -av --info=progress2 --no-i-r *.tar.gz dev@{$this->host}:/home/dev/ddev-projects/{$this->projectName}/";

        $proc = popen($query, 'r');

        while ($progress = fgets($proc, 12)) {
            echo $progress;
        }

        pclose($proc);

        return $this;
    }

    /**
     * @return Sync;
     */
    protected function dumpDatabase(): self
    {
        exec("ddev export-db --file=../../dump-{$this->projectName}.sql.gz");
        exec("rsync -av *.tar.gz dev@{$this->host}:/home/dev/ddev-projects/{$this->projectName}/");

        return $this;
    }
}
