<?php

namespace Intersect\Database\Migrations;

use Intersect\Core\Logger\Logger;
use Intersect\Core\Storage\FileStorage;
use Intersect\Core\Exception\ObjectNotFoundException;

class Generator {

    /** @var FileStorage */
    private $fileStorage;

    /** @var Logger */
    private $logger;

    /** @var string */
    private $migrationsPath;

    public function __construct(FileStorage $fileStorage, Logger $logger, $migrationsPath)
    {
        $this->fileStorage = $fileStorage;
        $this->logger = $logger;
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    public function generate($name, $templateName)
    {
        $migrationsPath = $this->getMigrationsPath();
        if (!$this->fileStorage->directoryExists($migrationsPath))
        {
            $this->fileStorage->writeDirectory($migrationsPath);
        }

        $templatePath = $this->generateFullTemplatePath($templateName);

        if (!$this->fileStorage->fileExists($templatePath))
        {
            throw new \InvalidArgumentException('Template file does not exist: ' . $templateName . '.template');
        }

        $path = $this->generateFullPath($name);

        if ($this->fileStorage->fileExists($path))
        {
            throw new \InvalidArgumentException('Cannot generate file, file already exists: ' . $path);
        }

        $templateContents = null;

        try {
            $templateContents = $this->fileStorage->getFile($templatePath);
        } catch (ObjectNotFoundException $e) {
            throw new \InvalidArgumentException('There were problems retrieving template file contents: ' . $templatePath);
        }

        $className = MigrationHelper::resolveClassNameFromPath($path);
        $templateContents = str_replace('{{CLASS_NAME}}', $className, $templateContents);

        $this->fileStorage->writeFile($path, $templateContents);

        $this->logger->info('Generated file: ' . $path);
    }

    private function generateFullPath($name)
    {
        return $this->getMigrationsPath() . '/' . date('Y_m_d_His') . '_' . $name . '_' . time() . '.php';
    }

    private function generateFullTemplatePath($templateName)
    {
        return $this->getTemplatePath() . '/' . $templateName . '.template';
    }

    private function getMigrationsPath()
    {
        return $this->migrationsPath;
    }

    private function getTemplatePath()
    {
        return __DIR__ . '/templates';
    }

}