<?php

namespace Zirvu\Api\Traits;

trait Relation
{
	
    public function checkDelete()
    {
        $relations = $this->getRelations();
        foreach ($relations as $relationName => $relation) {
            if ($relation instanceof \Illuminate\Support\Collection && $relation->count() > 0) {
                return false;
            }
        }
        return true;
    }

}