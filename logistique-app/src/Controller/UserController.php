<?php
// src/Controller/UserController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Region;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isGranted('user.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des utilisateurs.');
            return $this->redirectToRoute('app_homepage_index');
        }
        
        $selectedRegionId = $request->getSession()->get('selected_region');

        if ($selectedRegionId) {
            $users = $entityManager->getRepository(User::class)->findBy(['region' => $selectedRegionId]);
        } else {
            $users = $entityManager->getRepository(User::class)->findAll();
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('user.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un utilisateur.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vérifier les permissions supplémentaires pour les super admin
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Accès refusé. Privilèges insuffisants.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $user = new User();

                // Validation des données
                $email = trim($request->request->get('email', ''));
                if (empty($email)) {
                    $this->addFlash('error', 'L\'email est obligatoire.');
                    return $this->redirectToRoute('app_user_new');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('error', 'Email invalide.');
                    return $this->redirectToRoute('app_user_new');
                }

                // Vérifier si l'email existe déjà
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                    return $this->redirectToRoute('app_user_new');
                }

                $user->setEmail($email);
                
                $nom = trim($request->request->get('nom', ''));
                if (empty($nom)) {
                    $this->addFlash('error', 'Le nom est obligatoire.');
                    return $this->redirectToRoute('app_user_new');
                }
                $user->setNom($nom);
                
                $prenom = trim($request->request->get('prenom', ''));
                if (empty($prenom)) {
                    $this->addFlash('error', 'Le prénom est obligatoire.');
                    return $this->redirectToRoute('app_user_new');
                }
                $user->setPrenom($prenom);
                
                $user->setTelephone(trim($request->request->get('telephone', '')));

                // Gestion des rôles
                $roles = $request->request->all('roles', []);
                if (!is_array($roles)) {
                    $roles = [$roles];
                }

                // Empêcher les administrateurs normaux d'attribuer le rôle SUPER_ADMIN
                if (!$this->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $roles)) {
                    $this->addFlash('error', 'Vous n\'êtes pas autorisé à attribuer le rôle Super Admin.');
                    return $this->redirectToRoute('app_user_new');
                }

                $user->setRoles($roles);

                // Gestion de la région
                $regionId = $request->request->get('region');
                if ($regionId) {
                    $region = $entityManager->getRepository(Region::class)->find($regionId);
                    $user->setRegion($region);
                }

                if (count($roles) === 1 && in_array('ROLE_USER', $roles) && !$regionId) {
                    $this->addFlash('error', 'Un utilisateur avec uniquement le rôle User doit être associé à une région.');
                    return $this->redirectToRoute('app_user_new');
                }

                // Gestion des permissions
                $permissions = $request->request->all('permissions');
                if ($permissions && is_array($permissions)) {
                    // Nettoyer les permissions (enlever les cases non cochées)
                    $cleanedPermissions = [];
                    foreach ($permissions as $module => $actions) {
                        foreach ($actions as $action => $value) {
                            if ($value === '1') {
                                $cleanedPermissions[$module][$action] = true;
                            }
                        }
                    }
                    $user->setPermissions($cleanedPermissions);
                }

                // Hashage du mot de passe
                $plainPassword = $request->request->get('password');
                if (empty($plainPassword)) {
                    $this->addFlash('error', 'Le mot de passe est requis.');
                    return $this->redirectToRoute('app_user_new');
                }

                if (strlen($plainPassword) < 8) {
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                    return $this->redirectToRoute('app_user_new');
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    User::class,
                    $user->getId(),
                    ['new' => [
                        'email' => $email,
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'telephone' => $user->getTelephone(),
                        'roles' => $roles,
                        'region' => $regionId,
                        'permissions' => $cleanedPermissions ?? []
                    ]],
                    'success'
                );

                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de création
                $this->auditLogger->log(
                    'create',
                    User::class,
                    0,
                    ['attempted_data' => [
                        'email' => $email ?? '',
                        'nom' => $nom ?? '',
                        'prenom' => $prenom ?? '',
                        'telephone' => $request->request->get('telephone', ''),
                        'roles' => $roles ?? [],
                        'region' => $regionId ?? null
                    ], 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
                return $this->redirectToRoute('app_user_new');
            }
        }

        // GET request - afficher le formulaire
        $regions = $entityManager->getRepository(Region::class)->findAll();
        return $this->render('user/new.html.twig', [
            'regions' => $regions,
        ]);
    }

    #[Route('/profile/edit', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone()
        ];

        if ($request->isMethod('POST')) {
            try {
                $email = trim($request->request->get('email', ''));
                if (empty($email)) {
                    $this->addFlash('error', 'L\'email est obligatoire.');
                    return $this->redirectToRoute('app_user_profile');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('error', 'Email invalide.');
                    return $this->redirectToRoute('app_user_profile');
                }

                // Vérifier si l'email existe déjà pour un autre utilisateur
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                    return $this->redirectToRoute('app_user_profile');
                }

                $user->setEmail($email);
                
                $nom = trim($request->request->get('nom', ''));
                if (empty($nom)) {
                    $this->addFlash('error', 'Le nom est obligatoire.');
                    return $this->redirectToRoute('app_user_profile');
                }
                $user->setNom($nom);
                
                $prenom = trim($request->request->get('prenom', ''));
                if (empty($prenom)) {
                    $this->addFlash('error', 'Le prénom est obligatoire.');
                    return $this->redirectToRoute('app_user_profile');
                }
                $user->setPrenom($prenom);
                
                $user->setTelephone(trim($request->request->get('telephone', '')));

                $entityManager->flush();

                // Log de l'action de modification du profil
                $newData = [
                    'email' => $email,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'telephone' => $user->getTelephone()
                ];

                $changes = [];
                foreach ($oldData as $key => $oldValue) {
                    if ($oldValue != $newData[$key]) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newData[$key]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $this->auditLogger->log(
                        'update_profile',
                        User::class,
                        $user->getId(),
                        $changes,
                        'success'
                    );
                }

                $this->addFlash('success', 'Profil mis à jour avec succès.');
                return $this->redirectToRoute('app_user_profile');
            } catch (\Exception $e) {
                // Log de l'échec de modification du profil
                $this->auditLogger->log(
                    'update_profile',
                    User::class,
                    $user->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Erreur lors de la mise à jour du profil: ' . $e->getMessage());
                return $this->redirectToRoute('app_user_profile');
            }
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $oldPassword = $request->request->get('oldPassword');
                $newPassword = $request->request->get('newPassword');
                $confirmPassword = $request->request->get('confirmPassword');

                // Vérification de l'ancien mot de passe
                if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                    $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
                    return $this->redirectToRoute('app_user_change_password');
                }

                // Vérification que les nouveaux mots de passe correspondent
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_user_change_password');
                }

                // Validation de la force du mot de passe
                if (strlen($newPassword) < 8) {
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                    return $this->redirectToRoute('app_user_change_password');
                }

                // Hashage du nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                $entityManager->flush();

                // Log du changement de mot de passe
                $this->auditLogger->log(
                    'change_password',
                    User::class,
                    $user->getId(),
                    [],
                    'success'
                );

                $this->addFlash('success', 'Mot de passe changé avec succès.');
                return $this->redirectToRoute('app_user_profile');
            } catch (\Exception $e) {
                // Log de l'échec du changement de mot de passe
                $this->auditLogger->log(
                    'change_password',
                    User::class,
                    $user->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Erreur lors du changement de mot de passe: ' . $e->getMessage());
                return $this->redirectToRoute('app_user_change_password');
            }
        }

        return $this->render('user/change_password.html.twig');
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('user.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cet utilisateur.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('user.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cet utilisateur.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vérifier les permissions supplémentaires
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->isSuperAdmin()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier un super administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'region' => $user->getRegion() ? $user->getRegion()->getId() : null,
            'permissions' => $user->getPermissions(),
            'isActive' => $user->isIsActive()
        ];

        if ($request->isMethod('POST')) {
            try {
                // Mise à jour des données
                $nom = trim($request->request->get('nom', ''));
                if (empty($nom)) {
                    $this->addFlash('error', 'Le nom est obligatoire.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
                $user->setNom($nom);
                
                $prenom = trim($request->request->get('prenom', ''));
                if (empty($prenom)) {
                    $this->addFlash('error', 'Le prénom est obligatoire.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
                $user->setPrenom($prenom);
                
                $user->setTelephone(trim($request->request->get('telephone', '')));
                $user->setIsActive($request->request->get('isActive') === 'on');

                // Gestion des rôles
                $roles = $request->request->all('roles', []);
                if (!is_array($roles)) {
                    $roles = [$roles];
                }

                // Empêcher les administrateurs normaux d'attribuer le rôle SUPER_ADMIN
                if (!$this->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $roles)) {
                    $this->addFlash('error', 'Vous n\'êtes pas autorisé à attribuer le rôle Super Admin.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }

                $user->setRoles($roles);

                // Gestion de la région
                $regionId = $request->request->get('region');
                if ($regionId) {
                    $region = $entityManager->getRepository(Region::class)->find($regionId);
                    $user->setRegion($region);
                } else {
                    $user->setRegion(null);
                }

                if (count($roles) === 1 && in_array('ROLE_USER', $roles) && !$regionId) {
                    $this->addFlash('error', 'Un utilisateur avec uniquement le rôle User doit être associé à une région.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }

                // Gestion des permissions
                $permissions = $request->request->all('permissions');
                if ($permissions && is_array($permissions)) {
                    // Nettoyer les permissions (enlever les cases non cochées)
                    $cleanedPermissions = [];
                    foreach ($permissions as $module => $actions) {
                        foreach ($actions as $action => $value) {
                            if ($value === '1') {
                                $cleanedPermissions[$module][$action] = true;
                            }
                        }
                    }
                    $user->setPermissions($cleanedPermissions);
                } else {
                    $user->setPermissions([]);
                }

                $entityManager->flush();

                // Log de l'action de modification avec anciennes et nouvelles valeurs
                $newData = [
                    'email' => $user->getEmail(),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'telephone' => $user->getTelephone(),
                    'roles' => $roles,
                    'region' => $regionId,
                    'permissions' => $cleanedPermissions ?? [],
                    'isActive' => $user->isIsActive()
                ];

                $changes = [];
                foreach ($oldData as $key => $oldValue) {
                    if ($oldValue != $newData[$key]) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newData[$key]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $this->auditLogger->log(
                        'update',
                        User::class,
                        $user->getId(),
                        $changes,
                        'success'
                    );
                }

                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de modification
                $this->auditLogger->log(
                    'update',
                    User::class,
                    $user->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Erreur lors de la modification de l\'utilisateur: ' . $e->getMessage());
                return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
            }
        }

        // GET request - afficher le formulaire
        $regions = $entityManager->getRepository(Region::class)->findAll();
        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'regions' => $regions,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('user.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cet utilisateur.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vérifier les permissions supplémentaires
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->isSuperAdmin()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer un super administrateur.');
            return $this->redirectToRoute('app_user_index');
        }

        // Empêcher un utilisateur de se supprimer lui-même
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                // Log de l'action de suppression avec les données de l'entité
                $this->auditLogger->log(
                    'delete',
                    User::class,
                    $user->getId(),
                    ['old' => [
                        'email' => $user->getEmail(),
                        'nom' => $user->getNom(),
                        'prenom' => $user->getPrenom(),
                        'telephone' => $user->getTelephone(),
                        'roles' => $user->getRoles(),
                        'region' => $user->getRegion() ? $user->getRegion()->getId() : null,
                        'permissions' => $user->getPermissions(),
                        'isActive' => $user->isIsActive()
                    ]],
                    'success'
                );

                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'échec de suppression
                $this->auditLogger->log(
                    'delete',
                    User::class,
                    $user->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_user_index');
    }
}