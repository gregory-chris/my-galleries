# my-galleries.com - MVP Development Plan

## Prerequisites: Environment Configuration Checklist

Before starting development, verify the following requirements:

- [x] **PHP Version:** 7.4 or higher installed
- [x] **Apache:** mod_rewrite module enabled
- [x] **PHP Extensions:**
  - [x] pdo_sqlite
  - [x] gd
  - [x] mbstring
- [x] **Composer:** Installed and available in PATH
- [x] **Node.js & npm:** For Vite and React development

## Milestone 1: Project Setup & Foundation

### Frontend Setup
- [x] Initialize Vite + React project with JavaScript (not TypeScript)
- [x] Configure Tailwind CSS with custom configuration
- [x] Install dependencies:
  - [x] @heroicons/react
  - [x] React Router DOM for routing
- [x] Configure Google Fonts (add to index.html)
- [x] Create base directory structure: src/components, src/pages, src/hooks, src/utils, src/api

### Backend Setup
- [x] Create directory structure:
  - [x] `/server/api/` - API endpoints
  - [x] `/server/auth/` - Authentication logic
  - [x] `/server/db/` - Database connection and queries
  - [x] `/server/uploads/` - Upload handling
  - [x] `/database/` - Database file storage
  - [x] `/public/uploads/` - Uploaded images storage
- [x] Create composer.json with dependencies:
  - [x] slim/slim: ^4.0
  - [x] slim/psr7: ^1.0
  - [x] firebase/php-jwt: ^6.0
- [x] Run `composer install`
- [x] Set up writable permissions for `/public/uploads/` and `/database/`

### .htaccess Configuration
- [x] Create `/public/.htaccess` for frontend routing (SPA fallback)
- [x] Create `/server/.htaccess` for API routing (route to PHP)

### Database Setup
- [x] Create `/database/schema.sql` with all table definitions
- [x] Create database initialization script
- [x] Generate `/database/galleries.db` with schema
- [x] Enable foreign key constraints with CASCADE delete
- [x] Test database connection with PDO

### PHP Configuration Check
- [x] Create PHP script to verify GD extension is available
- [x] Verify SQLite3 PDO driver is available
- [x] Verify mbstring extension is available

### Logging Infrastructure Setup
- [x] Create `/logs/` directory with writable permissions
- [x] Create `.htaccess` in `/logs/` directory:
  - [x] Deny all web access: `Deny from all`
  - [x] Prevent directory listing
- [x] Create `/server/utils/logger.php` with logging functions:
  - [x] `logRequest($method, $path, $statusCode, $durationMs, $userId = null)`
  - [x] `logError($message, $context = [], $exception = null)`
  - [x] `logAuth($event, $email, $success, $userId = null)`
  - [x] `logUpload($galleryId, $fileCount, $totalSize, $success, $errorDetails = null)`
  - [x] `logDatabase($query, $success, $errorMessage = null)` (development only)
- [x] Implement JSON log formatting:
  - [x] Include timestamp (UTC, ISO 8601 format)
  - [x] Include log level (DEBUG, INFO, WARNING, ERROR)
  - [x] Include type (request, auth, upload, error, database)
  - [x] Include request_id (12-char alphanumeric)
  - [x] Include optional fields: user_id, ip_address, context, stack_trace
- [x] Implement file writing with locking:
  - [x] Use `file_put_contents()` with `FILE_APPEND | LOCK_EX`
  - [x] Write to appropriate log file based on type
  - [x] Write ERROR level also to `error.log`
- [x] Implement daily log rotation:
  - [x] Check if current log file is from previous day
  - [x] Rename to `{type}-{YYYY-MM-DD}.log` format
  - [x] Create new current day log file
- [x] Implement log cleanup (30-day retention):
  - [x] Delete log files older than 30 days
  - [x] Run on first request of each day
- [x] Create `/server/logs/viewer.php`:
  - [x] Authenticate user with JWT before displaying logs
  - [x] Read and parse JSON log files
  - [x] Display in HTML table with formatting
  - [x] Add filters: date, level, type, user_id, request_id
  - [x] Add search functionality
  - [x] Show last 100/500/1000 entries options
  - [x] Basic CSS styling for readability

## Milestone 2: User Authentication

### Request Logging Middleware
- [x] Create Slim middleware for request logging:
  - [x] Generate unique request_id at start of request: `substr(bin2hex(random_bytes(6)), 0, 12)`
  - [x] Store request_id in request attribute for access by other handlers
  - [x] Capture request start time with `microtime(true)`
  - [x] Add request_id to response headers: `X-Request-ID`
