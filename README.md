# bannerlord-cli #

## installation of releases ##
1. Get the most recent release  from the releases page: [here](https://github.com/1338/bannerlord-cli/releases)
2. make sure you have php installed, for Windows follow [this](https://www.sitepoint.com/how-to-install-php-on-windows/) guide if you aren't sure

## installation of source ##
You need these things:
- php
- composer
```
git clone git@github.com:1338/bannerlord-cli.git
cd bannerlord-cli
composer install
```

## configuration ##
To configure your bannerlord-cli run:
```
php bannerlord-cli config
```
the `source` is where your src files will live (c#)
the `bannerlord` is the main bannerlord path
Example:
```
 source path: []:
 > C:\Users\1338\source\repos

 confirm path: C:\Users\1338\source\repos (yes/no) [yes]:
 > yes

 Bannerlord path: []:
 > D:\steam\steamapps\common\Mount & Blade II Bannerlord

 confirm path: D:\steam\steamapps\common\Mount & Blade II Bannerlord (yes/no) [yes]:
 > yes

```
## create mod ##
To actually create a mod(ule):
```
php bannerlord-cli
```
Or add the mod name at the end
```
php bannerlord-cli TestMod
```
An example of running through the command:
```
Module creation
===============

source path: C:\Users\1338\source\repos
bannerlord path: D:\steam\steamapps\common\Mount & Blade II Bannerlord

 What do you want the mod name to be? [ExampleMod]:
 > TestMod

 Do you want to name the mod 'TestMod'? (yes/no) [yes]:
 >

 Do you want to use C#? (yes/no) [yes]:
 >

 Is this mod for singleplayer? (yes/no) [yes]:
 >

 Is this mod for singleplayer? (yes/no) [yes]:
 > no

 What do you want your main class to be called? [Main]:
 >
```
Choosing for `Do you want to use C#?` will do several things
- create the `/bin/Win64_Shipping_Client` folder
- create a solution file
- create the .csproj file
- create the main .cs file (according to ModName.MainClassName)
- create Properties/AssemblyInfo.cs
- set up to output in `Bannerlord\Modules\modName\bin\Win64_Shipping_Client`
This allows you to open the solution then just solution -> build solution, and it will build to the correct place

Keep in mind, that this is including all the TaleWorlds.*.dll, so you may want to remove some if need be.