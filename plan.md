# my-galleries.com - MVP Development Plan

## Prerequisites: Environment Configuration Checklist

Before starting development, verify the following requirements:

- [ ] **PHP Version:** 7.4 or higher installed
- [ ] **Apache:** mod_rewrite module enabled
- [ ] **PHP Extensions:**
  - [ ] pdo_sqlite
  - [ ] gd
  - [ ] mbstring
- [ ] **Composer:** Installed and available in PATH
- [ ] **Node.js & npm:** For Vite and React development

## Milestone 1: Project Setup & Foundation

### Frontend Setup
- [ ] Initialize Vite + React project with JavaScript (not TypeScript)
- [ ] Configure Tailwind CSS with custom configuration
- [ ] Install dependencies:
  - [ ] @heroicons/react
  - [ ] React Router DOM for routing
- [ ] Configure Google Fonts (add to index.html)
- [ ] Create base directory structure: src/components, src/pages, src/hooks, src/utils, src/api

### Backend Setup
- [ ] Create directory structure:
  - [ ] `/server/api/` - API endpoints
  - [ ] `/server/auth/` - Authentication logic
  - [ ] `/server/db/` - Database connection and queries
  - [ ] `/server/uploads/` - Upload handling
  - [ ] `/database/` - Database file storage
  - [ ] `/public/uploads/` - Uploaded images storage
- [ ] Create composer.json with dependencies:
  - [ ] slim/slim: ^4.0
  - [ ] slim/psr7: ^1.0
  - [ ] firebase/php-jwt: ^6.0
- [ ] Run `composer install`
- [ ] Set up writable permissions for `/public/uploads/` and `/database/`

### .htaccess Configuration
- [ ] Create `/public/.htaccess` for frontend routing (SPA fallback)
- [ ] Create `/server/.htaccess` for API routing (route to PHP)

### Database Setup
- [ ] Create `/database/schema.sql` with all table definitions
- [ ] Create database initialization script
- [ ] Generate `/database/galleries.db` with schema
- [ ] Enable foreign key constraints with CASCADE delete
- [ ] Test database connection with PDO

### PHP Configuration Check
- [ ] Create PHP script to verify GD extension is available
- [ ] Verify SQLite3 PDO driver is available
- [ ] Verify mbstring extension is available

## Milestone 2: User Authentication

### Backend Authentication
- [ ] Create JWT helper functions using firebase/php-jwt:
  - [ ] Generate JWT with 24-hour expiration (HS256 algorithm)
  - [ ] Verify and decode JWT
  - [ ] Extract user ID from token
- [ ] Implement password hashing with PASSWORD_BCRYPT:
  - [ ] Hash password on signup: `password_hash($password, PASSWORD_BCRYPT)`
  - [ ] Verify password on login: `password_verify($password, $hash)`
- [ ] Create authentication middleware:
  - [ ] Extract token from `Authorization: Bearer {token}` header
  - [ ] Validate token and attach user to request
  - [ ] Return 401 for invalid/missing tokens
- [ ] Implement API endpoints:
  - [ ] POST /api/auth/signup - Email validation, password min 8 chars, store semicolon-separated tokens
  - [ ] POST /api/auth/login - Return JWT token and user object
  - [ ] POST /api/auth/logout - Remove current token from auth_tokens field
  - [ ] GET /api/auth/me - Return current user info (requires auth middleware)

### Frontend Authentication
- [ ] Create auth context/provider for React
- [ ] Implement localStorage token management:
  - [ ] Save token with key 'auth_token'
  - [ ] Load token on app initialization
  - [ ] Clear token on logout or 401 response
- [ ] Create API client with Authorization header:
  - [ ] Add `Authorization: Bearer {token}` to all authenticated requests
  - [ ] Handle 401 responses (clear token, redirect to login)
- [ ] Build UI components:
  - [ ] Login modal with email and password fields
  - [ ] Signup modal with email and password fields
  - [ ] Form validation (email format, password min 8 chars)
  - [ ] Error message display
- [ ] Create top navigation bar:
  - [ ] User icon/avatar button
  - [ ] Dropdown menu with logout option
  - [ ] Show login/signup button when not authenticated
