# my-galleries.com - MVP Specification

> **Note:** This specification defines the Minimum Viable Product (MVP) scope only. The `project-description.md` file represents the future vision with additional features like video support, collaboration, and public sharing, which are explicitly excluded from this MVP.

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

### Backend Dependencies (Composer)

```json
{
  "require": {
    "slim/slim": "^4.0",
    "slim/psr7": "^1.0",
    "firebase/php-jwt": "^6.0"
  }
}
```

**Note:** Use Slim's built-in router. No additional routing packages required.

## Server Requirements

### PHP Configuration
- **PHP Version:** 7.4 or higher
- **Required PHP Extensions:**
  - `pdo_sqlite` - SQLite database support
  - `gd` - Image processing for thumbnails
  - `mbstring` - Multibyte string handling

### Apache Configuration
- **Apache Modules:** 
  - `mod_rewrite` - URL rewriting for clean URLs
- **DocumentRoot:** Project root directory
- **.htaccess Files:**
  - `/public/.htaccess` - Frontend routing (SPA fallback to index.html)
  - `/server/.htaccess` - API routing (route all requests through PHP)

### File Permissions
- `/public/uploads/` - Must be writable by web server
- `/database/` - Must be writable by web server
- `/logs/` - Must be writable by web server, NOT web-accessible (deny via .htaccess)

## MVP Features

### 1. User Authentication

- Sign up with email and password
- Login/logout functionality
- Session management
- Top bar with user icon and dropdown menu
- Modal-based login/signup forms

#### Authentication Details

**JWT Configuration:**
- **Storage Location:** `localStorage` with key name `auth_token`
- **Token Expiration:** 24 hours from generation
- **Token Format:** Standard JWT with HS256 algorithm
- **Authorization Header:** `Authorization: Bearer {token}`

**Password Security:**
- **Hashing Algorithm:** PHP `password_hash()` with `PASSWORD_BCRYPT`
- **Minimum Length:** 8 characters
- **Complexity Requirements:** None for MVP (simple validation)

**Session Management:**
- **Multiple Sessions:** Allowed (users can have multiple active tokens)
- **Token Storage in DB:** Multiple tokens per user stored in `auth_tokens` field. The field is in JSON format containing list of objects containing a JWT token and its expiration date.
- **Logout Behavior:** Invalidates only the current token (removes it from `auth_tokens`)
- **Token Refresh:** Not implemented in MVP (users must re-login after 24 hours)

**Auth State Persistence:**
- Check `localStorage` for `auth_token` on app initialization
- Validate token by calling `GET /api/auth/me` endpoint
- Clear token and redirect to login if validation fails

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

#### File Upload Rules

**Size Limits:**
- **Max file size per image:** 10 MB
- **Max images per upload batch:** 20 files
- **Max total batch size:** 200 MB

**File Storage:**
- **Storage Path:** `/public/uploads/` (web-accessible directory)
- **Filename Format:** `{unix_timestamp}_{random_hash}.{original_extension}`
  - Example: `1697385600_a3f9c2b8e1d4.jpg`
  - Random hash: 12-character alphanumeric string
- **Original Filename:** Stored in database `original_filename` field for reference

**Thumbnail Generation:**
- **Automatic:** Yes, generated on upload
- **Dimensions:** Up to 300x300 pixels, preserve aspect ratio
- **Method:** maintain aspect ratio (center crop), width and height must be at most 300 pixels
- **Library:** PHP GD extension
- **Filename Format:** `thumb_{unix_timestamp}_{random_hash}.{original_extension}`
- **Storage Path:** Same as full images (`/public/uploads/`)

**Validation:**
- **File Type Check:** Both MIME type and file extension must match
- **Allowed MIME Types:** `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- **Allowed Extensions:** `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

**Error Handling:**
- **Failed Upload Behavior:** Rollback entire batch (delete all uploaded files from current batch)
- **Validation Errors:** Return 400 status with specific error message
- **Storage Errors:** Return 500 status with generic error message

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

## UI Behavior Specifications

### Gallery Display
- **Sorting Order:** Newest first (ORDER BY `created_at` DESC)
- **Grid Layout:** 
  - Desktop: 3 columns
  - Tablet: 2 columns
  - Mobile: 1 column
- **Gallery Cards:** Show gallery name, image count, and thumbnail (first image or placeholder)

### Image Display
- **Ordering:** Upload order (ORDER BY `uploaded_at` ASC)
- **Grid Layout:** Responsive grid with equal-sized thumbnails
- **Lightbox Navigation:** Previous/Next buttons, keyboard arrows, ESC to close

### Delete Behavior
- **Gallery Deletion:** 
  - Show confirmation dialog
  - Cascade delete all associated images from database
  - Delete all image files and thumbnails from filesystem
