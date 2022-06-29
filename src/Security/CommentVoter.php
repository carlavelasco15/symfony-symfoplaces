<?php   

namespace App\Security;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class CommentVoter extends Voter {

    private $security, $operaciones;

    public function __construct(Security $security) {
        $this->security = $security;
        $this->operaciones = ['create','edit','delete'];
    } 


    protected function supports(string $attribute, $subject): bool {

        if(!in_array($attribute, $this->operaciones))
            return false;

        if(!$subject instanceof Comment)
            return false;

        return true;
    }


    protected function voteOnAttribute(string $attribute, $comment, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User)
            return false;

        $method = 'can' .ucfirst($attribute);

        return $this->$method($comment, $user);
    }


    private function canCreate(Comment $comment, User $user): bool {
        return $user->isVerified();
    }


    private function canEdit(Comment $comment, User $user): bool {
        return $user === $comment->getUser() || $this->security->isGranted('ROLE_EDITOR');
    }

    private function canDelete(Comment $comment, User $user): bool {
        return $this->canEdit($comment, $user);
    }
    
}