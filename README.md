# Google Docs Clone

A web-based document management system that allows users to create, edit, and collaborate on documents in real-time.

## Features

- User Management (Admin and Regular Users)
- Real-time document editing with auto-save
- Document collaboration
- Activity logging
- Integrated messaging system
- Rich text editing with HTML support
- Image upload support

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Setup Instructions

1. Clone the repository
2. Create a MySQL database named `google_docs_clone`
3. Import the database schema from `database/schema.sql`
4. Configure database connection in `config/database.php`
5. Start your web server
6. Access the application through your web browser

## Directory Structure

```
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── database/
├── includes/
├── templates/
└── uploads/
```

## Security

- All user passwords are hashed using PHP's password_hash()
- Input validation and sanitization implemented
- CSRF protection enabled
- XSS protection implemented 