- **Image Deletion:** 
  - Show confirmation dialog
  - Delete image record from database
  - Delete image file and thumbnail from filesystem

### Loading States
- **Type:** Centered spinner overlay
- **Text:** "Loading..." below spinner
- **Use Cases:** API requests, image uploads, page transitions

### Empty States
- **No Galleries:** 
  - Centered message: "No galleries yet"
  - Icon: Photo icon from Heroicons
  - Call-to-action: "Create Your First Gallery" button
- **No Images in Gallery:** 
  - Centered message: "No images in this gallery"
  - Icon: Photo icon from Heroicons
  - Call-to-action: "Upload Images" button

### Authentication State
- **On App Load:** Check `localStorage` for `auth_token`
- **If Token Exists:** Validate with `GET /api/auth/me`
- **If Valid:** Redirect to dashboard
- **If Invalid:** Clear token, show login modal
- **Protected Routes:** Redirect to home with login modal if not authenticated

## Validation Rules

### User Input
- **Email:**
  - Format: Standard email validation using PHP `filter_var($email, FILTER_VALIDATE_EMAIL)`
  - Required field
  - Must be unique (check during signup)

- **Password:**
  - Minimum length: 8 characters
  - No maximum length limit
  - No complexity requirements for MVP
  - Required field

### Gallery Input
- **Gallery Name:**
  - Length: 1-100 characters
  - Allowed characters: Alphanumeric, spaces, and basic punctuation (`.`, `,`, `-`, `'`, `!`)
  - Required field
  - Pattern: `/^[a-zA-Z0-9\s.,\-'!]{1,100}$/`

- **Gallery Description:**
  - Length: 0-500 characters (optional)
  - No character restrictions
  - Optional field

### Image Files
- **File Type Validation:**
  - Check MIME type matches extension
  - Allowed combinations only:
    - `image/jpeg` with `.jpg` or `.jpeg`
    - `image/png` with `.png`
    - `image/gif` with `.gif`
    - `image/webp` with `.webp`
- **File Size:** Enforced at PHP and frontend levels
- **Filename Sanitization:** Server generates new filenames (no user input used)

## Database Schema

### Database Configuration
- **File Location:** `/database/galleries.db`
- **Foreign Keys:** ENABLED with CASCADE delete
- **Initial Data:** No seed data required
- **Connection:** PDO with SQLite driver

### Users Table

