/**
 * API Client Utility
 * Handles HTTP requests to backend API with authentication
 */

const API_BASE_URL = 'http://localhost:8000/api';

/**
 * Get authentication token from localStorage
 */
function getAuthToken() {
  return localStorage.getItem('auth_token');
}

/**
 * Make authenticated API request
 */
async function apiRequest(endpoint, options = {}) {
  const token = getAuthToken();
  
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };
  
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  
  const config = {
    ...options,
    headers,
  };
  
  const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
  
  const data = await response.json();
  
  if (!response.ok) {
    // Log error details to console
    console.error('API Error:', {
      status: response.status,
      error: data.error,
      details: data.details,
      request_id: data.request_id,
    });
    
    // Throw error with user-friendly message
    const error = new Error(data.error || 'Request failed');
    error.status = response.status;
    error.requestId = data.request_id;
    error.details = data.details;
    throw error;
  }
  
  return data;
}

/**
 * Authentication API
 */
export const auth = {
  /**
   * Sign up new user
   */
  async signup(email, password) {
    const data = await apiRequest('/auth/signup', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    
    // Store token
    localStorage.setItem('auth_token', data.token);
    
    return data;
  },
  
  /**
   * Login user
   */
  async login(email, password) {
    const data = await apiRequest('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    
    // Store token
    localStorage.setItem('auth_token', data.token);
    
    return data;
  },
  
  /**
   * Logout user
   */
  async logout() {
    try {
      await apiRequest('/auth/logout', {
        method: 'POST',
      });
    } finally {
      // Always remove token, even if request fails
      localStorage.removeItem('auth_token');
    }
  },
  
  /**
   * Get current user
   */
  async me() {
    return await apiRequest('/auth/me');
  },
};

/**
 * Galleries API
 */
export const galleries = {
  /**
   * Get all galleries
   */
  async getAll() {
    return await apiRequest('/galleries');
  },
  
  /**
   * Get single gallery with images
   */
  async getById(id) {
    return await apiRequest(`/galleries/${id}`);
  },
  
  /**
   * Create new gallery
   */
  async create(name, description) {
    return await apiRequest('/galleries', {
      method: 'POST',
      body: JSON.stringify({ name, description }),
    });
  },
  
  /**
   * Update gallery
   */
  async update(id, name, description) {
    return await apiRequest(`/galleries/${id}`, {
      method: 'PUT',
      body: JSON.stringify({ name, description }),
    });
  },
  
  /**
   * Delete gallery
   */
  async delete(id) {
    return await apiRequest(`/galleries/${id}`, {
      method: 'DELETE',
    });
  },
};

/**
 * Images API
 */
export const images = {
  /**
   * Upload images to gallery
   */
  async upload(galleryId, files) {
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
      formData.append('files[]', files[i]);
    }
    
    const token = getAuthToken();
    
    const response = await fetch(`${API_BASE_URL}/galleries/${galleryId}/images`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
      body: formData,
    });
    
    const data = await response.json();
    
    if (!response.ok) {
      console.error('Upload Error:', data);
      const error = new Error(data.error || 'Upload failed');
      error.status = response.status;
      error.requestId = data.request_id;
      error.details = data.details;
      throw error;
    }
    
    return data;
  },
  
  /**
   * Delete image
   */
  async delete(id) {
    return await apiRequest(`/images/${id}`, {
      method: 'DELETE',
    });
  },
  
  /**
   * Get image URL
   */
  getImageUrl(filename) {
    return `http://localhost:8000/uploads/${filename}`;
  },
  
  /**
   * Get thumbnail URL
   */
  getThumbnailUrl(filename) {
    return `http://localhost:8000/uploads/${filename}`;
  },
};




