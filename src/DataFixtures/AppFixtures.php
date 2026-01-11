<?php

namespace App\DataFixtures;

use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Editeur;
use App\Entity\Livre;
use App\Entity\ReadingGoal;
use App\Entity\ReadingProgress;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends BaseFixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer des auteurs réels
        $auteurs = [];
        $auteursData = [
            ['prenom' => 'Victor', 'nom' => 'Hugo', 'biographie' => 'Écrivain, dramaturge, poète, homme politique et intellectuel français, né le 26 février 1802 à Besançon et mort le 22 mai 1885 à Paris.'],
            ['prenom' => 'Albert', 'nom' => 'Camus', 'biographie' => 'Écrivain, philosophe, dramaturge et journaliste français, né le 7 novembre 1913 à Mondovi (Algérie) et mort le 4 janvier 1960 près de Sens (Yonne). Prix Nobel de littérature en 1957.'],
            ['prenom' => 'Simone', 'nom' => 'de Beauvoir', 'biographie' => 'Philosophe, romancière, épistolière, mémorialiste et essayiste française, née le 9 janvier 1908 à Paris et morte le 14 avril 1986 dans la même ville.'],
            ['prenom' => 'Marcel', 'nom' => 'Proust', 'biographie' => 'Écrivain français, né le 10 juillet 1871 à Auteuil (alors commune indépendante) et mort le 18 novembre 1922 à Paris.'],
            ['prenom' => 'Gustave', 'nom' => 'Flaubert', 'biographie' => 'Écrivain français, né le 12 décembre 1821 à Rouen et mort le 8 mai 1880 à Croisset.'],
            ['prenom' => 'Émile', 'nom' => 'Zola', 'biographie' => 'Écrivain et journaliste français, né le 2 avril 1840 à Paris et mort le 29 septembre 1902 dans la même ville.'],
            ['prenom' => 'Honoré', 'nom' => 'de Balzac', 'biographie' => 'Écrivain français, né le 20 mai 1799 à Tours et mort le 18 août 1850 à Paris.'],
            ['prenom' => 'Stendhal', 'nom' => '', 'biographie' => 'Écrivain français, né le 23 janvier 1783 à Grenoble et mort le 23 mars 1842 à Paris.'],
            ['prenom' => 'François', 'nom' => 'Mauriac', 'biographie' => 'Écrivain français, né le 11 octobre 1885 à Bordeaux et mort le 1er septembre 1970 à Paris. Prix Nobel de littérature en 1952.'],
            ['prenom' => 'André', 'nom' => 'Malraux', 'biographie' => 'Écrivain, homme politique et ministre français, né le 3 novembre 1901 à Paris et mort le 23 novembre 1976 dans la même ville.'],
        ];

        foreach ($auteursData as $data) {
            $auteur = new Auteur();
            $auteur->setPrenom($data['prenom']);
            $auteur->setNom($data['nom']);
            $auteur->setBiographie($data['biographie']);
            $auteur->setCreatedAt(new \DateTimeImmutable());
            $auteur->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($auteur);
            $auteurs[] = $auteur;
        }

        // Créer des catégories réelles
        $categories = [];
        $categoriesData = [
            ['designation' => 'Roman', 'description' => 'Œuvres de fiction narrative en prose'],
            ['designation' => 'Philosophie', 'description' => 'Ouvrages traitant de questions philosophiques et existentielles'],
            ['designation' => 'Biographie', 'description' => 'Récits de vie d\'individus célèbres'],
            ['designation' => 'Poésie', 'description' => 'Recueils de poèmes et œuvres poétiques'],
            ['designation' => 'Théâtre', 'description' => 'Pièces de théâtre et œuvres dramatiques'],
            ['designation' => 'Essai', 'description' => 'Ouvrages d\'analyse et de réflexion'],
            ['designation' => 'Histoire', 'description' => 'Ouvrages historiques et récits du passé'],
            ['designation' => 'Science', 'description' => 'Ouvrages scientifiques et techniques'],
        ];

        foreach ($categoriesData as $data) {
            $categorie = new Categorie();
            $categorie->setDesignation($data['designation']);
            $categorie->setDescription($data['description']);
            $categorie->setCreatedAt(new \DateTimeImmutable());
            $categorie->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($categorie);
            $categories[] = $categorie;
        }

        // Créer des éditeurs réels
        $editeurs = [];
        $editeursData = [
            ['nomEditeur' => 'Gallimard', 'pays' => 'France', 'adresse' => '5 rue Gaston-Gallimard, 75328 Paris Cedex 07', 'telephone' => '01 49 54 42 00'],
            ['nomEditeur' => 'Le Seuil', 'pays' => 'France', 'adresse' => '27 rue Jacob, 75006 Paris', 'telephone' => '01 40 43 20 00'],
            ['nomEditeur' => 'Flammarion', 'pays' => 'France', 'adresse' => '87 quai Panhard-et-Levassor, 75647 Paris Cedex 13', 'telephone' => '01 40 51 30 00'],
            ['nomEditeur' => 'Albin Michel', 'pays' => 'France', 'adresse' => '22 rue Huyghens, 75014 Paris', 'telephone' => '01 42 79 10 00'],
            ['nomEditeur' => 'Éditions du Seuil', 'pays' => 'France', 'adresse' => '27 rue Jacob, 75006 Paris', 'telephone' => '01 40 43 20 00'],
            ['nomEditeur' => 'Grasset', 'pays' => 'France', 'adresse' => '61 rue des Saints-Pères, 75006 Paris', 'telephone' => '01 44 39 23 00'],
        ];

        foreach ($editeursData as $data) {
            $editeur = new Editeur();
            $editeur->setNomEditeur($data['nomEditeur']);
            $editeur->setPays($data['pays']);
            $editeur->setAdresse($data['adresse']);
            $editeur->setTelephone($data['telephone']);
            $editeur->setCreatedAt(new \DateTimeImmutable());
            $editeur->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($editeur);
            $editeurs[] = $editeur;
        }

        // Créer des livres réels avec données authentiques
        $livresData = [
            [
                'titre' => 'Les Misérables',
                'nbPages' => 1488,
                'dateEdition' => new \DateTime('1862-01-01'),
                'nbExemplaires' => 45,
                'prix' => 29.90,
                'isbn' => '9782070418945',
                'auteur' => 0, // Victor Hugo
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'L\'Étranger',
                'nbPages' => 123,
                'dateEdition' => new \DateTime('1942-01-01'),
                'nbExemplaires' => 67,
                'prix' => 8.50,
                'isbn' => '9782070360022',
                'auteur' => 1, // Albert Camus
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Le Deuxième Sexe',
                'nbPages' => 587,
                'dateEdition' => new \DateTime('1949-01-01'),
                'nbExemplaires' => 23,
                'prix' => 24.50,
                'isbn' => '9782070322853',
                'auteur' => 2, // Simone de Beauvoir
                'categorie' => 1, // Philosophie
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'À la recherche du temps perdu',
                'nbPages' => 4211,
                'dateEdition' => new \DateTime('1913-01-01'),
                'nbExemplaires' => 12,
                'prix' => 89.90,
                'isbn' => '9782070414169',
                'auteur' => 3, // Marcel Proust
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Madame Bovary',
                'nbPages' => 528,
                'dateEdition' => new \DateTime('1857-01-01'),
                'nbExemplaires' => 34,
                'prix' => 12.90,
                'isbn' => '9782070419188',
                'auteur' => 4, // Gustave Flaubert
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Germinal',
                'nbPages' => 592,
                'dateEdition' => new \DateTime('1885-01-01'),
                'nbExemplaires' => 28,
                'prix' => 15.50,
                'isbn' => '9782070414046',
                'auteur' => 5, // Émile Zola
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Le Père Goriot',
                'nbPages' => 352,
                'dateEdition' => new \DateTime('1835-01-01'),
                'nbExemplaires' => 41,
                'prix' => 9.90,
                'isbn' => '9782070414047',
                'auteur' => 6, // Honoré de Balzac
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Le Rouge et le Noir',
                'nbPages' => 640,
                'dateEdition' => new \DateTime('1830-01-01'),
                'nbExemplaires' => 19,
                'prix' => 14.50,
                'isbn' => '9782070419189',
                'auteur' => 7, // Stendhal
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Thérèse Desqueyroux',
                'nbPages' => 192,
                'dateEdition' => new \DateTime('1927-01-01'),
                'nbExemplaires' => 15,
                'prix' => 11.90,
                'isbn' => '9782070419190',
                'auteur' => 8, // François Mauriac
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'La Condition humaine',
                'nbPages' => 432,
                'dateEdition' => new \DateTime('1933-01-01'),
                'nbExemplaires' => 22,
                'prix' => 16.50,
                'isbn' => '9782070419191',
                'auteur' => 9, // André Malraux
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Les Fleurs du mal',
                'nbPages' => 248,
                'dateEdition' => new \DateTime('1857-01-01'),
                'nbExemplaires' => 8,
                'prix' => 7.90,
                'isbn' => '9782070419192',
                'auteur' => 0, // Victor Hugo (représentant la poésie)
                'categorie' => 3, // Poésie
                'editeur' => 0, // Gallimard
            ],
            [
                'titre' => 'Du côté de chez Swann',
                'nbPages' => 576,
                'dateEdition' => new \DateTime('1913-01-01'),
                'nbExemplaires' => 31,
                'prix' => 18.90,
                'isbn' => '9782070419193',
                'auteur' => 3, // Marcel Proust
                'categorie' => 0, // Roman
                'editeur' => 0, // Gallimard
            ],
        ];

        $livres = [];
        foreach ($livresData as $data) {
            $livre = new Livre();
            $livre->setTitre($data['titre']);
            $livre->setNbPages($data['nbPages']);
            $livre->setDateEdition($data['dateEdition']);
            // Set stock for sale and loan separately
            $stockVente = (int) floor($data['nbExemplaires'] / 2);
            $stockEmprunt = (int) ceil($data['nbExemplaires'] / 2);
            $livre->setStockVente($stockVente);
            $livre->setStockEmprunt($stockEmprunt);
            $livre->setNbExemplaires($data['nbExemplaires']);
            $livre->setPrix($data['prix']);
            $livre->setIsbn($data['isbn']);
            $livre->setAuteur($auteurs[$data['auteur']]);
            $livre->setCategorie($categories[$data['categorie']]);
            $livre->setEditeur($editeurs[$data['editeur']]);
            $livre->setCreatedAt(new \DateTimeImmutable());
            $livre->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($livre);
            $livres[] = $livre;
        }

        // Create users
        $usersData = [
            [
                'email' => 'admin@example.com',
                'username' => 'admin',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin123',
                'isActive' => true,
            ],
            [
                'email' => 'moderator@example.com',
                'username' => 'moderator',
                'roles' => ['ROLE_MODERATOR'],
                'password' => 'mod123',
                'isActive' => true,
            ],
            [
                'email' => 'user@example.com',
                'username' => 'user',
                'roles' => ['ROLE_USER'],
                'password' => 'user123',
                'isActive' => true,
            ],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles($data['roles']);
            $user->setIsActive($data['isActive']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $users[] = $user;
        }

        // Create sample user data for the regular user
        $regularUser = $users[2]; // user@example.com

        // Add some books to owned books
        $regularUser->addOwnedBook($livres[0]); // Les Misérables
        $regularUser->addOwnedBook($livres[1]); // L'Étranger

        // Add some books to wishlist
        $regularUser->addToWishlist($livres[2]); // Le Deuxième Sexe
        $regularUser->addToWishlist($livres[3]); // À la recherche du temps perdu

        // Add favorite authors
        $regularUser->addFavoriteAuthor($auteurs[0]); // Victor Hugo
        $regularUser->addFavoriteAuthor($auteurs[1]); // Albert Camus

        // Create reading progress
        $progress1 = new ReadingProgress();
        $progress1->setUser($regularUser);
        $progress1->setLivre($livres[0]);
        $progress1->setProgressPercentage(75);
        $progress1->setLastReadAt(new \DateTime('-2 days'));
        $manager->persist($progress1);

        $progress2 = new ReadingProgress();
        $progress2->setUser($regularUser);
        $progress2->setLivre($livres[1]);
        $progress2->setProgressPercentage(100);
        $progress2->setLastReadAt(new \DateTime('-1 day'));
        $manager->persist($progress2);

        // Create reading goals
        $goal1 = new ReadingGoal();
        $goal1->setUser($regularUser);
        $goal1->setGoalType('books_year');
        $goal1->setTargetValue(20);
        $goal1->setCurrentValue(12);
        $goal1->setStartDate(new \DateTime('2025-01-01'));
        $goal1->setEndDate(new \DateTime('2025-12-31'));
        $manager->persist($goal1);

        $goal2 = new ReadingGoal();
        $goal2->setUser($regularUser);
        $goal2->setGoalType('pages_month');
        $goal2->setTargetValue(1000);
        $goal2->setCurrentValue(800);
        $goal2->setStartDate(new \DateTime('2025-11-01'));
        $goal2->setEndDate(new \DateTime('2025-11-30'));
        $manager->persist($goal2);

        // Create a review
        $review = new Review();
        $review->setUser($regularUser);
        $review->setLivre($livres[1]);
        $review->setRating(5);
        $review->setComment('An excellent existentialist novel that explores the absurdity of life.');
        $manager->persist($review);

        $manager->flush();
    }
}
