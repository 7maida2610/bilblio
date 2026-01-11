<?php

namespace App\DataFixtures;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Livre;
use Doctrine\Persistence\ObjectManager;

class LoanFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get the regular user and some books
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);
        $books = $manager->getRepository(Livre::class)->findAll();

        if (!$user || count($books) < 3) {
            return; // Skip if dependencies not met
        }

        // Create loans with different statuses and dates
        $loansData = [
            [
                'book' => $books[0], // Les Misérables
                'status' => Loan::STATUS_ACTIVE,
                'requestedAt' => new \DateTimeImmutable('-10 days'),
                'approvedAt' => new \DateTimeImmutable('-9 days'),
                'loanStartDate' => new \DateTimeImmutable('-9 days'),
                'dueDate' => new \DateTimeImmutable('+6 days'), // Due in 6 days
            ],
            [
                'book' => $books[1], // L'Étranger
                'status' => Loan::STATUS_RETURNED,
                'requestedAt' => new \DateTimeImmutable('-20 days'),
                'approvedAt' => new \DateTimeImmutable('-19 days'),
                'loanStartDate' => new \DateTimeImmutable('-19 days'),
                'dueDate' => new \DateTimeImmutable('-5 days'),
                'returnedAt' => new \DateTimeImmutable('-3 days'),
            ],
            [
                'book' => $books[2], // Le Deuxième Sexe
                'status' => Loan::STATUS_REQUESTED,
                'requestedAt' => new \DateTimeImmutable('-2 days'),
            ],
            [
                'book' => $books[3], // À la recherche du temps perdu
                'status' => Loan::STATUS_OVERDUE,
                'requestedAt' => new \DateTimeImmutable('-25 days'),
                'approvedAt' => new \DateTimeImmutable('-24 days'),
                'loanStartDate' => new \DateTimeImmutable('-24 days'),
                'dueDate' => new \DateTimeImmutable('-2 days'), // Overdue
            ],
            [
                'book' => $books[4], // Madame Bovary
                'status' => Loan::STATUS_APPROVED,
                'requestedAt' => new \DateTimeImmutable('-5 days'),
                'approvedAt' => new \DateTimeImmutable('-4 days'),
                'loanStartDate' => new \DateTimeImmutable('-4 days'),
                'dueDate' => new \DateTimeImmutable('+11 days'),
            ],
        ];

        foreach ($loansData as $data) {
            $loan = new Loan();
            $loan->setUser($user);
            $loan->setLivre($data['book']);
            $loan->setStatus($data['status']);
            $loan->setRequestedAt($data['requestedAt']);

            if (isset($data['approvedAt'])) {
                $loan->setApprovedAt($data['approvedAt']);
            }
            if (isset($data['loanStartDate'])) {
                $loan->setLoanStartDate($data['loanStartDate']);
            }
            if (isset($data['dueDate'])) {
                $loan->setDueDate($data['dueDate']);
            }
            if (isset($data['returnedAt'])) {
                $loan->setReturnedAt($data['returnedAt']);
            }

            $manager->persist($loan);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}