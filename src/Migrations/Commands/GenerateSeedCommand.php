<?php

namespace Intersect\Database\Migrations\Commands;

use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Migrations\Generator;

class GenerateSeedCommand extends AbstractCommand {

    /** @var Generator */
    private $generator;

    public function __construct($migrationsPath)
    {
        parent::__construct();
        $this->generator = new Generator(new FileStorage(), $this->logger, $migrationsPath);
    }

    public function getDescription()
    {
        return 'Generates a seed file based on a blank template';
    }

    public function getParameters()
    {
        return [
            'name' => 'Name used in file generation'
        ];
    }

    public function execute($data = [])
    {
        if (!isset($data[0]))
        {
            $this->logger->warn('Please enter a seed name');
            exit();
        }

        $this->generator->generate($data[0], 'blank-seed');
    }

}