<?php   

namespace App\Security;

use App\Entity\Actor;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ActorVoter extends Voter {

    private $security, $operaciones;

    public function __construct(Security $security) {
        $this->security = $security;
        $this->operaciones = ['create','edit','delete'];
    } 


    protected function supports(string $attribute, $subject): bool {

        if(!in_array($attribute, $this->operaciones))
            return false;

        if(!$subject instanceof Actor)
            return false;

        return true;
    }


    protected function voteOnAttribute(string $attribute, $actor, TokenInterface $token)
    {
        $user = $token->getUser();

        if(!$user instanceof User)
            return false;

        $method = 'can' .ucfirst($attribute);

        return $this->$method($actor, $user);
    }


    private function canCreate(Actor $actor, User $user): bool {
        return true;
    }


    private function canEdit(Actor $actor, User $user): bool {
        return $user === $actor->getUser() || $this->security->isGranted('ROLE_EDITOR');
    }

    private function canDelete(Actor $actor, User $user): bool {
        return $this->canEdit($actor, $user);
    }
    
}