<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('', name: 'app_messages_index', methods: ['GET'])]
    public function index(Request $request, MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        $tab = $request->query->get('tab', 'inbox');
        $page = max(1, $request->query->getInt('page', 1));
        
        if ($tab === 'sent') {
            $messages = $messageRepository->findSentByUser($user, $page);
            $total = $messageRepository->countSentByUser($user);
        } else {
            $messages = $messageRepository->findInboxForUser($user, $page);
            $total = $messageRepository->countInboxForUser($user);
        }
        
        $unreadCount = $messageRepository->countUnreadForUser($user);
        
        return $this->render('message/index.html.twig', [
            'messages' => $messages,
            'tab' => $tab,
            'page' => $page,
            'totalPages' => ceil($total / 20),
            'total' => $total,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/new', name: 'app_messages_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            $recipientId = $request->request->get('recipient');
            $subject = $request->request->get('subject');
            $content = $request->request->get('content');
            
            $recipient = $userRepository->find($recipientId);
            
            if (!$recipient) {
                $this->addFlash('error', 'Destinataire non trouvé.');
                return $this->redirectToRoute('app_messages_new');
            }
            
            if (empty($subject) || empty($content)) {
                $this->addFlash('error', 'Veuillez remplir tous les champs.');
                return $this->redirectToRoute('app_messages_new');
            }
            
            $message = new Message();
            $message->setSender($user);
            $message->setRecipient($recipient);
            $message->setSubject($subject);
            $message->setContent($content);
            
            $em->persist($message);
            $em->flush();
            
            $this->addFlash('success', 'Message envoyé avec succès!');
            return $this->redirectToRoute('app_messages_index', ['tab' => 'sent']);
        }
        
        // Get users to send message to (exclude current user)
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.id != :currentUser')
            ->andWhere('u.isActive = true')
            ->setParameter('currentUser', $user->getId())
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('message/new.html.twig', [
            'users' => $users,
            'replyTo' => $request->query->get('replyTo'),
        ]);
    }

    #[Route('/{id}', name: 'app_messages_show', methods: ['GET'])]
    public function show(Message $message, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Check access
        if ($message->getSender() !== $user && $message->getRecipient() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce message.');
        }
        
        // Mark as read if recipient
        if ($message->getRecipient() === $user && !$message->isRead()) {
            $message->setIsRead(true);
            $em->flush();
        }
        
        return $this->render('message/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_messages_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if ($message->getSender() !== $user && $message->getRecipient() !== $user) {
            throw $this->createAccessDeniedException();
        }
        
        if ($this->isCsrfTokenValid('delete' . $message->getId(), $request->request->get('_token'))) {
            $em->remove($message);
            $em->flush();
            $this->addFlash('success', 'Message supprimé.');
        }
        
        return $this->redirectToRoute('app_messages_index');
    }

    #[Route('/api/unread-count', name: 'app_messages_unread_count', methods: ['GET'])]
    public function unreadCount(MessageRepository $messageRepository): JsonResponse
    {
        $user = $this->getUser();
        $count = $messageRepository->countUnreadForUser($user);
        
        return new JsonResponse(['count' => $count]);
    }

    #[Route('/api/recent', name: 'app_messages_recent', methods: ['GET'])]
    public function recent(MessageRepository $messageRepository): JsonResponse
    {
        $user = $this->getUser();
        $messages = $messageRepository->findRecentForUser($user, 4);
        
        $data = [];
        foreach ($messages as $message) {
            $sender = $message->getSender();
            $data[] = [
                'id' => $message->getId(),
                'subject' => $message->getSubject(),
                'content' => mb_substr($message->getContent(), 0, 80) . '...',
                'sender' => $sender->getUsername(),
                'senderImage' => $sender->getProfilePicture() 
                    ? '/uploads/profile_pictures/' . $sender->getProfilePicture()
                    : '/img/undraw_profile.svg',
                'timeAgo' => $message->getTimeAgo(),
                'isRead' => $message->isRead(),
            ];
        }
        
        return new JsonResponse($data);
    }
}
