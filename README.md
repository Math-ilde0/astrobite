# **AstroBite**

AstroBite is a concept e-commerce website focused on **freeze-dried space food**.  
Built with **PHP**, **MySQL**, and **AJAX**, the project aims to deliver a fast, clean, and intuitive shopping experience inspired by space exploration.

Users can browse products, manage their accounts, place orders, and navigate through a simple and efficient interface.

---

## **Features**

### **Authentication**
- Secure **email + password** login  
- Account registration  
- Password reset  
- Optional **OAuth login** (Google & Facebook)

### **Shopping & Checkout**
- Product catalog with **filtering**  
- Detailed product pages  
- **AJAX-powered** cart updates  
- Guided checkout with order summary  
- Order confirmation screen  

### **User Profile**
- Update personal information  
- Access full order history  

### **Admin Panel**
- Create, edit, and delete products  
- Manage categories  
- Manage users  
- View and monitor orders  
- Update product stock  
- Fully protected **/admin** module (restricted to admin accounts)


### Test Accounts

For testing purposes, the project includes two predefined accounts:

- **User account**
  - Email: `user@astrobite.com`
  - Password: `password`

- **Admin account**
  - Email: `admin@astrobite.com`
  - Password: `admin`


---

## **Tech Stack**

### **Frontend**
- HTML5  
- CSS3  
- Vanilla JavaScript  
- AJAX

### **Backend**
- PHP 8  
- MySQL  
- Composer

### **Security**
- Password hashing using **bcrypt**  
- Secure sessions with **HTTPOnly** cookies  
- Input sanitization and validation  
- Access restrictions on admin routes  

---

## **Installation**

### **1. Clone the repository**

```bash
git clone https://github.com/Math-ilde0/astrobite.git
2. Install dependencies
composer install

3. Configure your environment
Update your database and OAuth credentials inside:

includes/config.php  
includes/oauth.php
or set them as server environment variables.

4. Import the database
In phpMyAdmin or MySQL CLI:

Create a new database:

CREATE DATABASE astrobite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
Import schema:

sql/schema.sql
If using OAuth, also run:

sql/migrate-oauth.sql
5. Run the project locally
If using MAMP/XAMPP:


http://localhost/astrobite
OAuth Setup (Google & Facebook)
Google Login
Go to Google Cloud Console → Credentials

Create an OAuth 2.0 Client ID (Web)

Add the redirect URI:

http(s)://YOUR_HOST/google-login/callback.php
Add your credentials in environment variables:

GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
or inside includes/oauth.php.

Facebook Login
Go to Meta for Developers → Create App

Enable Facebook Login

Add the redirect URI:

http(s)://YOUR_HOST/facebook-login/callback.php
Add:

FACEBOOK_CLIENT_ID
FACEBOOK_CLIENT_SECRET
Project Structure

astrobite/
│
├── admin/               # Admin dashboard pages
├── ajax/                # AJAX controllers (cart, login, etc.)
├── assets/              # CSS, JS, images
├── includes/            # Reusable components (header, config, auth)
├── login/               # OAuth handlers
├── sql/                 # Database schema + migrations
├── vendor/              # Composer dependencies
│
├── index.php            # Homepage
├── products.php         # Products catalog
├── product.php          # Product details
├── cart.php             # Shopping cart
├── checkout.php         # Checkout flow
├── order-confirmation.php
├── login.php
├── register.php
├── profile.php
├── logout.php
│
└── README.md
