<?php
// Quick test data loader

$conn = new PDO('mysql:host=127.0.0.1;dbname=bibliodb', 'root', '');

try {
    // Create admin user
    $password = password_hash('password', PASSWORD_BCRYPT);
    $conn->exec("INSERT INTO user (email, username, roles, password, is_active, is_verified, created_at) VALUES ('admin@example.com', 'admin', '[\"ROLE_ADMIN\", \"ROLE_USER\"]', '$password', 1, 1, NOW())");
    echo "✓ Admin user created\n";
    
    // Create test authors
    $conn->exec("INSERT INTO auteur (prenom, nom, biographie, created_at, updated_at) VALUES ('Victor', 'Hugo', 'French writer', NOW(), NOW())");
    $conn->exec("INSERT INTO auteur (prenom, nom, biographie, created_at, updated_at) VALUES ('Albert', 'Camus', 'French writer', NOW(), NOW())");
    echo "✓ Authors created\n";
    
    // Create test categories
    $conn->exec("INSERT INTO categorie (designation, description, created_at, updated_at) VALUES ('Fiction', 'Fiction books', NOW(), NOW())");
    $conn->exec("INSERT INTO categorie (designation, description, created_at, updated_at) VALUES ('Philosophy', 'Philosophy books', NOW(), NOW())");
    echo "✓ Categories created\n";
    
    // Create test publishers
    $conn->exec("INSERT INTO editeur (nom_editeur, adresse, telephone, pays, created_at, updated_at) VALUES ('Publisher 1', '123 Street', '1234567890', 'France', NOW(), NOW())");
    echo "✓ Publishers created\n";
    
    // Create test books
    $conn->exec("INSERT INTO livre (titre, auteur_id, editeur_id, prix, image, isbn, nb_exemplaires, date_edition, nb_pages, created_at, updated_at) 
                VALUES ('Les Misérables', 1, 1, 15.99, 'default.jpg', '9780451524935', 10, '2020-01-01', 500, NOW(), NOW())");
    $conn->exec("INSERT INTO livre (titre, auteur_id, editeur_id, prix, image, isbn, nb_exemplaires, date_edition, nb_pages, created_at, updated_at) 
                VALUES ('The Stranger', 2, 1, 12.99, 'default.jpg', '9780679733768', 8, '2020-01-01', 400, NOW(), NOW())");
    echo "✓ Books created\n";
    
    // Create a test cart with items for the admin user
    $conn->exec("INSERT INTO carts (user_id, created_at, updated_at) VALUES (1, NOW(), NOW())");
    $conn->exec("INSERT INTO cart_items (carte_id, livre_id, quantity, unit_price, added_at) VALUES (1, 1, 2, 15.99, NOW())");
    $conn->exec("INSERT INTO cart_items (carte_id, livre_id, quantity, unit_price, added_at) VALUES (1, 2, 1, 12.99, NOW())");
    echo "✓ Test cart with items created\n";
    
    echo "\n✅ All test data loaded successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
