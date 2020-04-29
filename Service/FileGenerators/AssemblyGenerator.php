<?php

namespace Service\FileGenerators;

class AssemblyGenerator {

    /**
     * @var string
     */
    private $path = '';
    /**
     * @var string
     */
    private $modName = '';
    /**
     * @var string
     */
    private $modGUID = '';

    public function __construct(string $path, string $modName, string $modGUID)
    {
        $this->path = $path;
        $this->modName = $modName;
        $this->modGUID = $modGUID;
    }


    public function createFile()
    {
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
[assembly: AssemblyCopyright("Copyright ?  2020")]
[assembly: AssemblyTrademark("")]
[assembly: AssemblyCulture("")]

// Setting ComVisible to false makes the types in this assembly not visible
// to COM components.  If you need to access a type in this assembly from
// COM, set the ComVisible attribute to true on that type.
[assembly: ComVisible(false)]

// The following GUID is for the ID of the typelib if this project is exposed to COM
[assembly: Guid("{$this->modGUID}")]

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
        return file_put_contents("{$this->path}/{$this->modName}/{$this->modName}/Properties/AssemblyInfo.cs", $file);
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
    public function getModGUID(): string
    {
        return $this->modGUID;
    }

    /**
     * @param string $modGUID
     */
    public function setModGUID(string $modGUID): void
    {
        $this->modGUID = $modGUID;
    }
}
