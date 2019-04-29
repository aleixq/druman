<?php

namespace Druman;
/**
 * Methods to filter arrays.
 */
trait ProjectsFiltersTrait {
 /**
  * Filters an array by local, by remote or all.
  * 
  * @param bool $local
  *   If local items must be included.
  * @param bool $remote
  *   If remote items must be included.
  * @param [] $aliases
  *   The aliases to filter.
  *
  * @return []
  *   The filtered aliases or all if filters not opted in.
  */
  protected function filterByOrigin(bool $local, bool $remote, Array $aliases){
    // If none of local or remote are true return all.
    if (!$local && !$remote){
	return $aliases;
    }
    $results = [];
    foreach ($aliases as $key=>$alias){
      if ($local){
	if(substr( $alias['path'], 0, 1 ) === "/"){
	  $results[] = $alias;
	}
      }
      if ($remote){
	if(substr( $alias['path'], 0, 1 ) !== "/"){
	  $results[] = $alias;
	}
      }
    }
    return $results;
  }
    
 /**
  * Filters an array by group, if any.
  * 
  * @param string $group
  *   The group to filter by.
  * @param [] $aliases
  *   The aliases to filter.
  *
  * @return []
  *   The filtered aliases or all if filter not opted in.
  */
  protected function filterByGroup($group, Array $aliases){
    if (!$group){
      return $aliases;
    }
    $results = [];
    foreach($aliases as $key=>$alias){
      if ($group){
        $groups = explode(',', $alias['groups']);
	if(!in_array($group, $groups)){
	  continue;
	}
	$results[] = $alias;
      }
    }
    return $results;
  }

 /**
  * Filters an array by alias, if any.
  *
  * @param string $needle_alias
  *   The alias to filter by.
  * @param [] $aliases
  *   The aliases to filter.
  *
  * @return []
  *   The filtered aliases or all if filter not opted in.
  */
  protected function filterByAlias($needle_alias, Array $aliases){
    if (!$needle_alias){
      return $aliases;
    }
    $results = [];
    foreach($aliases as $key=>$alias){
      if ($needle_alias){
        if($needle_alias != $alias['alias']){
          continue;
        }
        $results[] = $alias;
      }
    }
    return $results;
  }

}
