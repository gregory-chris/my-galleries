import { createContext, useContext, useState, useEffect } from 'react';
import { auth } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Check for existing auth token on mount
  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
      // Validate token by fetching user info
      auth.me()
        .then((userData) => {
          setUser(userData);
          setLoading(false);
        })
        .catch((err) => {
          // Token is invalid, clear it
          console.error('Token validation failed:', err);
          localStorage.removeItem('auth_token');
          setUser(null);
          setLoading(false);
        });
    } else {
      setLoading(false);
    }
  }, []);
  
  const signup = async (email, password) => {
    try {
      setError(null);
      const data = await auth.signup(email, password);
      setUser(data.user);
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };
  
  const login = async (email, password) => {
    try {
      setError(null);
      const data = await auth.login(email, password);
      setUser(data.user);
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };
  
  const logout = async () => {
    try {
      await auth.logout();
    } finally {
      setUser(null);
    }
  };
  
  const value = {
    user,
    loading,
    error,
    signup,
    login,
    logout,
    isAuthenticated: !!user,
  };
  
  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}

