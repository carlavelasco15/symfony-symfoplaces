<?php
namespace App\Form\Custom;

use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DataListType extends AbstractType {

    public function getParent() {
        return EntityType::class;
    }

}