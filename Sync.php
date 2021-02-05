<?php

namespace Iammati\Synchro;

use Phar;
use PharData;
use Symfony\Component\Yaml\Yaml;

class Sync
{
    /**
     * @var array
     */
    protected $ddevConfig;

    /**
     * @param string $ddevDir
     */
    public function __construct(string $ddev = '')
    {
        if ($ddev == '') {
            $ddev = $_SERVER['DOCUMENT_ROOT'] . '/.ddev';
        }

        $this->ddevConfig = Yaml::parseFile('/config.yaml');
    }

    /**
     * @param string $source
     */
    public function create(string $source): self
    {
        $projectName = $this->ddevConfig['name'];
        $dateTimeStamp = date('Y-m-d') . '_' . date('H-i');

        $name = $projectName . '_' . $dateTimeStamp;
        echo "Creating new $name.tar.gz...\n";

        try {
            $pharData = new PharData($name . '.tar');

            $pharData->buildFromDirectory($source);
            $pharData->compress(Phar::GZ);

            unlink($name . '.tar');

            echo "Done.\n\n";
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }
}
