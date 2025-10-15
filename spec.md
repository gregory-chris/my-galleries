# my-galleries.com - MVP Specification

## Project Overview

A web application for uploading, organizing, and viewing personal photo galleries.
Users can create multiple galleries, upload images, and browse their collection through an intuitive interface.

## Tech Stack

### Frontend

- **React** - UI library
- **JavaScript** - Primary language. No TypeScript.
- **Vite** - Build tool and dev server
- **Tailwind CSS** - Styling framework
- **Heroicons** - Icon library
- **Google Fonts** - Typography

### Backend

- **PHP** - Server-side logic and API. Use Composer - Assume it is installed.
- **Composer** - Dependency manager
- **RESTful API** - Architecture style
- **Slim** - Framework
- **SQLite** - Database
- **Apache2** - Web server.
- **mod_rewrite** - Apache module for URL rewriting. Create .htaccess file.
- **JWT** - JSON Web Tokens for authentication
- **UTC** - Use Universal Time for timestamps

## MVP Features

### 1. User Authentication

- Sign up with email and password
- Login/logout functionality
- Session management
- Top bar with user icon and dropdown menu
- Modal-based login/signup forms

### 2. Gallery Management

- Create new gallery (name, optional description)
- List all user's galleries (grid or list view)
- View individual gallery
- Edit gallery details (name, description)
- Delete gallery

### 3. Image Upload

- Upload multiple images to a gallery
- Supported formats: JPG, PNG, GIF, WEBP
- Upload progress indicator
- Image preview before/after upload
- Basic error handling for failed uploads

### 4. Image Viewing

- Gallery page showing all images in grid layout
- Click image to view in larger size
- Basic slideshow/carousel navigation
- Previous/Next navigation buttons

### 5. Basic UI Components

- Responsive navigation bar
- Dashboard/home page showing user galleries
- Gallery detail page
- Image upload interface
- Image viewer/lightbox

## Database Schema

### Users Table

```SQL
- id (INTEGER PRIMARY KEY)
- email (TEXT UNIQUE)
- password_hash (TEXT)
- auth_tokens (TEXT) COMMENT 'JSON Web Tokens for authentication separated by semicolons'
- created_at (DATETIME) COMMENT 'UTC timestamp of user creation'
```

### Galleries Table

```SQL
- id (INTEGER PRIMARY KEY)
- user_id (INTEGER FOREIGN KEY)
- name (TEXT)
- description (TEXT)
- created_at (DATETIME)
- updated_at (DATETIME)
```

### Images Table

```SQL
- id (INTEGER PRIMARY KEY)
- gallery_id (INTEGER FOREIGN KEY)
- filename (TEXT)
- original_filename (TEXT)
- thumbnail_filename (TEXT)
- file_size (INTEGER)
- width (INTEGER)
- height (INTEGER)
- uploaded_at (DATETIME)
```

## API Endpoints

### Authentication

- `POST /api/auth/signup` - Create new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user info

### Galleries

- `GET /api/galleries` - List user's galleries
- `POST /api/galleries` - Create new gallery
- `GET /api/galleries/:id` - Get gallery details with images
- `PUT /api/galleries/:id` - Update gallery
- `DELETE /api/galleries/:id` - Delete gallery

### Images

- `POST /api/galleries/:id/images` - Upload images to gallery
- `GET /api/images/:id` - Get image file
- `DELETE /api/images/:id` - Delete image

## File Structure

```
/
├── public/
│   └── uploads/           # Uploaded images storage
├── src/
│   ├── components/        # React components
│   │   ├── Auth/
│   │   ├── Gallery/
│   │   ├── Image/
│   │   └── Layout/
│   ├── pages/            # Page components
│   ├── hooks/            # Custom React hooks
│   ├── utils/            # Helper functions
│   ├── api/              # API client functions
│   └── App.jsx
├── server/
│   ├── api/              # PHP API endpoints
│   ├── auth/             # Authentication logic
│   ├── db/               # Database connection and queries
│   └── uploads/          # Upload handling
└── database/
    └── schema.sql        # Database schema
```

## Out of Scope for MVP

- Video uploads
- User collaboration/sharing galleries
- Public URL sharing
- Advanced search functionality
- Image editing
- Tags and metadata
- Comments
- Themes/customization
- Social features

## Success Criteria

- Users can register and log in securely
- Users can create and manage galleries
- Users can upload images successfully
- Images are displayed properly in galleries
- UI is responsive and works on mobile devices
- Basic error handling is in place
