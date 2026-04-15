<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Page de connexion — gérée par form_login de Symfony Security.
     */
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_compte_dashboard');
        }

        return $this->render('security/connexion.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Déconnexion — interceptée automatiquement par Symfony Security.
     */
    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Ce code ne doit jamais être atteint (intercepté par le firewall).');
    }

    /**
     * Page d'inscription — création de compte client.
     */
    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_compte_dashboard');
        }

        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data = [
                'username' => trim($request->request->get('username', '')),
                'nom'      => trim($request->request->get('nom', '')),
                'prenom'   => trim($request->request->get('prenom', '')),
                'email'    => trim($request->request->get('email', '')),
                'telephone'=> trim($request->request->get('telephone', '')),
                'password' => $request->request->get('password', ''),
                'confirm'  => $request->request->get('confirm', ''),
            ];

            // Validation
            if (empty($data['username'])) {
                $errors['username'] = 'Le nom d\'utilisateur est requis.';
            } elseif (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $data['username'])) {
                $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, _ et - (3-50 caractères).';
            }
            if (empty($data['nom']))    $errors['nom']    = 'Le nom est requis.';
            if (empty($data['prenom'])) $errors['prenom'] = 'Le prénom est requis.';
            if (empty($data['email']))  $errors['email']  = 'L\'email est requis.';
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalide.';
            }
            if (strlen($data['password']) < 6) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            if ($data['password'] !== $data['confirm']) {
                $errors['confirm'] = 'Les mots de passe ne correspondent pas.';
            }

            // Vérifier unicité username et email
            if (empty($errors)) {
                if ($em->getRepository(User::class)->findOneBy(['username' => $data['username']])) {
                    $errors['username'] = 'Ce nom d\'utilisateur est déjà pris.';
                }
                if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
                    $errors['email'] = 'Cet email est déjà utilisé.';
                }
            }

            if (empty($errors)) {
                $user = new User();
                $user->setUsername($data['username'])
                     ->setNom($data['nom'])
                     ->setPrenom($data['prenom'])
                     ->setEmail($data['email'])
                     ->setTelephone($data['telephone'] ?: null)
                     ->setPassword($hasher->hashPassword($user, $data['password']));

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès ! Connectez-vous pour accéder à votre espace.');
                return $this->redirectToRoute('app_connexion');
            }
        }

        return $this->render('security/inscription.html.twig', [
            'errors' => $errors,
            'data'   => $data,
        ]);
    }
}
