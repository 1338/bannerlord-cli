<?php

namespace Command;

use Service\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CreateModuleCommand extends Command
{
    protected static $defaultName = 'mod:create';

    private $modName = '';

    private $title = "Module creation";
    /** @var Config $config */
    private  $config;
    /**
     * @var Filesystem
     */
    private $fs;
    /**
     * @var Finder
     */
    private $finder;
    /**
     * @var string
     */
    private $guid;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->fs = new Filesystem();
        $this->finder = new Finder();
        $this->guid = $this->GUIDv4(false);
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('create module')->setHelp('This command allows you to create a module');
        $this->addArgument('mod_name', InputArgument::OPTIONAL, 'Mod Name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->title);

        $config = $this->config->get();

        $src = $config['folders']['src'];
        $out = $config['folders']['out'];

        if(!$src || !$out) {
            $this->io->writeln('create config first!');
        } else {
            $this->io->writeln('source path: ' . $src);
            $this->io->writeln('bannerlord path: ' . $out);
        }


        $confirmedModName = false;
        while($confirmedModName == false) {
            if($input->getArgument('mod_name')) {
                if($this->io->confirm("Do you want to name the mod '{$input->getArgument('mod_name')}'?", true)) {
                    $confirmedModName = $input->getArgument('mod_name');
                }
                $input->setArgument('mod_name', false);
            } else {
                $modName = $this->io->ask('What do you want the mod name to be?', 'ExampleMod');
                if($this->io->confirm("Do you want to name the mod '{$modName}'?", true)) {
                    $confirmedModName = $modName;
                }
            }
        }
        $this->modName = $confirmedModName;
        $cSharp = $this->io->confirm('Do you want to use C#?');

        if(!$this->createDirectories($cSharp)) {
            $this->io->writeln('Couldnt create directories');
            return 1;
        }


        $singlePlayer = $this->io->confirm('Is this mod for singleplayer?');
        $multiPlayer = $this->io->confirm('Is this mod for singleplayer?');
        $entryPoint = null;
        if($cSharp) {
            $entryPoint = $this->io->ask('What do you want your main class to be called?', 'Main');
            if($entryPoint) {
                if(!$this->createEntypointFile($entryPoint)) {
                    $this->io->writeln('Couldnt create entryPoint');
                    return 1;
                }
                if(!$this->createProjectFile($entryPoint)) {
                    $this->io->writeln('Couldnt create project file');
                    return 1;
                }
                if(!$this->createAssemblyFile()) {
                    $this->io->writeln('Couldnt create assembly file');
                    return 1;
                }
                if(!$this->createSolutionFile()) {

                }
            }
        }

        if(!$this->createSubModuleFile($cSharp, $singlePlayer, $multiPlayer, $entryPoint)) {
            $this->io->writeln('Couldnt create submodule file');
            return 1;
        }

        return 0;

    }

    private function createSolutionFile() {
        $file = <<<EOF
Microsoft Visual Studio Solution File, Format Version 12.00
# Visual Studio Version 16
VisualStudioVersion = 16.0.30011.22
MinimumVisualStudioVersion = 10.0.40219.1
Project("{$this->GUIDv4(false)}") = "{$this->modName}", "{$this->modName}\.csproj", "{$this->guid}"
EndProject
Global
	GlobalSection(SolutionConfigurationPlatforms) = preSolution
		Debug|Any CPU = Debug|Any CPU
		Release|Any CPU = Release|Any CPU
	EndGlobalSection
	GlobalSection(ProjectConfigurationPlatforms) = postSolution
		{$this->guid}.Debug|Any CPU.ActiveCfg = Debug|Any CPU
		{$this->guid}.Debug|Any CPU.Build.0 = Debug|Any CPU
		{$this->guid}.Release|Any CPU.ActiveCfg = Release|Any CPU
		{$this->guid}.Release|Any CPU.Build.0 = Release|Any CPU
	EndGlobalSection
	GlobalSection(SolutionProperties) = preSolution
		HideSolutionNode = FALSE
	EndGlobalSection
	GlobalSection(ExtensibilityGlobals) = postSolution
		SolutionGuid = {$this->GUIDv4(false)}
	EndGlobalSection
EndGlobal
EOF;
    file_put_contents("{$this->config->get()['folders']['src']}/{$this->modName}/{$this->modName}.sln", $file);
    }


    private function createDirectories($cSharp = false): bool {
        $config = $this->config->get();
        if(!$this->fs->exists($config['folders']['src'])) {
            echo 'src dir doesnt exists!';
            return false;
        }
        if(!$this->fs->exists($config['folders']['out'])) {
            echo 'modules dir doesnt exists!';
            return false;
        }
        if($this->fs->exists($config['folders']['src'] . '/' . $this->modName)) {
            echo 'src dir modname already exists!';
            return false;
        }
        if($this->fs->exists($config['folders']['out'] . "/Modules/{$this->modName}")) {
            echo 'out dir modname already exists!';
            return false;
        }
        $this->fs->mkdir([
            $config['folders']['src'] . "/{$this->modName}/{$this->modName}",
            $config['folders']['out'] . "/Modules/{$this->modName}"
        ]);
        if($cSharp) {
            $this->fs->mkdir([
                $config['folders']['out'] . "/Modules/{$this->modName}/bin/Win64_Shipping_Client/",
                $config['folders']['src'] . "/{$this->modName}/{$this->modName}/Properties/",
            ]);
        }
        return true;
    }

    private function createSubModuleFile($cSharp = false, $singlePlayer = true, $multiPlayer = false, $entryPoint = null) : bool {
        $xml_header = '<Module></Module>';
        $xml = new \SimpleXMLElement($xml_header);
        $modName = $xml->addChild('Name');
        $modName->addAttribute('value', $this->modName);
        $id = $xml->addChild('Id');
        $id->addAttribute('value', $this->modName);
        $version = $xml->addChild('Version');
        $version->addAttribute('value', 'v1.0.0');
        $singleplayerMode = $xml->addChild('SingleplayerModule');
        $singleplayerMode->addAttribute('value', ($singlePlayer ? 'true' : 'false'));
        $multiPlayerMode = $xml->addChild('MultiplayerModule');
        $multiPlayerMode->addAttribute('value', ($multiPlayer ? 'true' : 'false'));
        $dependedModules = $xml->addChild('DependedModules');
        foreach (['Native', 'SandBoxCore', 'SandBox', 'CustomBattle', 'StoryMode'] as $module) {
            ${'module' . $module} = $dependedModules->addChild('DependedModule');
            ${'module' . $module}->addAttribute('Id', $module);
        }
        if($cSharp && $entryPoint) {
            $subModules = $xml->addChild('Submodules');
            $subModule = $subModules->addChild('Submodule');
            $subModuleName = $subModule->addChild('Name');
            $subModuleName->addAttribute('value', $this->modName);
            $dllName = $subModule->addChild('DLLName');
            $dllName->addAttribute('value', $this->modName . '.dll');
            $submoduleClassType = $subModule->addChild('SubModuleClassType');
            $submoduleClassType->addAttribute('value',$this->modName . '.dll');
            $subModuleTags = $subModule->addChild('Tags');
            $tagDedictateServerType = $subModuleTags->addChild('Tag');
            $tagDedictateServerType->addAttribute('key', 'DedicatedServerType');
            $tagDedictateServerType->addAttribute('value', 'none');
            $tagIsNoRenderModeElement = $subModuleTags->addChild('Tag');
            $tagIsNoRenderModeElement->addAttribute('key', 'IsNoRenderModeElement');
            $tagIsNoRenderModeElement->addAttribute('value', 'false');
        }
        $xmls = $xml->addChild('Xmls');

        return $xml->saveXML($this->config->get()['folders']['out'] . "/Modules/{$this->modName}/". 'SubModule.xml');
    }

    private function createEntypointFile($entryPoint) {
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
    public class {$entryPoint} : MBSubModuleBase
    {
        protected override void OnSubModuleLoad()
        {

        }
    }
}

EOF;

        return file_put_contents($this->config->get()['folders']['src'] . "/{$this->modName}/{$this->modName}/$entryPoint.cs", $file);

    }

    private function createAssemblyFile() {
        $file = <<<EOF
using System.Reflection;
using System.Runtime.CompilerServices;
using System.Runtime.InteropServices;

// General Information about an assembly is controlled through the following
// set of attributes. Change these attribute values to modify the information
// associated with an assembly.
[assembly: AssemblyTitle("{$this->modName}")]
[assembly: AssemblyDescription("")]
[assembly: AssemblyConfiguration("")]
[assembly: AssemblyCompany("")]
[assembly: AssemblyProduct("{$this->modName}")]
[assembly: AssemblyCopyright("Copyright �  2020")]
[assembly: AssemblyTrademark("")]
[assembly: AssemblyCulture("")]

// Setting ComVisible to false makes the types in this assembly not visible
// to COM components.  If you need to access a type in this assembly from
// COM, set the ComVisible attribute to true on that type.
[assembly: ComVisible(false)]

// The following GUID is for the ID of the typelib if this project is exposed to COM
[assembly: Guid("97d0b5ca-5a3f-406d-acd5-80594f2480dd")]

// Version information for an assembly consists of the following four values:
//
//      Major Version
//      Minor Version
//      Build Number
//      Revision
//
// You can specify all the values or you can default the Build and Revision Numbers
// by using the '*' as shown below:
// [assembly: AssemblyVersion("1.0.*")]
[assembly: AssemblyVersion("1.0.0.0")]
[assembly: AssemblyFileVersion("1.0.0.0")]
EOF;
    return file_put_contents("{$this->config->get()['folders']['src']}/{$this->modName}/{$this->modName}/Properties/AssemblyInfo.cs", $file);
    }

    private function createProjectFile($entryPoint) {
        $seperator = DIRECTORY_SEPARATOR;
        $path = htmlspecialchars($this->config->get()['folders']['out']);
        $mainBin = $path . $seperator . 'bin' . $seperator . 'Win64_Shipping_Client' . $seperator;
        $modulePath = $path . $seperator . 'Modules' . $seperator;
        $file = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<Project ToolsVersion="15.0" xmlns="http://schemas.microsoft.com/developer/msbuild/2003">
  <Import Project="$(MSBuildExtensionsPath)\$(MSBuildToolsVersion)\Microsoft.Common.props" Condition="Exists('$(MSBuildExtensionsPath)\$(MSBuildToolsVersion)\Microsoft.Common.props')" />
  <PropertyGroup>
    <Configuration Condition=" '$(Configuration)' == '' ">Debug</Configuration>
    <Platform Condition=" '$(Platform)' == '' ">AnyCPU</Platform>
    <ProjectGuid>{$this->guid}</ProjectGuid>
    <OutputType>Library</OutputType>
    <AppDesignerFolder>Properties</AppDesignerFolder>
    <RootNamespace>{$this->modName}</RootNamespace>
    <AssemblyName>{$this->modName}</AssemblyName>
    <TargetFrameworkVersion>v4.7.2</TargetFrameworkVersion>
    <FileAlignment>512</FileAlignment>
    <Deterministic>true</Deterministic>
  </PropertyGroup>
  <PropertyGroup Condition=" '$(Configuration)|$(Platform)' == 'Debug|AnyCPU' ">
    <DebugSymbols>true</DebugSymbols>
    <DebugType>full</DebugType>
    <Optimize>false</Optimize>
    <OutputPath>{$modulePath}{$this->modName}{$seperator}bin{$seperator}Win64_Shipping_Client{$seperator}</OutputPath>
    <DefineConstants>DEBUG;TRACE</DefineConstants>
    <ErrorReport>prompt</ErrorReport>
    <WarningLevel>4</WarningLevel>
  </PropertyGroup>
  <PropertyGroup Condition=" '$(Configuration)|$(Platform)' == 'Release|AnyCPU' ">
    <DebugType>pdbonly</DebugType>
    <Optimize>true</Optimize>
    <OutputPath>bin\Release\</OutputPath>
    <DefineConstants>TRACE</DefineConstants>
    <ErrorReport>prompt</ErrorReport>
    <WarningLevel>4</WarningLevel>
  </PropertyGroup>
  <ItemGroup>
    <Reference Include="System" />
    <Reference Include="System.Core" />
    <Reference Include="System.Xml.Linq" />
    <Reference Include="System.Data.DataSetExtensions" />
    <Reference Include="Microsoft.CSharp" />
    <Reference Include="System.Data" />
    <Reference Include="System.Net.Http" />
    <Reference Include="System.Xml" />
    <Reference Include="TaleWorlds.BattlEye.Client">
      <HintPath>{$mainBin}TaleWorlds.BattlEye.Client.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.CampaignSystem">
      <HintPath>{$mainBin}TaleWorlds.CampaignSystem.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.CampaignSystem.ViewModelCollection">
      <HintPath>{$mainBin}TaleWorlds.CampaignSystem.ViewModelCollection.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Core">
      <HintPath>{$mainBin}TaleWorlds.Core.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Core.ViewModelCollection">
      <HintPath>{$mainBin}TaleWorlds.Core.ViewModelCollection.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Diamond">
      <HintPath>{$mainBin}TaleWorlds.Diamond.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Diamond.AccessProvider.Epic">
      <HintPath>{$mainBin}TaleWorlds.Diamond.AccessProvider.Epic.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Diamond.AccessProvider.Steam">
      <HintPath>{$mainBin}TaleWorlds.Diamond.AccessProvider.Steam.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Diamond.AccessProvider.Test">
      <HintPath>{$mainBin}TaleWorlds.Diamond.AccessProvider.Test.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.DotNet">
      <HintPath>{$mainBin}TaleWorlds.DotNet.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.DotNet.AutoGenerated">
      <HintPath>{$mainBin}TaleWorlds.DotNet.AutoGenerated.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Engine">
      <HintPath>{$mainBin}TaleWorlds.Engine.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Engine.AutoGenerated">
      <HintPath>{$mainBin}TaleWorlds.Engine.AutoGenerated.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Engine.GauntletUI">
      <HintPath>{$mainBin}TaleWorlds.Engine.GauntletUI.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.GauntletUI">
      <HintPath>{$mainBin}TaleWorlds.GauntletUI.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.GauntletUI.Data">
      <HintPath>{$mainBin}TaleWorlds.GauntletUI.Data.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.GauntletUI.ExtraWidgets">
      <HintPath>{$mainBin}TaleWorlds.GauntletUI.ExtraWidgets.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.GauntletUI.PrefabSystem">
      <HintPath>{$mainBin}TaleWorlds.GauntletUI.PrefabSystem.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.GauntletUI.TooltipExtensions">
      <HintPath>{$mainBin}TaleWorlds.GauntletUI.TooltipExtensions.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.InputSystem">
      <HintPath>{$mainBin}TaleWorlds.InputSystem.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Library">
      <HintPath>{$mainBin}TaleWorlds.Library.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Localization">
      <HintPath>{$mainBin}TaleWorlds.Localization.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.AutoGenerated">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.AutoGenerated.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.CustomBattle">
      <HintPath>{$modulePath}CustomBattle{$seperator}bin{$seperator}Win64_Shipping_Client{$seperator}TaleWorlds.MountAndBlade.CustomBattle.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.Diamond">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.Diamond.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.GauntletUI">
      <HintPath>{$modulePath}Native{$seperator}bin{$seperator}Win64_Shipping_Client{$seperator}TaleWorlds.MountAndBlade.GauntletUI.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.GauntletUI.Widgets">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.GauntletUI.Widgets.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.Helpers">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.Helpers.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.View">
      <HintPath>{$modulePath}Native{$seperator}bin{$seperator}Win64_Shipping_Client{$seperator}TaleWorlds.MountAndBlade.View.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.MountAndBlade.ViewModelCollection">
      <HintPath>{$mainBin}TaleWorlds.MountAndBlade.ViewModelCollection.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.NavigationSystem">
      <HintPath>{$mainBin}TaleWorlds.NavigationSystem.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Network">
      <HintPath>{$mainBin}TaleWorlds.Network.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.PlatformService">
      <HintPath>{$mainBin}TaleWorlds.PlatformService.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.PlatformService.Epic">
      <HintPath>{$mainBin}TaleWorlds.PlatformService.Epic.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.PlatformService.Steam">
      <HintPath>{$mainBin}TaleWorlds.PlatformService.Steam.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.PlayerServices">
      <HintPath>{$mainBin}TaleWorlds.PlayerServices.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.PSAI">
      <HintPath>{$mainBin}TaleWorlds.PSAI.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.SaveSystem">
      <HintPath>{$mainBin}TaleWorlds.SaveSystem.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Starter.DotNetCore">
      <HintPath>{$mainBin}TaleWorlds.Starter.DotNetCore.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.Starter.Library">
      <HintPath>{$mainBin}TaleWorlds.Starter.Library.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.TwoDimension">
      <HintPath>{$mainBin}TaleWorlds.TwoDimension.dll</HintPath>
    </Reference>
    <Reference Include="TaleWorlds.TwoDimension.Standalone">
      <HintPath>{$mainBin}TaleWorlds.TwoDimension.Standalone.dll</HintPath>
    </Reference>
  </ItemGroup>
  <ItemGroup>
    <Compile Include="{$entryPoint}.cs" />
    <Compile Include="Properties\AssemblyInfo.cs" />
  </ItemGroup>
  <Import Project="$(MSBuildToolsPath)\Microsoft.CSharp.targets" />
</Project>
EOF;
        return file_put_contents($this->config->get()['folders']['src'] . "/{$this->modName}/{$this->modName}/.csproj", $file);
    }

    public function GUIDv4 ($trim = true)
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
            substr($charid,  0,  8).$hyphen.
            substr($charid,  8,  4).$hyphen.
            substr($charid, 12,  4).$hyphen.
            substr($charid, 16,  4).$hyphen.
            substr($charid, 20, 12).
            $rbrace;
        return $guidv4;
    }
}