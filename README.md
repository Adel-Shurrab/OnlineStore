# ClickCart - E-Commerce Platform

![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![License](https://img.shields.io/badge/License-Training%20Purpose-green.svg)

**ClickCart** is a complete e-commerce platform built with PHP and MySQL. This project is designed for **training and educational purposes**, demonstrating modern web development practices, MVC architecture, and essential e-commerce functionality.

---

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [Admin Panel](#-admin-panel)
- [Security Features](#-security-features)
- [API Integration](#-api-integration)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### Customer Features

- **User Authentication & Authorization**

  - Traditional email/password registration and login
  - Google OAuth 2.0 integration for social login
  - Email verification with OTP
  - Password reset functionality
  - User profile management
- **Product Catalog**

  - Browse products by categories
  - Advanced product search and filtering
  - Product sorting (price, popularity, newest, best sellers)
  - Image carousel for product photos
  - Product ratings and reviews (verified buyers only)
  - View count tracking
- **Shopping Cart**

  - Add/remove items from cart
  - Update product quantities
  - Apply promotional codes
  - Real-time price calculation
  - Cart persistence across sessions
- **Wishlist**

  - Save favorite items for later
  - Easy access to saved products
  - Direct add-to-cart from wishlist
- **Order Management**

  - Secure checkout process
  - Order history tracking
  - Order status updates
  - Payment processing
- **Promotional System**

  - Discount codes support
  - Category-specific promotions
  - Item-specific promotions
  - Time-limited offers
  - Event-based sales

### Admin Features

- **Dashboard**

  - Overview of total members, items, comments, and orders
  - Latest registered users
  - Recent items and comments
  - Quick access to pending approvals
- **User Management**

  - View and manage all users
  - Activate/deactivate user accounts
  - Edit user information
  - Ban/unban users
  - Pagination for large user lists
- **Product Management**

  - Add, edit, and delete products
  - Approve/reject user-submitted items
  - Manage product categories
  - Image upload and management (up to 3 images per product)
  - Stock quantity tracking
- **Order Management**

  - View all orders
  - Update order status
  - Track payments
  - Order history
- **Comment & Review Moderation**

  - Approve/reject product reviews
  - Moderate user comments
  - Ensure quality content
- **Promotional Code Management**

  - Create and manage promo codes
  - Set discount values and validity periods
  - Track usage limits
  - Category/item-specific promotions
- **Category Management**

  - Create and edit product categories
  - Organize product catalog
  - Category visibility control

### Additional Features

- **Multi-language Support**

  - English and Arabic language support
  - Easy language switching
  - Internationalization ready
- **Responsive Design**

  - Mobile-friendly interface
  - Bootstrap 5 framework
  - Modern and clean UI
- **Email Notifications**

  - Order confirmation emails
  - Password reset emails
  - Account verification emails
  - PHPMailer integration

---

## 🛠 Technology Stack

### Backend

- **PHP 7.4+** - Server-side scripting
- **MySQL** - Relational database management
- **PDO** - Database abstraction layer for secure queries

### Frontend

- **HTML5** - Semantic markup
- **CSS3** - Modern styling
- **JavaScript** - Client-side interactivity
- **Bootstrap 5** - Responsive framework
- **Font Awesome** - Icon library

### Libraries & Dependencies

- **Google API Client** - OAuth 2.0 authentication
- **PHPMailer** - Email sending functionality

### Development Tools

- **Composer** - Dependency management
- **Git** - Version control

---

## 💻 System Requirements

- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache or Nginx
- **Composer**: Latest version
- **Extensions Required**:
  - PDO MySQL
  - OpenSSL
  - MBString
  - cURL

---

## 📥 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/OnlineStore.git
cd OnlineStore
```

### 2. Install Dependencies

```bash
composer install
```

This will install:

- Google API Client
- PHPMailer

### 3. Database Setup

1. Create a new MySQL database:

```sql
CREATE DATABASE eshop CHARACTER SET utf8 COLLATE utf8_general_ci;
```

2. Import the database schema:

```bash
mysql -u root -p eshop < database/eshop.sql
```

Or use phpMyAdmin to import the SQL file.

### 4. Configure Database Connection

Edit `admin/connect.php`:

```php
$dsn = 'mysql:host=localhost;dbname=eshop;charset=utf8';
$user = 'your_database_username';
$pass = 'your_database_password';
```

### 5. Configure Google OAuth (Optional)

Edit `init.php` and replace with your Google credentials:

```php
$gClient->setClientId("YOUR_CLIENT_ID");
$gClient->setClientSecret("YOUR_CLIENT_SECRET");
$gClient->setRedirectUri("YOUR_REDIRECT_URI");
```

To obtain Google OAuth credentials:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URIs

### 6. Configure Email Settings

Edit `init.php` and update PHPMailer settings:

```php
$mail->Username = 'your_email@gmail.com';
$mail->Password = 'your_app_password';
$mail->setFrom('your_email@gmail.com', 'Your Shop Name');
```

**Note**: For Gmail, you need to use an [App Password](https://support.google.com/accounts/answer/185833).

### 7. Set Permissions

Ensure the uploads directory is writable:

```bash
chmod -R 755 data/uploads
```

---

## ⚙️ Configuration

### Upload Directory

Product images are stored in `data/uploads/`. Make sure this directory exists and has write permissions.

### Session Configuration

Sessions are used for user authentication. Ensure your PHP installation has sessions enabled.

### Security Settings

- Update the redirect URIs in `init.php` to match your domain
- In production, use HTTPS for secure data transmission
- Never commit sensitive credentials to version control

---

## 🚀 Usage

### Accessing the Application

1. **Customer Interface**:

   - Navigate to `http://localhost/online-store/` or your configured domain
   - Browse products, add to cart, and place orders
2. **Admin Panel**:

   - Navigate to `http://localhost/online-store/admin/`
   - Default admin credentials (set during initial setup)
   - Manage products, users, orders, and more

### Creating an Admin Account

1. Register a new user account through the website
2. Manually update the database to set `group_id = 1` for admin privileges:

```sql
UPDATE users SET group_id = 1 WHERE email = 'admin@example.com';
```

### Adding Products

**As Admin**:

1. Log into admin panel
2. Navigate to "Items" → "Add Item"
3. Fill in product details (name, description, price, quantity, category)
4. Upload product images (up to 3)
5. Submit and approve the item

**As User**:

1. Log into your account
2. Navigate to "New Ad"
3. Submit product for admin approval

---

## 📁 Project Structure

```
OnlineStore/
│
├── admin/                      # Admin panel
│   ├── categories.php          # Category management
│   ├── comments.php            # Comment moderation
│   ├── connect.php             # Database connection
│   ├── dashboard.php           # Admin dashboard
│   ├── items.php               # Product management
│   ├── members.php             # User management
│   ├── orders.php              # Order management
│   ├── promo_codes.php         # Promotional code management
│   ├── includes/               # Admin includes
│   └── layout/                 # Admin assets
│
├── data/
│   └── uploads/                # Product image uploads
│
├── includes/
│   ├── func/
│   │   ├── functions.php       # Core functions
│   │   └── validationFunc.php  # Validation functions
│   ├── langs/
│   │   ├── ar.php              # Arabic language
│   │   ├── en.php              # English language
│   │   └── language.php        # Language handler
│   └── tmps/
│       ├── header.php          # Header template
│       └── footer.php          # Footer template
│
├── layout/
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   └── images/                 # Static images
│
├── vendor/                     # Composer dependencies
│
├── cart.php                    # Shopping cart
├── index.php                   # Homepage
├── items.php                   # Product catalog
├── login.php                   # Login page
├── register.php                # Registration page
├── profile-settings.php        # User profile
├── newAd.php                   # Create new listing
├── process_checkout.php        # Checkout processing
├── forgetPass.php              # Password recovery
├── init.php                    # Application initialization
├── composer.json               # Composer dependencies
└── README.md                   # This file
```

---

## 🗄️ Database Schema

### Main Tables

- **users** - User accounts and profiles
- **items** - Product listings
- **categories** - Product categories
- **cart** - Shopping cart items
- **orders** - Customer orders
- **order_items** - Order line items
- **comments** - Product reviews
- **ratings** - Product ratings
- **wishlist** - User wishlists
- **promo_codes** - Promotional discounts
- **item_views** - Product view tracking

### Key Relationships

- Users can have multiple items, orders, and cart items
- Items belong to categories
- Orders contain multiple order_items
- Comments and ratings link users to items
- Promo codes can apply to specific items or categories

---

## 🔐 Admin Panel

### Access

- URL: `http://your-domain/admin/`
- Requires `group_id = 1` in the users table

### Features

1. **Dashboard**: Overview statistics and recent activity
2. **Members**: User management and activation
3. **Items**: Product catalog management
4. **Categories**: Organize products
5. **Comments**: Review moderation
6. **Orders**: Order processing and tracking
7. **Promo Codes**: Discount management

---

## 🔒 Security Features

- **SQL Injection Protection**: PDO prepared statements
- **XSS Prevention**: Input sanitization and filtering
- **CSRF Protection**: Session-based token validation
- **Password Hashing**: Secure password storage
- **Session Management**: Secure session handling
- **Email Verification**: OTP-based account verification
- **File Upload Validation**: Image type and size restrictions
- **User Authentication**: Role-based access control

---

## 🔌 API Integration

### Google OAuth 2.0

- Social login functionality
- Account selection prompt
- Email and profile access

### PHPMailer

- Transactional emails
- SMTP configuration
- TLS encryption

---

## 📸 Screenshots

_Add screenshots of your application here to showcase the UI_

### Homepage

![Homepage](path/to/screenshot.png)

### Product Page

![Product Page](path/to/screenshot.png)

### Admin Dashboard

![Admin Dashboard](path/to/screenshot.png)

---

## 🤝 Contributing

This is a training project, but contributions are welcome for educational purposes!

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📧 Contact

For questions or support regarding this training project:

**Email**: adelshurrab2003@gmail.com

---

## 🙏 Acknowledgments

- Bootstrap team for the responsive framework
- Font Awesome for the icon library
- Google for OAuth 2.0 API
- PHPMailer contributors

---

## 📚 Learning Resources

This project demonstrates:

- PHP MVC architecture
- Database design and normalization
- User authentication and authorization
- E-commerce workflows
- RESTful principles
- Responsive web design
- Third-party API integration

---

Made with ❤️ for learning purposes