```SQL
- id (INTEGER PRIMARY KEY)
- email (TEXT UNIQUE)
- password_hash (TEXT)
- auth_tokens (TEXT) COMMENT 'JSON with list of objects, each has a JWT tokens and an expiration date'
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

## Logging & Debugging

### Overview
All API operations, errors, and critical events must be logged to facilitate debugging, monitoring, and troubleshooting. Logs are stored in structured JSON format for easy parsing and analysis.

### What to Log

**API Requests:**
- HTTP method (GET, POST, PUT, DELETE)
- Request path/endpoint
- User ID (if authenticated)
- IP address
- Timestamp (UTC)
- Request ID (unique identifier for each request)

**API Responses:**
- HTTP status code
- Response time/duration (in milliseconds)
- Request ID (for correlation)

**Authentication Events:**
- Login attempts (success and failure)
- Logout events
- Token generation
- Token validation (success and failure)
- User email (not password)

**File Upload Operations:**
- Gallery ID
- Number of files uploaded
- Total size of upload batch
- Individual filenames (server-generated, not original)
- Success or failure status
- Error details on failure

**Database Operations (Development):**
- Failed queries with error messages
- Cascade delete operations (gallery deletion with image count)
- Connection errors

**Errors and Exceptions:**
- Error message
- Error type/class
- Stack trace
- Request context (path, user, request ID)
- HTTP status code returned

**Validation Failures:**
- Field name
- Validation rule that failed
- Submitted value (sanitized, no sensitive data)

### Log Format

All logs must be written in structured JSON format with the following fields:

```json
{
  "timestamp": "2023-10-15T14:30:45.123Z",
  "level": "INFO",
  "type": "request",
  "request_id": "a3f9c2b8e1d4",
  "user_id": 5,
  "ip_address": "192.168.1.100",
  "message": "API request completed",
  "context": {
    "method": "POST",
    "path": "/api/galleries",
    "status_code": 201,
    "duration_ms": 45
  }
}
```

**Required Fields:**
- `timestamp` - ISO 8601 format in UTC
- `level` - Log level (DEBUG, INFO, WARNING, ERROR)
- `type` - Log type (request, auth, upload, error, database)
- `message` - Human-readable message
- `request_id` - Unique request identifier (12-character alphanumeric)

**Optional Fields:**
- `user_id` - Authenticated user ID
- `ip_address` - Client IP address
- `context` - Additional contextual data (object)
- `stack_trace` - Error stack trace (for ERROR level)

### Log Storage

**Directory Structure:**
- **Base Directory:** `/logs/`
- **Security:** Directory must NOT be web-accessible (protected by .htaccess)
- **Permissions:** Writable by web server process

**Log Files:**
- `api.log` - All API requests and responses
- `error.log` - Errors and exceptions only (level ERROR)
- `auth.log` - Authentication and authorization events
- `upload.log` - File upload operations

**Daily Rotation:**
- Logs are rotated monthly at every 1st day of a month at midnight UTC
- Filename format: `{type}-{YYYY-MM}.log`
- Example: `api-2023-10.log`, `error-2023-11.log`
- Current month logs use base filename (e.g., `api.log`)

**Retention:**
- Keep log files forever

### Log Levels

**DEBUG** (Development Only):
- Detailed debugging information
- Database queries
- Detailed request/response bodies
- Internal state changes

**INFO** (Default):
- General informational messages
- Successful API requests
- Authentication success
- File upload success
- Normal operations

**WARNING**:
- Non-critical issues
- Validation failures
- Authentication failures (failed login attempts)
- Rate limiting (if implemented)
- Deprecated API usage

**ERROR**:
- Critical errors
- Exceptions and stack traces
- Database connection failures
- File system errors
- Failed file uploads
- 500 Internal Server errors

### Environment-Specific Behavior

**Development:**
- Log level: DEBUG and above
- Output: Write to log files AND stdout/stderr
- Include sensitive data in context (for debugging)
- Include full stack traces
- Log all database queries

**Production:**
- Log level: INFO and above
- Output: Write to log files only
- Exclude sensitive data (passwords, full tokens, credit cards)
- Include stack traces only for ERROR level
- Do not log individual database queries

**Sensitive Data Exclusion:**
Never log the following in production:
- Passwords (plain or hashed)
- Full JWT tokens (log only first 10 chars: `eyJhbGciOi...`)
- Session tokens
- Credit card numbers (if added in future)
- Social security numbers or personal IDs

### Request ID Generation

**Format:**
- 12-character alphanumeric string
- Generated using: `substr(bin2hex(random_bytes(6)), 0, 12)`
- Unique per request

**Usage:**
- Generated at start of each API request
- Added to all log entries for that request
- Included in response headers: `X-Request-ID: a3f9c2b8e1d4`
- Included in error responses for traceability

**Example:**
```json
{
  "error": "Gallery not found",
  "details": "Gallery ID 123 does not exist",
  "request_id": "a3f9c2b8e1d4"
}
```

### Log Viewer

**Purpose:**
Simple web-based interface to view and filter logs for debugging

**Location:** `/server/logs/viewer.php`

**Features:**
- View recent log entries (last 100, 500, 1000)
- Filter by:
  - Date range
  - Log level (DEBUG, INFO, WARNING, ERROR)
  - Log type (request, auth, upload, error)
  - User ID
  - Request ID
- Search by keyword
- Display in readable format (formatted JSON)
- Refresh/reload capability

**Security:**
- Protected by authentication (requires valid JWT token)
- Only accessible to authenticated users
- Consider admin-only access in production

**Implementation:**
- Read log files from `/logs/` directory
- Parse JSON lines
- Apply filters
- Display in HTML table or formatted view
- Basic HTML/CSS interface (no React needed)

### Logger Implementation

**Logger Class/Functions:**

Create utility functions in `/server/utils/logger.php`:

```php
function logRequest($method, $path, $statusCode, $durationMs, $userId = null)
function logError($message, $context = [], $exception = null)
function logAuth($event, $email, $success, $userId = null)
function logUpload($galleryId, $fileCount, $totalSize, $success, $errorDetails = null)
function logDatabase($query, $success, $errorMessage = null)
```

**Each function:**
- Generates properly formatted JSON log entry
- Includes timestamp, level, type, request_id
- Writes to appropriate log file(s)
- Handles file rotation
- Uses file locking to prevent corruption

## Error Handling Standards

### HTTP Status Codes
- **200 OK** - Successful GET/PUT/DELETE request
- **201 Created** - Successful POST request (resource created)
- **400 Bad Request** - Validation errors, invalid input
- **401 Unauthorized** - Missing or invalid authentication token
- **404 Not Found** - Resource not found
- **500 Internal Server Error** - Server-side errors (database, filesystem, etc.)

### Error Response Format
All error responses must follow this JSON structure:

```json
{
  "error": "User-friendly error message",
  "details": "Technical details for debugging (optional in production)",
  "request_id": "a3f9c2b8e1d4"
}
```

**Required Fields:**
- `error` - User-friendly error message (always included)
- `request_id` - Unique request identifier for log correlation (always included)

**Optional Fields:**
- `details` - Technical details for debugging (included in development, optional in production)

**Examples:**

```json
{
  "error": "Invalid email or password",
  "details": "User not found in database",
  "request_id": "a3f9c2b8e1d4"
}
```

```json
{
  "error": "File size exceeds maximum limit of 10MB",
  "details": "Uploaded file size: 12.5MB",
  "request_id": "b7c4d8e2f9a1"
}
```

### Error Logging Requirements

**All errors must be logged before returning to client:**

1. **Log Level:**
   - Validation errors (400): WARNING level
   - Authentication errors (401): WARNING level
   - Not found errors (404): INFO level
   - Server errors (500): ERROR level

2. **Required Log Information:**
   - Error message (same as response)
   - HTTP status code
   - Request ID (for correlation)
   - Request path and method
   - User ID (if authenticated)
   - Stack trace (for 500 errors only)
   - Context (relevant data that led to error)

3. **Implementation:**
   - Call `logError()` function before returning error response
   - Include exception object if available
   - Sanitize sensitive data before logging
   - Ensure request_id in log matches response

### Frontend Error Handling
- **Display:** Show `error` field in toast notification or alert
- **Logging:** Log full response including `details` to browser console
- **User Feedback:** Always provide actionable feedback (e.g., "Please try again" or "Contact support")

## API Response Formats

### Authentication Endpoints

**POST /api/auth/signup**
```json
// Request
{
  "email": "user@example.com",
  "password": "password123"
}