- [x] Log request after response is sent:
  - [x] Calculate duration: `(microtime(true) - $startTime) * 1000` (milliseconds)
  - [x] Extract user ID from authenticated request (if available)
  - [x] Get client IP address: `$_SERVER['REMOTE_ADDR']`
  - [x] Call `logRequest()` with all details
  - [x] Log to `api.log` at INFO level
- [x] Add request_id to all error responses:
  - [x] Include in error JSON: `"request_id": $requestId`
  - [x] Ensure consistency between response and logs
- [x] Register middleware in Slim app (before routing)

### Backend Authentication
- [x] Create JWT helper functions using firebase/php-jwt:
  - [x] Generate JWT with 24-hour expiration (HS256 algorithm)
  - [x] Verify and decode JWT
  - [x] Extract user ID from token
- [x] Implement password hashing with PASSWORD_BCRYPT:
  - [x] Hash password on signup: `password_hash($password, PASSWORD_BCRYPT)`
  - [x] Verify password on login: `password_verify($password, $hash)`
- [x] Create authentication middleware:
  - [x] Extract token from `Authorization: Bearer {token}` header
  - [x] Validate token and attach user to request
  - [x] Return 401 for invalid/missing tokens
  - [x] Log authentication failures with `logAuth()` (WARNING level)
- [x] Implement API endpoints:
  - [x] POST /api/auth/signup - Email validation, password min 8 chars, store tokens in JSON format
  - [x] POST /api/auth/login - Return JWT token and user object
  - [x] POST /api/auth/logout - Remove current token from auth_tokens field
  - [x] GET /api/auth/me - Return current user info (requires auth middleware)
- [x] Add authentication event logging:
  - [x] Log successful signup: `logAuth('signup', $email, true, $userId)` at INFO level
  - [x] Log failed signup: `logAuth('signup', $email, false)` at WARNING level
  - [x] Log successful login: `logAuth('login', $email, true, $userId)` at INFO level
  - [x] Log failed login: `logAuth('login', $email, false)` at WARNING level
  - [x] Log logout: `logAuth('logout', $email, true, $userId)` at INFO level
  - [x] Log token validation failures: `logAuth('token_validation', null, false)` at WARNING level
  - [x] Write auth logs to `auth.log`

### Frontend Authentication
- [x] Create auth context/provider for React
- [x] Implement localStorage token management:
  - [x] Save token with key 'auth_token'
  - [x] Load token on app initialization
  - [x] Clear token on logout or 401 response
- [x] Create API client with Authorization header:
  - [x] Add `Authorization: Bearer {token}` to all authenticated requests
  - [x] Handle 401 responses (clear token, redirect to login)
- [x] Build UI components:
  - [x] Login modal with email and password fields
  - [x] Signup modal with email and password fields
  - [x] Form validation (email format, password min 8 chars)
  - [x] Error message display
- [x] Create top navigation bar:
  - [x] User icon/avatar button
  - [x] Dropdown menu with logout option
  - [x] Show login/signup button when not authenticated
- [x] Implement protected routes:
  - [x] Check auth state before rendering protected pages
  - [x] Redirect to home with login modal if not authenticated

## Milestone 3: Gallery Management - Backend

- [x] Implement validation functions:
  - [x] Gallery name: 1-100 chars, pattern `/^[a-zA-Z0-9\s.,\-'!]{1,100}$/`
  - [x] Gallery description: 0-500 chars (optional)
  - [x] Log validation failures at WARNING level with field name and reason
- [x] Create database query functions:
  - [x] Get all galleries for user (ORDER BY created_at DESC)
  - [x] Get gallery by ID with user authorization check
  - [x] Create gallery (insert with UTC timestamps)
  - [x] Update gallery (update name, description, updated_at)
  - [x] Delete gallery (CASCADE delete images, return affected rows)
  - [x] Get image count for gallery
- [x] Add database error logging:
  - [x] Wrap all queries in try-catch blocks
  - [x] Log failed queries with `logDatabase()` or `logError()` at ERROR level
  - [x] Include query type, error message, and context
  - [x] Log cascade deletes at INFO level with image count
- [x] Implement API endpoints with auth middleware:
  - [x] GET /api/galleries - List user's galleries with image_count
  - [x] POST /api/galleries - Create gallery, return 201 with full object
  - [x] GET /api/galleries/:id - Get gallery with images array (404 if not found/not owned)
  - [x] PUT /api/galleries/:id - Update gallery (404 if not found/not owned)
  - [x] DELETE /api/galleries/:id - Delete gallery and all images/files (404 if not found/not owned)