- [ ] Implement protected routes:
  - [ ] Check auth state before rendering protected pages
  - [ ] Redirect to home with login modal if not authenticated

## Milestone 3: Gallery Management - Backend

- [ ] Implement validation functions:
  - [ ] Gallery name: 1-100 chars, pattern `/^[a-zA-Z0-9\s.,\-'!]{1,100}$/`
  - [ ] Gallery description: 0-500 chars (optional)
- [ ] Create database query functions:
  - [ ] Get all galleries for user (ORDER BY created_at DESC)
  - [ ] Get gallery by ID with user authorization check
  - [ ] Create gallery (insert with UTC timestamps)
  - [ ] Update gallery (update name, description, updated_at)
  - [ ] Delete gallery (CASCADE delete images, return affected rows)
  - [ ] Get image count for gallery
- [ ] Implement API endpoints with auth middleware:
  - [ ] GET /api/galleries - List user's galleries with image_count
  - [ ] POST /api/galleries - Create gallery, return 201 with full object
  - [ ] GET /api/galleries/:id - Get gallery with images array (404 if not found/not owned)
  - [ ] PUT /api/galleries/:id - Update gallery (404 if not found/not owned)
  - [ ] DELETE /api/galleries/:id - Delete gallery and all images/files (404 if not found/not owned)
- [ ] Add authorization checks:
  - [ ] Verify gallery belongs to authenticated user
  - [ ] Return 404 (not 403) for galleries user doesn't own (security best practice)

## Milestone 4: Gallery Management - Frontend

- [ ] Create API client functions for galleries:
  - [ ] fetchGalleries()
  - [ ] createGallery(name, description)
  - [ ] updateGallery(id, name, description)
  - [ ] deleteGallery(id)
  - [ ] fetchGallery(id)
- [ ] Build dashboard/home page:
  - [ ] Responsive grid layout (3 cols desktop, 2 tablet, 1 mobile)
  - [ ] Gallery cards with name, image count, thumbnail (first image or placeholder)
  - [ ] "Create Gallery" button
  - [ ] Empty state: "No galleries yet" with icon and CTA button
  - [ ] Loading state: centered spinner with "Loading..." text
- [ ] Create gallery creation modal:
  - [ ] Name input (required, 1-100 chars validation)
  - [ ] Description textarea (optional, 0-500 chars)
  - [ ] Form validation with error messages
  - [ ] Submit button (disabled during API call)
  - [ ] Close on successful creation
- [ ] Create gallery edit modal:
  - [ ] Pre-populate with existing name and description
  - [ ] Same validation as creation
  - [ ] Update gallery on submit
- [ ] Implement gallery deletion:
  - [ ] Confirmation dialog: "Are you sure? This will delete all images."
  - [ ] Call DELETE endpoint
  - [ ] Remove from local state on success
  - [ ] Show error toast on failure
- [ ] Add error handling:
  - [ ] Toast/alert for errors (show error.error field)
  - [ ] Console log full error response (error.details)

## Milestone 5: Image Upload System

### Backend Upload Handler
- [ ] Create file validation functions:
  - [ ] Check file size (max 10MB per file)
  - [ ] Check batch size (max 20 files, max 100MB total)
  - [ ] Validate MIME type matches extension
  - [ ] Allowed: image/jpeg (.jpg/.jpeg), image/png (.png), image/gif (.gif), image/webp (.webp)
- [ ] Implement filename generation:
  - [ ] Format: `{unix_timestamp}_{12_char_random_hash}.{extension}`
  - [ ] Use `time()` for timestamp
  - [ ] Generate random hash: `substr(bin2hex(random_bytes(6)), 0, 12)`
- [ ] Create thumbnail generation with GD:
  - [ ] Load image based on MIME type (imagecreatefromjpeg, imagecreatefrompng, etc.)
  - [ ] Calculate center crop dimensions for 300x300 square
  - [ ] Create new 300x300 image
  - [ ] Copy and resample (crop to center, maintain aspect)
  - [ ] Save as `thumb_{timestamp}_{hash}.{extension}`
  - [ ] Free memory with imagedestroy()
