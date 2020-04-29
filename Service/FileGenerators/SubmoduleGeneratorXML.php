<?php

namespace Service\FileGenerators;

use Symfony\Component\Filesystem\Filesystem;

class SubmoduleGeneratorXML {

    private $modName = '';
    private $cSharp = false;
    private $singlePlayer = false;
    private $multiPlayer = false;
    private $entryPoint = null;
    private $path = '';

    public function __construct(string $path, string $modName, $cSharp = false, $singlePlayer = true, $multiPlayer = false, $entryPoint = null)
    {
        $this->path = $path;
        $this->modName = $modName;
        $this->cSharp = $cSharp;
        $this->singlePlayer = $singlePlayer;
        $this->multiPlayer = $multiPlayer;
        $this->entryPoint = $entryPoint;
    }

    public function createFile(): bool {
        $xml_header = '<Module></Module>';
        $xml = new \SimpleXMLElement($xml_header);
        $modName = $xml->addChild('Name');
        $modName->addAttribute('value', $this->modName);
        $id = $xml->addChild('Id');
        $id->addAttribute('value', $this->modName);
        $version = $xml->addChild('Version');
        $version->addAttribute('value', 'v1.0.0');
        $singleplayerMode = $xml->addChild('SingleplayerModule');
        $singleplayerMode->addAttribute('value', ($this->singlePlayer ? 'true' : 'false'));
        $multiPlayerMode = $xml->addChild('MultiplayerModule');
        $multiPlayerMode->addAttribute('value', ($this->multiPlayer ? 'true' : 'false'));
        $dependedModules = $xml->addChild('DependedModules');
        foreach (['Native', 'SandBoxCore', 'Sandbox', 'CustomBattle', 'StoryMode'] as $module) {
            ${'module' . $module} = $dependedModules->addChild('DependedModule');
            ${'module' . $module}->addAttribute('Id', $module);
        }
        $subModules = $xml->addChild('SubModules');
        if($this->cSharp && $this->entryPoint) {
            $subModule = $subModules->addChild('SubModule');
            $subModuleName = $subModule->addChild('Name');
            $subModuleName->addAttribute('value', $this->modName);
            $dllName = $subModule->addChild('DLLName');
            $dllName->addAttribute('value', $this->modName . '.dll');
            $submoduleClassType = $subModule->addChild('SubModuleClassType');
            $submoduleClassType->addAttribute('value', "{$this->modName}.{$this->entryPoint}");
            $subModuleTags = $subModule->addChild('Tags');
            $tagDedictateServerType = $subModuleTags->addChild('Tag');
            $tagDedictateServerType->addAttribute('key', 'DedicatedServerType');
            $tagDedictateServerType->addAttribute('value', 'none');
            $tagIsNoRenderModeElement = $subModuleTags->addChild('Tag');
            $tagIsNoRenderModeElement->addAttribute('key', 'IsNoRenderModeElement');
            $tagIsNoRenderModeElement->addAttribute('value', 'false');
        }
        $xmls = $xml->addChild('Xmls');

        return $xml->saveXML($this->path . "/Modules/{$this->modName}/". 'SubModule.xml');
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
     * @return bool
     */
    public function isCSharp(): bool
    {
        return $this->cSharp;
    }

    /**
     * @param bool $cSharp
     */
    public function setCSharp(bool $cSharp): void
    {
        $this->cSharp = $cSharp;
    }

    /**
     * @return bool
     */
    public function isSinglePlayer(): bool
    {
        return $this->singlePlayer;
    }

    /**
     * @param bool $singlePlayer
     */
    public function setSinglePlayer(bool $singlePlayer): void
    {
        $this->singlePlayer = $singlePlayer;
    }

    /**
     * @return bool
     */
    public function isMultiPlayer(): bool
    {
        return $this->multiPlayer;
    }

    /**
     * @param bool $multiPlayer
     */
    public function setMultiPlayer(bool $multiPlayer): void
    {
        $this->multiPlayer = $multiPlayer;
    }

    /**
     * @return null
     */
    public function getEntryPoint()
    {
        return $this->entryPoint;
    }

    /**
     * @param null $entryPoint
     */
    public function setEntryPoint($entryPoint): void
    {
        $this->entryPoint = $entryPoint;
    }
}