# 📚 Biblio - Complete Library Management System

A comprehensive, production-ready library management system built with **Symfony 7** and **PHP 8.2+**. Features dual admin interfaces (EasyAdmin 4 + Custom SB Admin 2), complete loan & reservation workflow, e-commerce with Stripe payments, internal messaging, book reviews, reading progress tracking, and real-time analytics.

![Symfony](https://img.shields.io/badge/Symfony-7.x-black?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4.x-7952B3?style=for-the-badge&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---

## 📋 Table of Contents

- [Features](#-features)
- [Recent Updates](#-recent-updates)
- [System Architecture](#-system-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage Guide](#-usage-guide)
- [API Endpoints](#-api-endpoints)
- [Database Schema](#-database-schema)
- [Project Structure](#-project-structure)
- [Technologies](#-technologies-used)
- [Security](#-security-features)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### 📚 Library Management (Core)
| Feature | Description |
|---------|-------------|
| **Books (Livres)** | Full CRUD with title, ISBN, pages, price, stock, cover images, PDF uploads |
| **Authors (Auteurs)** | Author profiles with biography and book relationships |
| **Categories** | Book categorization with descriptions |
| **Publishers (Editeurs)** | Publisher management with contact info |
| **Dual Stock System** | Separate `stockVente` (for sales) and `stockEmprunt` (for loans) with auto-calculated `nbExemplaires` |
| **Borrowable Flag** | `isBorrowable` to control which books can be loaned |

### 📖 Loan System
| Feature | Description |
|---------|-------------|
| **Loan Workflow** | `requested` → `approved` → `active` → `returned` (or `overdue`/`cancelled`) |
| **Due Date Tracking** | Automatic 14-day loan period with overdue detection |
| **Admin Approval** | Loans require admin approval before activation |
| **Return Processing** | Mark books as returned, auto-update `stockEmprunt` |
| **Loan History** | Complete audit trail with timestamps |
| **Availability Calendar** | FullCalendar.js view showing loan periods and expected return dates |
| **Loan Reminders** | Console command to send overdue loan reminders |

### 📅 Reservation System
| Feature | Description |
|---------|-------------|
| **Queue Position** | Users queued by `position` when book unavailable |
| **Notifications** | `notifiedAt` tracks when user was alerted |
| **Active Status** | `isActive` flag to manage reservation lifecycle |
| **Auto-Conversion** | Reservations can convert to loans when book available |
| **Availability Calendar** | Interactive calendar showing when book will be available |

### 🛒 E-Commerce
| Feature | Description |
|---------|-------------|
| **Shopping Cart** | Persistent cart with `CartItem` quantities, validates against `stockVente` |
| **Orders** | Full order lifecycle: `pending` → `paid` → `processing` → `shipped` → `delivered` |
| **Stripe Integration** | Secure card payments with `stripePaymentIntentId` |
| **Multiple Payment Methods** | Stripe, bank transfer, cash on delivery, manual |
| **Order Numbers** | Auto-generated format: `ORD-YYYYMMDD-XXXXXX-XXXX` |
| **Addresses** | Separate billing and shipping addresses (JSON) |
| **Stock Validation** | Automatic `stockVente` check before purchase |

### ⭐ Reviews & Ratings
| Feature | Description |
|---------|-------------|
| **Star Ratings** | 1-5 star rating system |
| **Comments** | Text reviews with optional images |
| **Verified Badge** | Mark reviews from verified purchasers |
| **Helpful Votes** | Community voting on review usefulness |

### 📊 Reading Progress
| Feature | Description |
|---------|-------------|
| **Progress Tracking** | `progressPercentage` and `currentPage` per book |
| **Bookmarks** | JSON array of saved pages with notes |
| **Completion Status** | Auto-marks complete at 100% |
| **Last Read** | Tracks `lastReadAt` for activity feeds |

### 🎯 Reading Goals
| Feature | Description |
|---------|-------------|
| **Goal Types** | `books_year`, `pages_month`, custom periods |
| **Progress Tracking** | `currentValue` vs `targetValue` |
| **Date Ranges** | `startDate` and `endDate` for goal periods |
| **Progress Percentage** | Auto-calculated completion percentage |

### 💬 Message Center
| Feature | Description |
|---------|-------------|
| **Internal Messaging** | User-to-user communication |
| **Read Status** | `isRead` flag with `readAt` timestamp |
| **Real-time Count** | AJAX-powered unread count in navbar |
| **Time Ago** | French locale time display (`2h`, `3j`, etc.) |

### 🎨 Banner System
| Feature | Description |
|---------|-------------|
| **Banner Types** | `promotion`, `announcement`, `warning`, `info` |
| **Positions** | `top`, `bottom`, `sidebar`, `popup` |
| **Scheduling** | `startDate`/`endDate` with auto status updates |
| **Target Audience** | Role-based visibility (guest, ROLE_USER, ROLE_ADMIN) |
| **Custom Styling** | JSON-based colors and CSS customization |
| **Dismissible** | User preferences tracked per banner |

### 👥 User Management
| Feature | Description |
|---------|-------------|
| **Registration** | Email verification with token |
| **Password Reset** | Secure token-based reset flow |
| **Profiles** | First/last name, phone, avatar, addresses |
| **Roles** | `ROLE_USER`, `ROLE_ADMIN` with role hierarchy |
| **Wishlist** | ManyToMany book wishlist |
| **Owned Books** | Track purchased/owned books |
| **Favorite Authors** | Follow favorite authors |

### 📈 Activity Logging
| Feature | Description |
|---------|-------------|
| **Action Tracking** | All user actions logged |
| **Metadata** | JSON additional context |
| **IP & User Agent** | Security audit trail |
| **Timestamps** | Immutable creation dates |

### 🎛️ Dual Admin Interface
| Interface | Description |
|-----------|-------------|
| **EasyAdmin 4** (`/admin`) | Quick CRUD for all 18 entities |
| **Custom SB Admin 2** (`/backoffice`) | Rich dashboards, charts, custom workflows |
| **Loan Management** (`/admin/loans`) | Dedicated loan approval/tracking |
| **Reservation Management** (`/admin/reservations`) | Queue management |

---

## 🆕 Recent Updates

### December 2025 - Dual Stock System
Major update separating stock for sales and loans:

| Update | Description |
|--------|-------------|
| **`stockVente`** | New field for books available for purchase |
| **`stockEmprunt`** | New field for books available for borrowing |
| **Auto-calculated Total** | `nbExemplaires` now automatically sums both stocks |
| **Cart Validation** | Shopping cart validates against `stockVente` |
| **Loan Validation** | Loan requests validate against `stockEmprunt` |
| **Form Updates** | Book forms (admin + frontend) updated with dual stock fields |
| **Template Updates** | All book displays show "V: X | E: Y" format for stock |

### Availability Calendar
- **FullCalendar.js Integration** | Interactive calendar for loan/reservation availability
- **Turbo Compatibility** | Fixed JavaScript initialization with Turbo/Hotwire navigation
- **Visual Timeline** | See active loans and expected return dates

### Loan Reminders
- **Console Command** | `php bin/console app:send-loan-reminders` for overdue notifications
- **Email Integration** | Automated reminder emails to users with overdue books

---

## 🏗️ System Architecture

### Entity Relationship Overview

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    User     │────<│    Loan     │>────│   Livre     │
│  (18 fields)│     │  (workflow) │     │  (16 fields)│
└──────┬──────┘     └─────────────┘     └──────┬──────┘
       │                                        │
       │            ┌─────────────┐             │
       └───────────<│ Reservation │>────────────┘
       │            └─────────────┘             │
       │                                        │
       │            ┌─────────────┐             │
       └───────────<│   Review    │>────────────┘
       │            └─────────────┘             │
       │                                        │
       │   ┌─────────────┐   ┌─────────────┐   │
       └──<│    Cart     │──<│  CartItem   │>──┘
       │   └─────────────┘   └─────────────┘
       │
       │   ┌─────────────┐   ┌─────────────┐
       └──<│   Order     │──<│ OrderItem   │>──────────┐
       │   └─────────────┘   └─────────────┘           │
       │                                               │
       │   ┌─────────────┐                             │
       └──<│  Message    │ (sender/recipient)         │
       │   └─────────────┘                             │
       │                                               │
       │   ┌─────────────┐   ┌─────────────┐          │
       └──<│ReadingGoal  │   │ReadProgress │>─────────┘
       │   └─────────────┘   └─────────────┘
       │
       │   ┌─────────────┐   ┌─────────────┐
       └──<│ActivityLog  │   │BannerPref   │
           └─────────────┘   └─────────────┘

┌─────────────┐
│   Livre     │────>┌─────────────┐
│             │     │   Auteur    │
│             │────>├─────────────┤
│             │     │  Categorie  │
│             │────>├─────────────┤
│             │     │   Editeur   │
└─────────────┘     └─────────────┘

┌─────────────┐
│   Banner    │────>┌─────────────┐
│ (scheduling)│     │    User     │ (createdBy)
└─────────────┘     └─────────────┘
```

### Loan Status Flow
```
requested → approved → active → returned
    ↓           ↓         ↓
cancelled   cancelled   overdue → returned
```

### Order Status Flow
```
pending → paid → processing → shipped → delivered
    ↓       ↓         ↓
cancelled cancelled cancelled
                          ↓
                      refunded
```

---

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Symfony CLI
- MySQL/PostgreSQL database
- Node.js & npm (for assets)
- Stripe Account (for payments - optional)

### Step-by-Step Installation

1. **Clone the repository**
    ```bash
    git clone https://github.com/ahmedKhlif/biblio_Symfony.git
    cd biblio_Symfony
    ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` with your credentials:
   ```env
   # Database
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/biblio_db"
   
   # Mailer (for email verification & notifications)
   MAILER_DSN=smtp://user:pass@smtp.example.com:587
   
   # Stripe (for payments - optional)
   STRIPE_PUBLIC_KEY=pk_test_your_public_key
   STRIPE_SECRET_KEY=sk_test_your_secret_key
   ```

5. **Create Database**
   ```bash
   php bin/console doctrine:database:create
   ```

6. **Run Migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

7. **Load Sample Data** (Optional)
   ```bash
   php bin/console doctrine:fixtures:load
   ```

8. **Create Upload Directories**
    ```bash
    mkdir -p public/uploads/images
    mkdir -p public/uploads/pdfs
    mkdir -p public/uploads/avatars
    ```

9. **Install Assets**
   ```bash
   php bin/console assets:install
   npm run build
   ```

10. **Start Development Server**
    ```bash
    symfony serve
    ```
    Or use PHP's built-in server:
    ```bash
    php -S localhost:8000 -t public
    ```

---

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DATABASE_URL` | Database connection string | `mysql://user:pass@localhost:3306/biblio` |
| `MAILER_DSN` | Email server configuration | `smtp://user:pass@smtp.gmail.com:587` |
| `STRIPE_PUBLIC_KEY` | Stripe publishable key | `pk_test_xxx` |
| `STRIPE_SECRET_KEY` | Stripe secret key | `sk_test_xxx` |
| `APP_ENV` | Environment (dev/prod) | `dev` |
| `APP_SECRET` | Application secret key | `generated-secret` |

### Services Configuration

The application uses several custom services in `src/Service/`:

| Service | Purpose |
|---------|---------|
| `EmailService` | Send verification, reset, and notification emails |
| `StripePaymentService` | Handle Stripe payment processing |
| `ActivityLogger` | Log user actions for audit trail |
| `BookRecommendationService` | Generate book recommendations |
| `ReadingStreakService` | Track reading streaks |
| `GoalAchievementService` | Monitor reading goal progress |

---

## 📖 Usage Guide

### Application Routes

#### Public Routes
| Route | Controller | Description |
|-------|------------|-------------|
| `/` | `LandingController` | Public landing page |
| `/login` | `SecurityController` | User login |
| `/register` | `RegistrationController` | User registration |
| `/verify/email/{token}` | `EmailVerificationController` | Email verification |
| `/reset-password` | `PasswordResetController` | Password reset flow |
| `/about` | `AboutController` | About page |
| `/search` | `SearchController` | Search results |
| `/search/autocomplete` | `SearchController` | AJAX autocomplete |

#### User Routes (Authenticated)
| Route | Controller | Description |
|-------|------------|-------------|
| `/profile` | `ProfileController` | User profile management |
| `/livre` | `LivreController` | Browse books |
| `/auteur` | `AuteurController` | Browse authors |
| `/categorie` | `CategorieController` | Browse categories |
| `/cart` | `CartController` | Shopping cart |
| `/checkout` | `CheckoutController` | Payment checkout |
| `/orders` | `OrderController` | Order history |
| `/messages` | `MessageController` | Message center |
| `/reservations` | `ReservationController` | My reservations |
| `/loans` | `LoanController` | My loans |
| `/reading-progress` | `ReadingProgressController` | Reading tracker |
| `/reviews` | `ReviewController` | My reviews |

#### Admin Routes
| Route | Controller | Description |
|-------|------------|-------------|
| `/admin` | `Admin\DashboardController` | EasyAdmin dashboard |
| `/backoffice` | `BackofficeController` | Custom admin dashboard |
| `/admin/loans` | `LoanManagementController` | Loan management |
| `/admin/reservations` | `ReservationManagementController` | Reservation management |
| `/dashboard` | `DashboardController` | Statistics dashboard |
| `/activity-log` | `ActivityLogController` | User activity logs |
| `/banners` | `BannerController` | Banner management |

#### API Endpoints
| Route | Method | Description |
|-------|--------|-------------|
| `/messages/api/unread-count` | GET | Get unread message count |
| `/messages/api/recent` | GET | Get recent messages |
| `/search/autocomplete?q=` | GET | Search suggestions |

### EasyAdmin CRUD Controllers

All entities have dedicated CRUD controllers in `src/Controller/Admin/`:

| Controller | Entity | Features |
|------------|--------|----------|
| `LivreCrudController` | Book | Image upload, PDF upload, relations |
| `AuteurCrudController` | Author | Biography, book list |
| `CategorieCrudController` | Category | Description, book count |
| `EditeurCrudController` | Publisher | Contact info |
| `UserCrudController` | User | Roles, verification status |
| `LoanCrudController` | Loan | Status workflow, approval |
| `BookReservationCrudController` | Reservation | Queue management |
| `OrderCrudController` | Order | Status, payment info |
| `CartCrudController` | Cart | User carts |
| `ReviewCrudController` | Review | Moderation |
| `BannerCrudController` | Banner | Scheduling, targeting |
| `ReadingProgressCrudController` | Progress | User reading stats |
| `ReadingGoalCrudController` | Goal | Target tracking |
| `ActivityLogCrudController` | Log | Audit trail |

### Default Credentials

After loading fixtures (`php bin/console doctrine:fixtures:load`):

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@biblio.com` | `admin123` |
| User | `user@biblio.com` | `user123` |

## 🗂️ Project Structure

```
biblio/
├── assets/                          # Frontend assets
│   ├── app.js                       # Main JS entry point
│   ├── bootstrap.js                 # Stimulus bootstrap
│   ├── controllers.json             # Stimulus controller config
│   ├── controllers/                 # Stimulus controllers
│   └── styles/                      # Custom SCSS/CSS
│
├── bin/
│   ├── console                      # Symfony console
│   └── phpunit                      # PHPUnit runner
│
├── config/
│   ├── bundles.php                  # Registered bundles
│   ├── services.yaml                # Service definitions
│   ├── routes.yaml                  # Route definitions
│   ├── admin_email_config.yaml      # Email templates config
│   └── packages/                    # Bundle configurations
│       ├── doctrine.yaml
│       ├── easyadmin.yaml
│       ├── security.yaml
│       ├── twig.yaml
│       └── ...
│
├── migrations/                      # Doctrine migrations (24 files)
│   ├── Version20251020135839.php    # Initial schema
│   ├── Version20251204070239.php    # Latest migration
│   └── ...
│
├── public/
│   ├── index.php                    # Front controller
│   ├── css/                         # Compiled CSS
│   ├── js/                          # Compiled JavaScript
│   ├── img/                         # Static images
│   ├── ilustration/                 # Illustrations
│   └── uploads/                     # User uploads
│       ├── images/                  # Book covers
│       ├── pdfs/                    # PDF documents
│       └── avatars/                 # User avatars
│
├── src/
│   ├── Kernel.php                   # Application kernel
│   │
│   ├── Command/                     # Console commands
│   │
│   ├── Controller/                  # HTTP Controllers (35 files)
│   │   ├── Admin/                   # EasyAdmin CRUD (20 controllers)
│   │   │   ├── DashboardController.php
│   │   │   ├── LivreCrudController.php
│   │   │   ├── UserCrudController.php
│   │   │   └── ...
│   │   ├── Api/                     # API endpoints
│   │   ├── BackofficeController.php
│   │   ├── LoanManagementController.php
│   │   ├── ReservationManagementController.php
│   │   ├── MessageController.php
│   │   ├── SearchController.php
│   │   ├── CartController.php
│   │   ├── CheckoutController.php
│   │   └── ...
│   │
│   ├── Entity/                      # Doctrine entities (18 entities)
│   │   ├── User.php                 # User with roles, profile, relations
│   │   ├── Livre.php                # Book with all metadata
│   │   ├── Auteur.php               # Author
│   │   ├── Categorie.php            # Category
│   │   ├── Editeur.php              # Publisher
│   │   ├── Loan.php                 # Loan with workflow
│   │   ├── BookReservation.php      # Reservation queue
│   │   ├── Cart.php                 # Shopping cart
│   │   ├── CartItem.php             # Cart line items
│   │   ├── Order.php                # Purchase order
│   │   ├── OrderItem.php            # Order line items
│   │   ├── Message.php              # Internal messages
│   │   ├── Review.php               # Book reviews
│   │   ├── ReadingProgress.php      # Reading tracker
│   │   ├── ReadingGoal.php          # Reading goals
│   │   ├── Banner.php               # Announcement banners
│   │   ├── UserBannerPreference.php # Banner dismissals
│   │   └── ActivityLog.php          # Audit log
│   │
│   ├── Repository/                  # Doctrine repositories
│   │   └── ...                      # One per entity
│   │
│   ├── Form/                        # Form types
│   │
│   ├── Service/                     # Business logic (7 services)
│   │   ├── EmailService.php
│   │   ├── StripePaymentService.php
│   │   ├── ActivityLogger.php
│   │   ├── BookRecommendationService.php
│   │   ├── ReadingStreakService.php
│   │   └── GoalAchievementService.php
│   │
│   ├── Security/                    # Security voters & authenticators
│   │
│   ├── EventSubscriber/             # Event subscribers
│   │
│   └── EventListener/               # Event listeners
│
├── templates/                       # Twig templates (30+ directories)
│   ├── base.html.twig               # Public base layout
│   ├── backendofficebase.html.twig  # Admin base layout (SB Admin 2)
│   ├── pagination.html.twig         # Pagination component
│   │
│   ├── admin/                       # EasyAdmin overrides
│   ├── dashboard/                   # Dashboard views
│   ├── backoffice/                  # Backoffice pages
│   ├── livre/                       # Book templates
│   ├── loan/                        # Loan templates
│   ├── loan_management/             # Admin loan pages
│   ├── reservation/                 # Reservation templates
│   ├── reservation_management/      # Admin reservation pages
│   ├── message/                     # Message center
│   ├── search/                      # Search results
│   ├── cart/                        # Shopping cart
│   ├── checkout/                    # Checkout flow
│   ├── order/                       # Order pages
│   ├── profile/                     # User profile
│   ├── security/                    # Login/register
│   ├── emails/                      # Email templates
│   ├── banner/                      # Banner templates
│   ├── review/                      # Review templates
│   ├── reading_progress/            # Reading progress
│   └── ...
│
├── tests/                           # PHPUnit tests
├── translations/                    # i18n translations
├── var/                             # Cache & logs
├── vendor/                          # Composer dependencies
│
├── .env                             # Environment template
├── .env.local                       # Local overrides (gitignored)
├── composer.json                    # PHP dependencies
├── package.json                     # Node dependencies
├── importmap.php                    # Asset mapping
└── phpunit.dist.xml                 # PHPUnit config
```

---

## 🗄️ Database Schema

### Entity Summary

| Entity | Table | Key Fields | Relations |
|--------|-------|------------|-----------|
| **User** | `user` | email, username, roles, isVerified, isActive | loans, reservations, orders, carts, messages, reviews, progress, goals |
| **Livre** | `livre` | titre, isbn, nbPages, prix, nbExemplaires, image, pdf, isBorrowable | auteur, categorie, editeur, loans, reservations, reviews |
| **Auteur** | `auteur` | nom, prenom, biographie | livres |
| **Categorie** | `categorie` | nom, description | livres |
| **Editeur** | `editeur` | nom, adresse, email, telephone | livres |
| **Loan** | `loans` | status, requestedAt, dueDate, returnedAt, notes | user, livre, approvedBy |
| **BookReservation** | `book_reservations` | position, isActive, notifiedAt | user, livre |
| **Cart** | `cart` | createdAt | user, items |
| **CartItem** | `cart_item` | quantity | cart, livre |
| **Order** | `orders` | orderNumber, status, totalAmount, paymentMethod, stripePaymentIntentId | user, items |
| **OrderItem** | `order_item` | quantity, price | order, livre |
| **Message** | `messages` | subject, content, isRead, readAt | sender, recipient |
| **Review** | `review` | rating, comment, verified, helpful | user, livre |
| **ReadingProgress** | `reading_progress` | progressPercentage, currentPage, bookmarks, isCompleted | user, livre |
| **ReadingGoal** | `reading_goal` | goalType, targetValue, currentValue, startDate, endDate | user |
| **Banner** | `banners` | title, content, type, position, status, startDate, endDate, priority | createdBy |
| **UserBannerPreference** | `user_banner_preference` | isDismissed | user, banner |
| **ActivityLog** | `activity_log` | action, description, metadata, ipAddress | user |

---

## 🛠️ Technologies Used

### Backend Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | 8.2+ | Server-side language |
| **Symfony** | 7.x | MVC framework |
| **Doctrine ORM** | 3.x | Database abstraction |
| **EasyAdmin** | 4.x | Admin CRUD generator |
| **Twig** | 3.x | Template engine |
| **KnpPaginator** | 6.x | Pagination |
| **Symfony Mailer** | 7.x | Email sending |
| **Stripe PHP** | Latest | Payment processing |

### Frontend Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **SB Admin 2** | 4.x | Admin template |
| **Bootstrap** | 4.6 | CSS framework |
| **Font Awesome** | 5.x | Icon library |
| **Chart.js** | 4.4 | Data visualization |
| **jQuery** | 3.x | DOM manipulation |
| **Stimulus** | 3.x | JS framework |

### Database

| Option | Use Case |
|--------|----------|
| **MySQL 8** | Production |
| **PostgreSQL 15** | Production alternative |
| **SQLite** | Development/Testing |

### Development Tools

| Tool | Purpose |
|------|---------|
| **Composer** | PHP dependency management |
| **npm** | Node.js package management |
| **Symfony CLI** | Local development server |
| **PHPUnit** | Testing framework |
| **Git** | Version control |
| **Docker** | Containerization (optional) |

---

## 🔒 Security Features

### Authentication & Authorization

| Feature | Implementation |
|---------|----------------|
| **Login/Logout** | Symfony Security with form login |
| **Email Verification** | Token-based with `verificationToken` field |
| **Password Reset** | Secure token with expiration (`resetTokenExpiresAt`) |
| **Role Hierarchy** | `ROLE_USER` < `ROLE_ADMIN` |
| **Remember Me** | Cookie-based session persistence |
| **Account Status** | `isActive` flag for account suspension |

### Data Protection

| Feature | Implementation |
|---------|----------------|
| **CSRF Protection** | Symfony CSRF tokens on all forms |
| **XSS Prevention** | Twig auto-escaping enabled |
| **SQL Injection** | Doctrine ORM parameterized queries |
| **Password Hashing** | Symfony password hasher (bcrypt/argon2) |

### File Upload Security

| Type | Restrictions |
|------|--------------|
| **Images** | JPEG, PNG, GIF only; max 1MB |
| **PDFs** | PDF only; max 10MB; MIME validation |
| **Storage** | Safe filename slugging; separate directories |

### Payment Security

| Feature | Implementation |
|---------|----------------|
| **Stripe Integration** | Server-side payment processing |
| **No Card Storage** | Stripe handles all card data |
| **Payment Intent** | PCI-compliant payment flow |
| **Webhook Verification** | Signature validation |

### Audit Trail

| Feature | Implementation |
|---------|----------------|
| **Activity Logging** | All user actions logged with `ActivityLog` |
| **IP Tracking** | Client IP recorded |
| **User Agent** | Browser information stored |
| **Timestamps** | Immutable creation dates |

---

## 📱 Responsive Design

The application is fully responsive and works seamlessly on:
- **Desktop Computers** (1920px+)
- **Laptops** (1024px - 1920px)
- **Tablets** (768px - 1024px)
- **Mobile Phones** (320px - 768px)

## 🌍 Browser Support

- **Chrome** (recommended)
- **Firefox**
- **Safari**
- **Edge**
- **Opera**

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Symfony** team for the excellent framework
- **SB Admin 2** creators for the beautiful admin template
- **Font Awesome** for the comprehensive icon set
- **Bootstrap** team for the responsive framework
- **Chart.js** for the charting capabilities

## 📞 Support

If you have any questions or need help:
1. Check the [Issues](https://github.com/ahmedKhlif/biblio_Symfony/issues) page
2. Create a new issue with detailed information
3. Contact the maintainer

---

## 📋 Version History

### v3.0.0 (December 2024) - Current
**Major Features:**
- ✅ **Message Center** - Full internal messaging with real-time notifications
- ✅ **Search with Autocomplete** - Global search with AJAX suggestions
- ✅ **Chart.js Integration** - Fixed charts in EasyAdmin (Turbo-compatible)
- ✅ **Custom Loan Management** - Dedicated admin pages at `/admin/loans`
- ✅ **Custom Reservation Management** - Queue management at `/admin/reservations`
- ✅ **Banner System** - Scheduled announcements with targeting
- ✅ **Reading Progress** - Track pages, bookmarks, completion
- ✅ **Reading Goals** - Set and monitor reading targets
- ✅ **Activity Logging** - Complete audit trail

### v2.0.0
- ✅ **E-Commerce** - Cart, orders, Stripe payments
- ✅ **PDF Management** - Upload, view, download PDFs
- ✅ **Review System** - Ratings, comments, helpful votes
- ✅ **User Profiles** - Extended profile fields, addresses

### v1.0.0
- ✅ **Core CRUD** - Books, Authors, Categories, Publishers
- ✅ **Loan System** - Basic loan workflow
- ✅ **User Auth** - Registration, login, roles
- ✅ **EasyAdmin** - Admin interface
- ✅ **SB Admin 2** - Custom backoffice theme

---

## 📊 Project Statistics

| Metric | Count |
|--------|-------|
| **Entities** | 18 |
| **Controllers** | 35+ |
| **EasyAdmin CRUDs** | 20 |
| **Services** | 7 |
| **Migrations** | 24 |
| **Templates** | 100+ |

---

## 📸 Screenshots

> Add your screenshots to `public/img/screenshots/`

### Admin Dashboard
![Dashboard](public/img/screenshots/dashboard.png)

### Book Management
![Books](public/img/screenshots/books.png)

### Loan Management
![Loans](public/img/screenshots/loans.png)

### Message Center
![Messages](public/img/screenshots/messages.png)

---

## 🙏 Acknowledgments

- **[Symfony](https://symfony.com)** - The PHP framework
- **[EasyAdmin](https://symfony.com/bundles/EasyAdminBundle)** - Admin generator
- **[SB Admin 2](https://startbootstrap.com/theme/sb-admin-2)** - Dashboard template
- **[Bootstrap](https://getbootstrap.com)** - CSS framework
- **[Font Awesome](https://fontawesome.com)** - Icon library
- **[Chart.js](https://chartjs.org)** - Charts library
- **[Stripe](https://stripe.com)** - Payment processing

---

**Made with ❤️ by Ahmed Khlif**

[![GitHub](https://img.shields.io/badge/GitHub-ahmedKhlif-181717?style=for-the-badge&logo=github)](https://github.com/ahmedKhlif)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-0A66C2?style=for-the-badge&logo=linkedin)](https://linkedin.com/in/ahmedKhlif)

---

<p align="center">
  <img src="https://img.shields.io/github/stars/ahmedKhlif/biblio_Symfony?style=social" alt="GitHub Stars">
  <img src="https://img.shields.io/github/forks/ahmedKhlif/biblio_Symfony?style=social" alt="GitHub Forks">
  <img src="https://img.shields.io/github/watchers/ahmedKhlif/biblio_Symfony?style=social" alt="GitHub Watchers">
</p>