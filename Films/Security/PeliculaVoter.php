<?php   

namespace App\Security;

use App\Entity\Pelicula;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class PeliculaVoter extends Voter {

    private $security, $operaciones;

    public function __construct(Security $security) {
        $this->security = $security;
        $this->operaciones = ['create','edit','delete'];
    } 


    protected function supports(string $attribute, $subject): bool {

        if(!in_array($attribute, $this->operaciones))
            return false;

        if(!$subject instanceof Pelicula)
            return false;

        return true;
    }


    protected function voteOnAttribute(string $attribute, $pelicula, TokenInterface $token)
    {
        $user = $token->getUser();

        if(!$user instanceof User)
            return false;

        $method = 'can' .ucfirst($attribute);

        return $this->$method($pelicula, $user);
    }


    private function canCreate(Pelicula $pelicula, User $user): bool {
        return true;
    }


    private function canEdit(Pelicula $pelicula, User $user): bool {
        return $user === $pelicula->getUser() || $this->security->isGranted('ROLE_EDITOR');
    }

    private function canDelete(Pelicula $pelicula, User $user): bool {
        return $this->canEdit($pelicula, $user);
    }
    
}