- [x] Add authorization checks:
  - [x] Verify gallery belongs to authenticated user
  - [x] Return 404 (not 403) for galleries user doesn't own (security best practice)
  - [x] Log authorization failures at INFO level (404 responses)
- [x] Add error logging to all endpoints:
  - [x] Call `logError()` before returning error responses
  - [x] Include request_id in error response
  - [x] Log validation errors at WARNING level
  - [x] Log not found errors at INFO level
  - [x] Log server errors at ERROR level with stack trace

## Milestone 4: Gallery Management - Frontend

- [x] Create API client functions for galleries:
  - [x] fetchGalleries()
  - [x] createGallery(name, description)
  - [x] updateGallery(id, name, description)
  - [x] deleteGallery(id)
  - [x] fetchGallery(id)
- [x] Build dashboard/home page:
  - [x] Responsive grid layout (3 cols desktop, 2 tablet, 1 mobile)
  - [x] Gallery cards with name, image count, thumbnail (first image or placeholder)
  - [x] "Create Gallery" button
  - [x] Empty state: "No galleries yet" with icon and CTA button
  - [x] Loading state: centered spinner with "Loading..." text
- [x] Create gallery creation modal:
  - [x] Name input (required, 1-100 chars validation)
  - [x] Description textarea (optional, 0-500 chars)
  - [x] Form validation with error messages
  - [x] Submit button (disabled during API call)
  - [x] Close on successful creation
- [x] Create gallery edit modal:
  - [x] Pre-populate with existing name and description
  - [x] Same validation as creation
  - [x] Update gallery on submit
- [x] Implement gallery deletion:
  - [x] Confirmation dialog: "Are you sure? This will delete all images."
  - [x] Call DELETE endpoint
  - [x] Remove from local state on success
  - [x] Show error toast on failure
- [x] Add error handling:
  - [x] Toast/alert for errors (show error.error field)
  - [x] Console log full error response (error.details)

## Milestone 5: Image Upload System

### Backend Upload Handler
- [x] Create file validation functions:
  - [x] Check file size (max 10MB per file)
  - [x] Check batch size (max 20 files, max 200MB total)
  - [x] Validate MIME type matches extension
  - [x] Allowed: image/jpeg (.jpg/.jpeg), image/png (.png), image/gif (.gif), image/webp (.webp)
  - [x] Log validation failures at WARNING level with details
- [x] Implement filename generation:
  - [x] Format: `{unix_timestamp}_{12_char_random_hash}.{extension}`
  - [x] Use `time()` for timestamp
  - [x] Generate random hash: `substr(bin2hex(random_bytes(6)), 0, 12)`
- [x] Create thumbnail generation with GD:
  - [x] Load image based on MIME type (imagecreatefromjpeg, imagecreatefrompng, etc.)
  - [x] Calculate dimensions for up to 300x300, preserve aspect ratio
  - [x] Create new thumbnail image (max 300x300)
  - [x] Copy and resample (maintain aspect ratio, center crop)
  - [x] Save as `thumb_{timestamp}_{hash}.{extension}`
  - [x] Free memory with imagedestroy()
  - [x] Log thumbnail generation failures at ERROR level
- [x] Implement batch upload with rollback:
  - [x] Track all uploaded files in array
  - [x] If any file fails validation/processing:
    - [x] Delete all files from current batch
    - [x] Delete all thumbnails from current batch
    - [x] Log failed upload with `logUpload()` at ERROR level
    - [x] Return 400 error with details
  - [x] On success: insert all image records to database
  - [x] Log successful upload with `logUpload()` at INFO level
- [x] Get image dimensions:
  - [x] Use getimagesize() to get width and height
  - [x] Store in database
- [x] Implement POST /api/galleries/:id/images endpoint:
  - [x] Verify gallery exists and belongs to user (404 if not)
  - [x] Handle multipart/form-data with files[] array
  - [x] Return 201 with uploaded array of image objects
- [x] Add upload operation logging:
  - [x] Log start of upload: gallery ID, file count, total size at INFO level
  - [x] Log each successful file upload to `upload.log`
  - [x] Log failed uploads with error details to `upload.log` and `error.log`
  - [x] Include filenames (server-generated only, not original)
  - [x] Log rollback operations when batch fails

### Frontend Upload UI
- [x] Create file upload component:
  - [x] Drag-and-drop zone
  - [x] Click to browse file selector
  - [x] Show file previews before upload
  - [x] Display file names and sizes
  - [x] Allow removing files before upload
- [x] Implement client-side validation:
  - [x] Check file types (jpg, jpeg, png, gif, webp)
  - [x] Check individual file size (10MB)
  - [x] Check total files (20 max)
  - [x] Check total size (200MB)
  - [x] Show validation errors