- [ ] Implement batch upload with rollback:
  - [ ] Track all uploaded files in array
  - [ ] If any file fails validation/processing:
    - [ ] Delete all files from current batch
    - [ ] Delete all thumbnails from current batch
    - [ ] Return 400 error with details
  - [ ] On success: insert all image records to database
- [ ] Get image dimensions:
  - [ ] Use getimagesize() to get width and height
  - [ ] Store in database
- [ ] Implement POST /api/galleries/:id/images endpoint:
  - [ ] Verify gallery exists and belongs to user (404 if not)
  - [ ] Handle multipart/form-data with files[] array
  - [ ] Return 201 with uploaded array of image objects

### Frontend Upload UI
- [ ] Create file upload component:
  - [ ] Drag-and-drop zone
  - [ ] Click to browse file selector
  - [ ] Show file previews before upload
  - [ ] Display file names and sizes
  - [ ] Allow removing files before upload
- [ ] Implement client-side validation:
  - [ ] Check file types (jpg, jpeg, png, gif, webp)
  - [ ] Check individual file size (10MB)
  - [ ] Check total files (20 max)
  - [ ] Check total size (100MB)
  - [ ] Show validation errors
- [ ] Add upload progress:
  - [ ] Progress bar showing upload percentage
  - [ ] "Uploading X of Y files..." text
  - [ ] Disable upload button during upload
- [ ] Handle upload response:
  - [ ] Show success message with count
  - [ ] Add uploaded images to gallery view
  - [ ] Clear upload form
  - [ ] Show error toast on failure
- [ ] Create image preview thumbnails after upload

## Milestone 6: Gallery & Image Viewing

### Gallery Detail Page
- [ ] Create gallery detail page component:
  - [ ] Display gallery name and description
  - [ ] Show "Edit Gallery" and "Delete Gallery" buttons
  - [ ] Show "Upload Images" button
  - [ ] Display image count
- [ ] Implement image grid:
  - [ ] Responsive grid layout (equal-sized thumbnails)
  - [ ] Display thumbnail_filename for each image
  - [ ] Show images in upload order (ORDER BY uploaded_at ASC)
  - [ ] Click thumbnail to open lightbox
- [ ] Add empty state:
  - [ ] "No images in this gallery" message
  - [ ] Photo icon from Heroicons
  - [ ] "Upload Images" CTA button
- [ ] Add loading state while fetching gallery data

### Image Lightbox/Viewer
- [ ] Create lightbox component:
  - [ ] Full-screen overlay with dark background
  - [ ] Display full-size image (filename, not thumbnail)
  - [ ] Previous/Next navigation buttons
  - [ ] Close button (X in corner)
  - [ ] ESC key to close
  - [ ] Arrow keys for navigation (left/right)
- [ ] Add image navigation:
  - [ ] Disable Previous on first image
  - [ ] Disable Next on last image
  - [ ] Show current position (e.g., "3 of 15")
- [ ] Add delete button in lightbox:
  - [ ] Show confirmation dialog
  - [ ] Call DELETE /api/images/:id
  - [ ] Remove from gallery view and close lightbox
  - [ ] Show error on failure

### Image Deletion
- [ ] Implement DELETE /api/images/:id endpoint:
  - [ ] Verify image exists and belongs to user's gallery (404 if not)
  - [ ] Delete image file from filesystem
  - [ ] Delete thumbnail from filesystem
  - [ ] Delete image record from database
  - [ ] Return 200 with success message
- [ ] Add confirmation dialog in frontend:
  - [ ] "Are you sure you want to delete this image?"
  - [ ] Cancel and Delete buttons

## Milestone 7: Polish & Responsive Design

### Responsive Design
- [ ] Test and fix mobile layout (< 768px):
  - [ ] Gallery grid: 1 column
  - [ ] Navigation bar: mobile-friendly
  - [ ] Modals: full-width on mobile
  - [ ] Image lightbox: touch-friendly navigation
  - [ ] Upload zone: adjust size for mobile
- [ ] Test and fix tablet layout (768px - 1024px):
  - [ ] Gallery grid: 2 columns
  - [ ] Navigation: optimize spacing
- [ ] Test desktop layout (> 1024px):
  - [ ] Gallery grid: 3 columns
  - [ ] Ensure proper max-width constraints

