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
  
  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
    
    // Handle network errors or non-JSON responses
    let data;
    try {
      data = await response.json();
    } catch (jsonError) {
      console.error('Failed to parse JSON response:', jsonError);
      throw new Error('Server returned invalid response. Please try again.');
    }
    
    if (!response.ok) {
      // Handle 401 - Token expired or invalid
      if (response.status === 401) {
        localStorage.removeItem('auth_token');
        const error = new Error('Your session has expired. Please login again.');
        error.status = 401;
        error.requestId = data.request_id;
        throw error;
      }
      
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
  } catch (err) {
    // Handle network errors
    if (err instanceof TypeError && err.message === 'Failed to fetch') {
      const error = new Error('Network error. Please check your internet connection and try again.');
      error.isNetworkError = true;
      throw error;
    }
    
    // Re-throw other errors
    throw err;
  }
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
    
    try {
      const response = await fetch(`${API_BASE_URL}/galleries/${galleryId}/images`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });
      
      let data;
      try {
        data = await response.json();
      } catch (jsonError) {
        throw new Error('Server returned invalid response. Please try again.');
      }
      
      if (!response.ok) {
        // Handle 401 - Token expired
        if (response.status === 401) {
          localStorage.removeItem('auth_token');
          throw new Error('Your session has expired. Please login again.');
        }
        
        console.error('Upload Error:', data);
        const error = new Error(data.error || 'Upload failed');
        error.status = response.status;
        error.requestId = data.request_id;
        error.details = data.details;
        throw error;
      }
      
      return data;
    } catch (err) {
      // Handle network errors
      if (err instanceof TypeError && err.message === 'Failed to fetch') {
        throw new Error('Network error. Please check your internet connection and try again.');
      }
      throw err;
    }
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