- [x] Add upload progress:
  - [x] Progress bar showing upload percentage
  - [x] "Uploading X of Y files..." text
  - [x] Disable upload button during upload
- [x] Handle upload response:
  - [x] Show success message with count
  - [x] Add uploaded images to gallery view
  - [x] Clear upload form
  - [x] Show error toast on failure
- [x] Create image preview thumbnails after upload

## Milestone 6: Gallery & Image Viewing

### Gallery Detail Page
- [x] Create gallery detail page component:
  - [x] Display gallery name and description
  - [x] Show "Edit Gallery" and "Delete Gallery" buttons
  - [x] Show "Upload Images" button
  - [x] Display image count
- [x] Implement image grid:
  - [x] Responsive grid layout (equal-sized thumbnails)
  - [x] Display thumbnail_filename for each image
  - [x] Show images in upload order (ORDER BY uploaded_at ASC)
  - [x] Click thumbnail to open lightbox
- [x] Add empty state:
  - [x] "No images in this gallery" message
  - [x] Photo icon from Heroicons
  - [x] "Upload Images" CTA button
- [x] Add loading state while fetching gallery data

### Image Lightbox/Viewer
- [x] Create lightbox component:
  - [x] Full-screen overlay with dark background
  - [x] Display full-size image (filename, not thumbnail)
  - [x] Previous/Next navigation buttons
  - [x] Close button (X in corner)
  - [x] ESC key to close
  - [x] Arrow keys for navigation (left/right)
- [x] Add image navigation:
  - [x] Disable Previous on first image
  - [x] Disable Next on last image
  - [x] Show current position (e.g., "3 of 15")
- [x] Add delete button in lightbox:
  - [x] Show confirmation dialog
  - [x] Call DELETE /api/images/:id
  - [x] Remove from gallery view and close lightbox
  - [x] Show error on failure

### Image Deletion
- [x] Implement DELETE /api/images/:id endpoint:
  - [x] Verify image exists and belongs to user's gallery (404 if not)
  - [x] Delete image file from filesystem
  - [x] Delete thumbnail from filesystem
  - [x] Delete image record from database
  - [x] Return 200 with success message
  - [x] Log successful deletion at INFO level with image ID and gallery ID
  - [x] Log failed deletion at ERROR level with details
- [x] Add file deletion logging:
  - [x] Log file deletion attempts (success and failure)
  - [x] Log filesystem errors (file not found, permission denied)
  - [x] Include image filename and gallery context
- [x] Add confirmation dialog in frontend:
  - [x] "Are you sure you want to delete this image?"
  - [x] Cancel and Delete buttons

## Milestone 7: Polish & Responsive Design

### Responsive Design
- [x] Test and fix mobile layout (< 768px):
  - [x] Gallery grid: 1 column
  - [x] Navigation bar: mobile-friendly
  - [x] Modals: full-width on mobile
  - [x] Image lightbox: touch-friendly navigation
  - [x] Upload zone: adjust size for mobile
- [x] Test and fix tablet layout (768px - 1024px):
  - [x] Gallery grid: 2 columns
  - [x] Navigation: optimize spacing
- [x] Test desktop layout (> 1024px):
  - [x] Gallery grid: 3 columns
  - [x] Ensure proper max-width constraints

### Loading States
- [x] Add spinners for all async operations:
  - [x] Centered spinner with "Loading..." text
  - [x] Gallery list loading
  - [x] Gallery detail loading
  - [x] Image upload in progress
  - [x] Authentication checks
- [x] Add smooth transitions:
  - [x] Page transitions
  - [x] Modal open/close animations
  - [x] Button hover effects
  - [x] Image hover effects

### Error Handling
- [x] Implement React error boundary:
  - [x] Catch rendering errors
  - [x] Show user-friendly error page
  - [x] Log errors to console
- [x] Consistent error messaging:
  - [x] Toast notifications for API errors
  - [x] Display error.error field to users
  - [x] Log error.details to console
  - [x] Provide actionable feedback
- [x] Handle edge cases:
  - [x] Network failures
  - [x] Token expiration (redirect to login)
  - [x] 404 errors (show not found page)
  - [x] File upload failures (rollback UI state)

### Testing & Bug Fixes
- [x] Test all user flows:
  - [x] Complete signup/login/logout flow
  - [x] Create, edit, delete gallery
  - [x] Upload images (single and batch)
  - [x] View images in lightbox
  - [x] Delete images
  - [x] Navigation between pages
- [x] Cross-browser testing:
  - [x] Chrome
  - [x] Firefox
  - [x] Safari
- [x] Fix any identified bugs