### Loading States
- [ ] Add spinners for all async operations:
  - [ ] Centered spinner with "Loading..." text
  - [ ] Gallery list loading
  - [ ] Gallery detail loading
  - [ ] Image upload in progress
  - [ ] Authentication checks
- [ ] Add smooth transitions:
  - [ ] Page transitions
  - [ ] Modal open/close animations
  - [ ] Button hover effects
  - [ ] Image hover effects

### Error Handling
- [ ] Implement React error boundary:
  - [ ] Catch rendering errors
  - [ ] Show user-friendly error page
  - [ ] Log errors to console
- [ ] Consistent error messaging:
  - [ ] Toast notifications for API errors
  - [ ] Display error.error field to users
  - [ ] Log error.details to console
  - [ ] Provide actionable feedback
- [ ] Handle edge cases:
  - [ ] Network failures
  - [ ] Token expiration (redirect to login)
  - [ ] 404 errors (show not found page)
  - [ ] File upload failures (rollback UI state)

### Testing & Bug Fixes
- [ ] Test all user flows:
  - [ ] Complete signup/login/logout flow
  - [ ] Create, edit, delete gallery
  - [ ] Upload images (single and batch)
  - [ ] View images in lightbox
  - [ ] Delete images
  - [ ] Navigation between pages
- [ ] Cross-browser testing:
  - [ ] Chrome
  - [ ] Firefox
  - [ ] Safari
- [ ] Fix any identified bugs

## Milestone 8: Deployment Preparation

### Performance Optimization
- [ ] Optimize frontend build:
  - [ ] Run `npm run build` to create production bundle
  - [ ] Verify bundle size is reasonable
  - [ ] Test production build locally
- [ ] Image optimization:
  - [ ] Verify thumbnail generation works efficiently
  - [ ] Check memory usage during batch uploads
  - [ ] Test with large images (near 10MB limit)

### Production Configuration
- [ ] Create production .env or config file:
  - [ ] Database path (absolute or relative)
  - [ ] JWT secret key (generate strong random key)
  - [ ] Upload directory path
  - [ ] API base URL
- [ ] Configure Apache for production:
  - [ ] Update .htaccess files if needed
  - [ ] Set proper file permissions
  - [ ] Enable production PHP settings (error reporting off)

### End-to-End Testing
- [ ] Run complete feature tests:
  - [ ] User signup with new email
  - [ ] User login with correct credentials
  - [ ] User login with incorrect credentials (should fail)
  - [ ] Create multiple galleries
  - [ ] Edit gallery details
  - [ ] Delete empty gallery
  - [ ] Upload single image
  - [ ] Upload multiple images (batch)
  - [ ] View images in lightbox
  - [ ] Navigate between images in lightbox
  - [ ] Delete single image
  - [ ] Delete gallery with images (cascade delete)
  - [ ] Logout and login again (token persistence)

### Validation Testing
- [ ] Test upload limits:
  - [ ] Upload file larger than 10MB (should fail with error)
  - [ ] Upload 21 images at once (should fail with error)
  - [ ] Upload batch over 100MB total (should fail with error)
  - [ ] Upload invalid file type (.txt, .pdf) (should fail with error)
- [ ] Test authentication:
  - [ ] Access protected API without token (should return 401)
  - [ ] Access protected pages without login (should redirect)
  - [ ] Token expiration after 24 hours (should require re-login)
- [ ] Test authorization:
  - [ ] Try to access another user's gallery (should return 404)
  - [ ] Try to delete another user's image (should return 404)
- [ ] Test validation rules:
  - [ ] Gallery name with 101 characters (should fail)
  - [ ] Gallery name with invalid characters (should fail)
  - [ ] Password with less than 8 characters (should fail)
  - [ ] Invalid email format (should fail)

### Documentation
- [ ] Create or update README.md:
  - [ ] Installation instructions
  - [ ] Prerequisites (PHP, Apache, Composer)
  - [ ] Database setup steps
  - [ ] Configuration instructions
  - [ ] How to run development server
  - [ ] How to build for production
- [ ] Document API endpoints (reference spec.md)
- [ ] Document known limitations or issues
