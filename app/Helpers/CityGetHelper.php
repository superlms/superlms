<?php

namespace App\Helpers;

class CityGetHelper
{
    private $citiesData;

    public function __construct()
    {
        $jsonPath = public_path('cities/cities.json');
        $jsonContent = file_get_contents($jsonPath);
        $this->citiesData = json_decode($jsonContent, true);
    }

    /**
     * Get all unique states from the cities data
     * @return array of state names
     */
    public function getState()
    {
        $states = [];
        
        foreach ($this->citiesData as $city) {
            if (!in_array($city['state'], $states)) {
                $states[] = $city['state'];
            }
        }

        return $states;
    }

    /**
     * Get all cities for a specific state name
     * @param string $stateName
     * @return array of cities with names
     */
    public function cityGetByState($stateName)
    {
        $filtered = array_filter($this->citiesData, function($city) use ($stateName) {
            return $city['state'] === $stateName;
        });

        return array_values(array_map(fn($c) => $c['name'], $filtered));
    }
}