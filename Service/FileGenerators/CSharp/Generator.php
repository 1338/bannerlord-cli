<?php

namespace Service\FileGenerators\CSharp;

/**
 * Class Generate
 * Basic C# file generator
 * Currntly just a placeholder
 * This will implement generating c# in a nicer way then the <<< >>> structure here.
 * @package Service\FileGenerators\CSharp
 */
class Generator {

    /**
     * @var string
     */
    private $cSharp = '';
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var array
     */
    private $references;
    /**
     * @var array
     */
    private $methods;
    /**
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var string
     */
    private $path;


    public function __construct(string $path, string $fileName, string $namespace, string $className)
    {
        $this->fileName = $fileName;
        $this->path = $path;
        $this->namespace = $namespace;
        $this->className = $className;
        $this->methods = [];
        $this->references = [];
        $this->cSharp = '';
    }




    public function genrate()
    {
        // TODO: generate cSharp from set values
    }
    public function saveToFile() {

    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return array
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * @param array $references
     */
    public function setReferences(array $references): void
    {
        $this->references = $references;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array $methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

}