<?php
namespace Druman;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Druman\Alias;
class AliasFactory
{
    private function __construct(){}
    
    public static function generateAliases(string $aliasesFilePath)
    {
        $questionObjs = [];
        try {
            $ymlData = Yaml::parse(file_get_contents($aliasesFilePath));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
        
        foreach ($ymlData['projects'] as $alias) {
          //$aliasObjs[] = new Alias($alias);
          $aliasObjs[$alias['alias']] = $alias;
        }
        return $aliasObjs;
    }
}

