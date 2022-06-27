<?php
namespace App\Service;

class EntityFakerService {

    public function getMock(string $className) {
        $fullName = '\\App\\Entity\\'.$className;
        return new $fullName();
    }
}
    