<?php

// src/Security/PermissionVoter.php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use App\Entity\User;

class PermissionVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // Le voter supporte les attributs de type "module.action"
        return preg_match('/^[a-z_]+\.[a-z_]+$/', $attribute);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Super admin a tous les droits
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // Vérifier les permissions
        list($module, $action) = explode('.', $attribute);
        
        return $user->hasPermission($module, $action);
    }
}