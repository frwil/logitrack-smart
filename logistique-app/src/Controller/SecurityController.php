<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        // Rediriger vers la création du super admin si aucun utilisateur n'existe
        if ($userRepository->count([]) === 0) {
            return $this->redirectToRoute('app_create_super_admin');
        }

        // Si l'utilisateur est déjà connecté, rediriger vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route(path: '/create-super-admin', name: 'app_create_super_admin')]
    public function createSuperAdmin(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response
    {
        // Si des utilisateurs existent déjà, rediriger vers la page de connexion
        if ($userRepository->count([]) > 0) {
            return $this->redirectToRoute('app_login');
        }

        $user = new User();
        $errors = [];

        // Traitement du formulaire de création du super admin
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $telephone = $request->request->get('telephone');

            // Validation des données
            $user->setEmail($email);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setTelephone($telephone);

            $validationErrors = $validator->validate($user);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            if (empty($password) || strlen($password) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas';
            }

            if (empty($errors)) {
                // Création du super admin
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                
                // Définir le rôle super admin et toutes les permissions
                $user->setRoles(['ROLE_SUPER_ADMIN']);
                
                // Donner toutes les permissions au super admin
                $allPermissions = $this->getAllPermissions();
                $user->setPermissions($allPermissions);
                
                $user->setIsActive(true);
                $user->setCreatedAt(new \DateTimeImmutable());

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Super administrateur créé avec succès. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            }

            // S'il y a des erreurs, les passer à la vue
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('security/create_super_admin.html.twig', [
            'user' => $user
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut être vide - elle sera interceptée par le système de déconnexion de Symfony
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Retourne toutes les permissions possibles pour un super admin
     */
    private function getAllPermissions(): array
    {
        $modules = [
            'vehicule', 'chauffeur', 'voyage', 'document', 
            'affectation', 'reparation', 'maintenance', 'reporting'
        ];
        
        $actions = ['view', 'create', 'update', 'delete', 'print', 'export'];
        
        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[$module][$action] = true;
            }
        }
        
        return $permissions;
    }
}