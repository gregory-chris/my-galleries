import { useNavigate } from 'react-router-dom';
import { ExclamationCircleIcon } from '@heroicons/react/24/outline';

export default function NotFound() {
  const navigate = useNavigate();
  
  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center bg-gray-50 px-4">
      <div className="text-center max-w-md">
        <div className="flex justify-center mb-6">
          <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-red-100 flex items-center justify-center">
            <ExclamationCircleIcon className="w-10 h-10 sm:w-12 sm:h-12 text-red-600" />
          </div>
        </div>
        
        <h1 className="text-6xl sm:text-7xl font-bold text-gray-900 mb-4">404</h1>
        <h2 className="text-xl sm:text-2xl font-semibold text-gray-800 mb-3">
          Page Not Found
        </h2>
        <p className="text-sm sm:text-base text-gray-600 mb-8">
          Sorry, the page you're looking for doesn't exist or has been moved.
        </p>
        
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <button
            onClick={() => navigate(-1)}
            className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
          >
            Go Back
          </button>
          <button
            onClick={() => navigate('/')}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
          >
            Go to Home
          </button>
        </div>
      </div>
    </div>
  );
}

