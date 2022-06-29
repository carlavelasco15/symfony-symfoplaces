<?php   

namespace App\Security;

use App\Entity\Picture;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class PictureVoter extends Voter {

    private $security, $operaciones;

    public function __construct(Security $security) {
        $this->security = $security;
        $this->operaciones = ['create','edit','delete'];
    } 


    protected function supports(string $attribute, $subject): bool {

        if(!in_array($attribute, $this->operaciones))
            return false;

        if(!$subject instanceof Picture)
            return false;

        return true;
    }


    protected function voteOnAttribute(string $attribute, $place, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User)
            return false;

        $method = 'can' .ucfirst($attribute);

        return $this->$method($place, $user);
    }


    private function canCreate(Picture $place, User $user): bool {
        return $user->isVerified();
    }


    private function canEdit(Picture $place, User $user): bool {
        return $user === $place->getPlace()->getUser() || $this->security->isGranted('ROLE_EDITOR');
    }

    private function canDelete(Picture $place, User $user): bool {
        return $this->canEdit($place, $user);
    }
    
}