// Response (201)
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "created_at": "2023-10-15T10:30:00Z"
  }
}
```

**POST /api/auth/login**
```json
// Request
{
  "email": "user@example.com",
  "password": "password123"
}

// Response (200)
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com"
  }
}
```

**POST /api/auth/logout**
```json
// Request (token in Authorization header)
{}

// Response (200)
{
  "message": "Logged out successfully"
}
```

**GET /api/auth/me**
```json
// Response (200)
{
  "id": 1,
  "email": "user@example.com",
  "created_at": "2023-10-15T10:30:00Z"
}
```

### Gallery Endpoints

**GET /api/galleries**
```json
// Response (200)
{
  "galleries": [
    {
      "id": 1,
      "name": "Summer Vacation",
      "description": "Photos from our trip",
      "image_count": 15,
      "created_at": "2023-10-15T10:30:00Z",
      "updated_at": "2023-10-16T14:20:00Z"
    }
  ]
}
```

**POST /api/galleries**
```json
// Request
{
  "name": "New Gallery",
  "description": "Optional description"
}

// Response (201)
{
  "id": 2,
  "name": "New Gallery",
  "description": "Optional description",
  "image_count": 0,
  "created_at": "2023-10-17T09:15:00Z",
  "updated_at": "2023-10-17T09:15:00Z"
}
```

**GET /api/galleries/:id**
```json
// Response (200)
{
  "id": 1,
  "name": "Summer Vacation",
  "description": "Photos from our trip",
  "created_at": "2023-10-15T10:30:00Z",
  "updated_at": "2023-10-16T14:20:00Z",
  "images": [
    {
      "id": 1,
      "filename": "1697385600_a3f9c2b8e1d4.jpg",
      "original_filename": "beach_photo.jpg",
      "thumbnail_filename": "thumb_1697385600_a3f9c2b8e1d4.jpg",
      "file_size": 2048576,
      "width": 1920,
      "height": 1080,
      "uploaded_at": "2023-10-15T11:00:00Z"
    }
  ]
}
```

**PUT /api/galleries/:id**
```json
// Request
{
  "name": "Updated Gallery Name",
  "description": "Updated description"
}

// Response (200)
{
  "id": 1,
  "name": "Updated Gallery Name",
  "description": "Updated description",
  "image_count": 15,
  "created_at": "2023-10-15T10:30:00Z",
  "updated_at": "2023-10-17T10:00:00Z"
}
```

**DELETE /api/galleries/:id**
```json
// Response (200)
{
  "message": "Gallery deleted successfully"
}
```

### Image Endpoints

**POST /api/galleries/:id/images**
```json
// Request: multipart/form-data with files[] array

// Response (201)
{
  "uploaded": [
    {
      "id": 5,
      "filename": "1697385600_a3f9c2b8e1d4.jpg",
      "original_filename": "photo1.jpg",
      "thumbnail_filename": "thumb_1697385600_a3f9c2b8e1d4.jpg",
      "file_size": 2048576,
      "width": 1920,
      "height": 1080,
      "uploaded_at": "2023-10-17T10:30:00Z"
    }
  ]
}
```

**GET /api/images/:id**
- Returns the actual image file (Content-Type: image/jpeg, image/png, etc.)
- Not JSON response

**DELETE /api/images/:id**
```json
// Response (200)
{
  "message": "Image deleted successfully"
}
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
