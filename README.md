# Student Notes Hub

> **⚠️ Project Status: Archived**  
> This project is archived and no longer actively maintained. However, anyone is free to use, modify, and distribute this project for any purpose. Feel free to fork it, improve it, and make it your own!

---

![Student Notes Hub Homepage](docs/homepage.png)

A comprehensive web-based platform for students to share, discover, and collaborate on academic notes. Built with PHP, MySQL, and modern web technologies.

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Email Configuration](#email-configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Technologies Used](#technologies-used)
- [Contributing](#contributing)
- [License](#license)

## 🎯 About

Student Notes Hub is a full-featured platform designed to help students:
- **Share** their academic notes with the community
- **Discover** notes from other students
- **Collaborate** through group chats and messaging
- **Organize** notes by categories and subjects
- **Rate and Review** notes to help others find quality content

The platform includes user authentication, email verification, password reset functionality, real-time notifications, and a comprehensive admin panel.

## ✨ Features

### Core Features
- **User Authentication & Authorization**
  - Secure registration with email verification
  - Password reset via email
  - Session management
  - Role-based access control (Admin, User)

- **Note Management**
  - Upload notes (PDF, DOCX, and other file formats)
  - Categorize notes by subject
  - Draft and published status
  - Edit and delete own notes
  - View counter and download tracking

- **Search & Discovery**
  - Title-based search functionality
  - Category filtering
  - Featured notes section
  - Related notes suggestions

- **Social Features**
  - Follow/unfollow other users
  - View user profiles
  - Like notes
  - Comment on notes
  - Rating system

- **Messaging System**
  - Private one-on-one conversations
  - Group chats
  - File attachments in messages
  - Read receipts and timestamps
  - Real-time message updates

- **Notifications**
  - Real-time notification system
  - Email notifications for important events
  - Mark as read/unread
  - Notification history

- **Admin Panel**
  - User management
  - Note moderation
  - Category management
  - System statistics

### Additional Features
- Dark mode support
- Responsive design (mobile-friendly)
- File upload with validation
- CSRF protection
- SQL injection prevention (prepared statements)
- Password hashing (bcrypt)
- Secure file storage

## 📦 Requirements

- **Web Server**: Apache or Nginx
- **PHP**: Version 8.0 or higher
- **MySQL**: Version 5.7 or higher (or MariaDB 10.2+)
- **Extensions**:
  - PDO
  - PDO_MySQL
  - mbstring
  - fileinfo
  - GD (for image processing)
- **Composer** (optional, for PHPMailer installation)

## 🚀 Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/Sanyuha/Student-Notes-Hub.git
cd Student-Notes-Hub
```

### Step 2: Install Dependencies

#### Option A: Using Composer (Recommended)

```bash
composer install
```

#### Option B: Manual Installation

1. Download PHPMailer from [GitHub Releases](https://github.com/PHPMailer/PHPMailer/releases)
2. Extract and place it in `vendor/PHPMailer/` directory
3. Ensure the structure is: `vendor/PHPMailer/src/PHPMailer.php`

### Step 3: Database Setup

1. Create a MySQL database:
   ```sql
   CREATE DATABASE student_notes_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Import the database schema:
   - Open phpMyAdmin or your MySQL client
   - Select the `student_notes_hub` database
   - Import `student_notes_hub.sql` file

### Step 4: Configure Database

1. Copy `db.php` (if it doesn't exist, create it based on your database credentials):
   ```php
   <?php
   $host = 'localhost';
   $db   = 'student_notes_hub';
   $user = 'your_username';
   $pass = 'your_password';
   $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
   try {
       $pdo = new PDO($dsn, $user, $pass,
                      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
   } catch (PDOException $e) {
       die('DB connection failed: ' . $e->getMessage());
   }
   ```

2. Update `bootstrap.php` with your database credentials (lines 16-18):
   ```php
   $dsn  = 'mysql:host=localhost;dbname=student_notes_hub;charset=utf8mb4';
   $user = 'your_username';
   $pass = 'your_password';
   ```

### Step 5: Configure Email Settings

1. Copy the email configuration template:
   ```bash
   cp email-config.example.php email-config.php
   ```

2. Edit `email-config.php` and update with your email credentials (see [Email Configuration](#email-configuration) section below)

### Step 6: Set Permissions

Ensure the `uploads/` directory is writable:
```bash
chmod 755 uploads/
```

### Step 7: Configure Web Server

#### Apache (.htaccess)
The project should work with Apache's default configuration. Ensure `mod_rewrite` is enabled.

#### Nginx
Configure your Nginx server block to point to the project directory.

### Step 8: Access the Application

1. Open your browser and navigate to: `http://localhost/Student-Notes-Hub/`
2. Register a new account
3. Verify your email (check your inbox)
4. Start using the platform!

## ⚙️ Configuration

### Database Configuration

The database configuration is stored in two files:
- `db.php` - Legacy database connection (if used)
- `bootstrap.php` - Main database connection (lines 16-18)

**Important**: Never commit `db.php` to version control. It's already in `.gitignore`.

### Email Configuration

Email functionality is used for:
- Email verification during registration
- Password reset links
- Notification emails

#### Setting Up Gmail

1. **Enable 2-Step Verification**:
   - Go to [Google Account Security](https://myaccount.google.com/security)
   - Enable "2-Step Verification"

2. **Generate App Password**:
   - Go to [App Passwords](https://myaccount.google.com/apppasswords)
   - Select "Mail" and "Other (Custom name)"
   - Enter "Student Notes Hub"
   - Click "Generate"
   - Copy the 16-character password

3. **Configure email-config.php**:
   ```php
   return [
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'smtp_secure' => 'tls',
       'smtp_auth' => true,
       'smtp_username' => 'your-email@gmail.com',
       'smtp_password' => 'your-16-char-app-password',
       'from_email' => 'your-email@gmail.com',
       'from_name' => 'Student Notes Hub',
       'reply_to_email' => 'your-email@gmail.com',
       'reply_to_name' => 'Student Notes Hub Support',
   ];
   ```

#### Using Other Email Providers

- **Outlook/Hotmail**: `smtp-mail.outlook.com`, port 587
- **Yahoo**: `smtp.mail.yahoo.com`, port 587
- **Custom SMTP**: Update `smtp_host`, `smtp_port`, and `smtp_secure` accordingly

**Note**: `email-config.php` is in `.gitignore` and will not be committed to the repository.

## 📁 Project Structure

```
Student-Notes-Hub/
├── api/                    # API endpoints
│   ├── add-comment.php
│   ├── create-group.php
│   ├── download.php
│   ├── fulltext-search.php
│   ├── get-notifications.php
│   ├── send-message.php
│   └── ...
├── components/             # Reusable components
│   ├── header.php
│   └── footer.php
├── upload/                 # Upload handling
│   ├── upload.php
│   └── ...
├── uploads/               # User uploaded files (not in git)
│   ├── [note files]
│   └── chat/
├── vendor/                # Dependencies (PHPMailer)
├── bootstrap.php          # Core initialization
├── db.php                 # Database config (not in git)
├── email-config.php       # Email config (not in git)
├── email-config.example.php
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
├── profile.php            # User profile
├── chat.php               # Messaging interface
├── search.php             # Search page
├── student_notes_hub.sql  # Database schema
├── style.css              # Main stylesheet
└── README.md              # This file
```

## 🛠️ Technologies Used

- **Backend**: PHP 8.0+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Email**: PHPMailer
- **Security**: 
  - Prepared statements (PDO)
  - Password hashing (bcrypt)
  - CSRF tokens
  - Session management

## 📖 Usage

### For Students

1. **Register**: Create an account with your email
2. **Verify Email**: Check your inbox and click the verification link
3. **Upload Notes**: Go to "My Notes" and upload your academic notes
4. **Share**: Set notes to "Published" to make them visible to others
5. **Discover**: Browse notes by category or use the search function
6. **Connect**: Follow other students and join group chats
7. **Rate**: Help the community by rating and commenting on notes

### For Administrators

1. **Login**: Use admin credentials to access the admin panel
2. **Manage Users**: View, edit, or remove user accounts
3. **Moderate Notes**: Review and manage published notes
4. **Categories**: Add, edit, or remove note categories
5. **Statistics**: View platform usage statistics

## 🔒 Security Notes

- **Database Credentials**: Never commit `db.php` to version control
- **Email Credentials**: Never commit `email-config.php` to version control
- **File Uploads**: The `uploads/` directory should be outside the web root in production
- **HTTPS**: Always use HTTPS in production
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection**: All queries use prepared statements
- **XSS Protection**: All user input is sanitized with `htmlspecialchars()`

## 🐛 Troubleshooting

### Email Not Sending

1. Check that `email-config.php` exists and is configured correctly
2. Verify your Gmail App Password is correct
3. Ensure 2-Step Verification is enabled on your Google account
4. Check PHP error logs for detailed error messages
5. Test SMTP connection using PHPMailer's debug mode (set `SMTPDebug = 2`)

### Database Connection Failed

1. Verify database credentials in `bootstrap.php` and `db.php`
2. Ensure MySQL service is running
3. Check that the database `student_notes_hub` exists
4. Verify user has proper permissions on the database

### File Upload Issues

1. Check `uploads/` directory permissions (should be writable)
2. Verify PHP `upload_max_filesize` and `post_max_size` settings
3. Check PHP error logs for upload errors

### PHPMailer Not Found

1. Install via Composer: `composer install`
2. Or manually download and place in `vendor/PHPMailer/`
3. Verify the file structure matches the expected paths

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

**Note**: This project is archived. You are free to use, modify, and distribute this project for any purpose without restrictions.

## 👥 Authors

- **Sanyuha** - [GitHub](https://github.com/Sanyuha)

## 🙏 Acknowledgments

- PHPMailer for email functionality
- Font Awesome for icons
- All contributors and users of the platform

---

**Note**: This project is archived and no longer actively maintained. However, it is fully functional and available for anyone to use, modify, and distribute freely.

