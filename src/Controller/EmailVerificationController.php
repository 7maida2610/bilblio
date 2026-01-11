<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        // Debug: Log the token being received
        error_log("Verification attempt with token: " . $token);

        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            error_log("User not found for token: " . $token);
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Update user verification status
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        // Send welcome email
        $emailService->sendWelcomeEmail($user);

        error_log("User " . $user->getEmail() . " verified successfully");

        $this->addFlash('success', 'Your email has been verified successfully! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend/verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $email = $request->query->get('email');

        if (!$email) {
            $this->addFlash('error', 'Email address is required.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'No account found with this email address.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        }

        // Generate new verification token
        $token = bin2hex(random_bytes(32));
        $user->setVerificationToken($token);
        $entityManager->flush();

        // Send verification email using EmailService
        $emailService->sendVerificationEmail($user);

        $this->addFlash('success', 'A new verification email has been sent to your email address.');

        return $this->redirectToRoute('app_login');
    }

}
