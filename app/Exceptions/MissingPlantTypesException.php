<?php

namespace App\Exceptions;

use RuntimeException;

class MissingPlantTypesException extends RuntimeException
{
    public static function forSeed(): self
    {
        return new self('Plant types are missing. Run PlantTypeSeeder first.');
    }
}
