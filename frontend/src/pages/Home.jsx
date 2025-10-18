import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { PhotoIcon } from '@heroicons/react/24/outline';

export default function Home() {
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();
  
  // Redirect to dashboard if authenticated
  useEffect(() => {
    if (isAuthenticated) {
      navigate('/dashboard');
    }
  }, [isAuthenticated, navigate]);
  
  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4">
      <div className="text-center max-w-3xl mx-auto">
        <PhotoIcon className="w-16 sm:w-20 md:w-24 h-16 sm:h-20 md:h-24 mx-auto text-blue-600 mb-4 sm:mb-6" />
        <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-3 sm:mb-4">
          My Galleries
        </h1>
        <p className="text-base sm:text-lg md:text-xl text-gray-600 mb-6 sm:mb-8">
          Upload, organize, and view your personal photo collections.
          Create beautiful galleries and manage your images with ease.
        </p>
        <div className="text-sm sm:text-base text-gray-500">
          Please login or sign up to get started
        </div>
      </div>
    </div>
  );
}




