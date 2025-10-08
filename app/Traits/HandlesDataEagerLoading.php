<?php

namespace App\Traits;

trait HandlesDataEagerLoading
{
    /**
     * Get the relationships that should be eager loaded for the Data object
     * This is meant to be overridden in the implementing class
     * 
     * @return array
     */
    public function getDefaultEagerLoads(): array
    {
        return [];
    }

    /**
     * Add eager loading to a query builder based on the Data object's needs
     * 
     * @param mixed $query The query builder instance
     * @param array $additionalRelationships Additional relationships to load
     * @return mixed
     */
    public function applyEagerLoads($query, array $additionalRelationships = [])
    {
        $relationships = array_merge($this->getDefaultEagerLoads(), $additionalRelationships);
        
        if (!empty($relationships)) {
            $query = $query->with($relationships);
        }
        
        return $query;
    }
}