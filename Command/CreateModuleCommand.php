<?php

namespace Command;

use Service\Config;
use Service\GUIDGenerator;
use Service\FileGenerators\AssemblyGenerator;
use Service\FileGenerators\EntryPointGenerator;
use Service\FileGenerators\SubmoduleGeneratorXML;
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
    private $modGUID = '';
    /**
     * @var string
     */
    private $solutionGUID = '';
    /**
     * @var string
     */
    private $projectGUID = '';


    /**
     * CreateModuleCommand constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->fs = new Filesystem();
        $this->finder = new Finder();
        $this->modGUID = GUIDGenerator::Generate(false);
        $this->solutionGUID = GUIDGenerator::Generate(false);
        $this->projectGUID = GUIDGenerator::Generate(false);
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

        if(!$src || !$out || $src = '' || $out = '') {
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
        $multiPlayer = $this->io->confirm('Is this mod for multiplayer?');
        $entryPoint = null;
        if($cSharp) {
            $entryPoint = $this->io->ask('What do you want your main class to be called?', 'Main');
            if($entryPoint) {
                $entryPointGenerator = new EntryPointGenerator($this->config->get()['folders']['src'], $this->modName, $entryPoint);
                if(!$entryPointGenerator->createFile()) {
                    $this->io->writeln('Could not create class file');
                    return 1;
                }
                if(!$this->createProjectFile($entryPoint)) {
                    $this->io->writeln('Couldnt create project file');
                    return 1;
                }
                $assemblyGenerator = new AssemblyGenerator($this->config->get()['folders']['src'], $this->modName, $this->modGUID);
                if(!$assemblyGenerator->createFile()) {
                    $this->io->writeln('Couldnt create assembly file');
                    return 1;
                }
                if(!$this->createSolutionFile()) {

                }
            }
        }

        $submoduleGenerator = new SubmoduleGeneratorXML(
            $this->config->get()['folders']['out'],
            $this->modName,
            true,
            $singlePlayer,
            $multiPlayer,
            $entryPoint
        );
        if(!$submoduleGenerator->createFile()) {
            $this->io->writeln('Could not create submodule.xml file');
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
Project("{$this->modGUID}") = "{$this->modName}", "{$this->modName}\.csproj", "{$this->projectGUID}"
EndProject
Global
	GlobalSection(SolutionConfigurationPlatforms) = preSolution
		Debug|Any CPU = Debug|Any CPU
		Release|Any CPU = Release|Any CPU
	EndGlobalSection
	GlobalSection(ProjectConfigurationPlatforms) = postSolution
		{$this->modGUID}.Debug|Any CPU.ActiveCfg = Debug|Any CPU
		{$this->modGUID}.Debug|Any CPU.Build.0 = Debug|Any CPU
		{$this->modGUID}.Release|Any CPU.ActiveCfg = Release|Any CPU
		{$this->modGUID}.Release|Any CPU.Build.0 = Release|Any CPU
	EndGlobalSection
	GlobalSection(SolutionProperties) = preSolution
		HideSolutionNode = FALSE
	EndGlobalSection
	GlobalSection(ExtensibilityGlobals) = postSolution
		SolutionGuid = {$this->solutionGUID}
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
    <ProjectGuid>{$this->projectGUID}</ProjectGuid>
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
}