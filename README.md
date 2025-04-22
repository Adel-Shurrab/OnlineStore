# ClickCart - Online Store

A feature-rich eCommerce platform built with PHP, MySQL, and Bootstrap that allows users to browse products, add items to their cart, manage orders, and more.

## Features

- **User Authentication**
  - Registration and login system
  - Google authentication integration
  - Email verification
  - Password recovery functionality

- **Product Management**
  - Browse products by categories
  - View product details with images
  - Rating and review system
  - Track product views and popularity

- **Shopping Experience**
  - Responsive and intuitive user interface
  - Add products to cart
  - Apply promo codes and discounts
  - Secure checkout process

- **User Dashboard**
  - View order history
  - Manage personal information
  - Track order status
  - Save favorite items

- **Admin Panel**
  - Manage products and categories
  - User management
  - Order processing
  - Analytics and reporting

## Technology Stack

- **Frontend**: HTML, CSS, Bootstrap, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Authentication**: Custom PHP authentication + Google OAuth
- **Email Service**: PHPMailer with Gmail SMTP
- **Payment Processing**: Integrated payment gateway

## Installation

1. **Prerequisites**
   - XAMPP, WAMP, MAMP, or any PHP development environment
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **Setup Instructions**
   - Clone the repository to your htdocs folder
     ```
     git clone https://github.com/yourusername/clickcart.git
     ```
   - Create a MySQL database named 'online_store'
   - Import the SQL file from the 'database' folder
   - Configure database connection in 'admin/connect.php'
   - Start your Apache and MySQL servers

3. **Configuration**
   - Update Google OAuth credentials in 'init.php'
   - Configure email settings for PHPMailer in 'init.php'
   - Set up your virtual host (optional)

## Usage

1. **Customer Side**
   - Register for a new account or login
   - Browse products by category
   - Add items to cart
   - Apply promo codes at checkout
   - Complete purchase with secure payment

2. **Admin Side**
   - Access the admin panel via '/admin'
   - Manage products and inventory
   - Process orders and update order status
   - View analytics and sales reports

## Project Structure

- `index.php` - Main entry point and homepage
- `init.php` - Initialization file with configurations
- `admin/` - Admin panel files
- `includes/` - Core functionality files
  - `func/` - Helper functions
  - `langs/` - Multilingual support
  - `tmps/` - Template files
- `layout/` - Frontend assets
  - `css/` - Stylesheets
  - `js/` - JavaScript files
  - `images/` - Image assets
- `data/uploads/` - User uploaded content

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- Bootstrap for the responsive design framework
- Google OAuth for authentication services
- PHPMailer for email functionality
- Font Awesome for icons
- All contributors who have helped improve this project 