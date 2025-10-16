import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeftIcon, PhotoIcon } from '@heroicons/react/24/outline';
import { galleries } from '../api/client';
import Loading from '../components/Layout/Loading';

export default function GalleryDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [gallery, setGallery] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    loadGallery();
  }, [id]);
  
  const loadGallery = async () => {
    try {
      setLoading(true);
      const data = await galleries.getById(id);
      setGallery(data);
    } catch (err) {
      console.error('Failed to load gallery:', err);
      setError(err.message || 'Failed to load gallery');
    } finally {
      setLoading(false);
    }
  };
  
  if (loading) {
    return <Loading />;
  }
  
  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <button
          onClick={() => navigate('/dashboard')}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"
        >
          <ArrowLeftIcon className="w-5 h-5" />
          Back to Galleries
        </button>
        <div className="text-center py-16">
          <p className="text-red-600 mb-4">{error}</p>
          <button
            onClick={() => navigate('/dashboard')}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Go to Dashboard
          </button>
        </div>
      </div>
    );
  }
  
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <button
        onClick={() => navigate('/dashboard')}
        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
      >
        <ArrowLeftIcon className="w-5 h-5" />
        Back to Galleries
      </button>
      
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">{gallery?.name}</h1>
        {gallery?.description && (
          <p className="text-gray-600">{gallery.description}</p>
        )}
      </div>
      
      {/* Empty state - images will be added in Milestone 5 & 6 */}
      <div className="text-center py-16 bg-gray-50 rounded-lg">
        <PhotoIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h3 className="text-xl font-semibold text-gray-900 mb-2">No images in this gallery</h3>
        <p className="text-gray-600 mb-6">Image upload functionality coming in next milestone</p>
      </div>
    </div>
  );
}

