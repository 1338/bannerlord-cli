<?php

namespace Service\FileGenerators;

class EntryPointGenerator {

    /**
     * @var string
     */
    private $modName = '';
    /**
     * @var string
     */
    private $entryPoint = '';

    /**
     * @var string
     */
    private $path = '';

    public function __construct(string $path, string $modName, string $entryPoint)
    {
        $this->path = $path;
        $this->modName = $modName;
        $this->entryPoint = $entryPoint;
    }

    /**
     * @return bool
     */
    public function createFile(): bool {
        $file = <<<EOF
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using TaleWorlds.Core;
using TaleWorlds.Localization;
using TaleWorlds.MountAndBlade;

namespace {$this->modName}
{
    public class {$this->entryPoint} : MBSubModuleBase
    {
        protected override void OnSubModuleLoad()
        {

        }
    }
}

EOF;
        return file_put_contents($this->path . "/{$this->modName}/{$this->modName}/$this->entryPoint.cs", $file);
    }

    /**
     * @return string
     */
    public function getModName(): string
    {
        return $this->modName;
    }

    /**
     * @param string $modName
     */
    public function setModName(string $modName): void
    {
        $this->modName = $modName;
    }

    /**
     * @return string
     */
    public function getEntryPoint(): string
    {
        return $this->entryPoint;
    }

    /**
     * @param string $entryPoint
     */
    public function setEntryPoint(string $entryPoint): void
    {
        $this->entryPoint = $entryPoint;
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