import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { PhotoIcon, ArrowLeftIcon, PencilIcon, TrashIcon, CloudArrowUpIcon, ShareIcon } from '@heroicons/react/24/outline';
import ImageUpload from '../components/Image/ImageUpload';
import ShareModal from '../components/Gallery/ShareModal';
import { galleries } from '../api/client';

export default function GalleryDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [gallery, setGallery] = useState(null);
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showUpload, setShowUpload] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [showShareModal, setShowShareModal] = useState(false);
  const [shareUrl, setShareUrl] = useState('');
  const [isEnablingShare, setIsEnablingShare] = useState(false);
  
  useEffect(() => {
    fetchGallery();
  }, [id]);
  
  const fetchGallery = async () => {
    setLoading(true);
    setError('');
    
    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`http://localhost:8000/api/galleries/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) {
        if (response.status === 404) {
          navigate('/');
          return;
        }
        throw new Error('Failed to load gallery');
      }
      
      const data = await response.json();
      setGallery(data);
      setImages(data.images || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };
  
  const handleUploadSuccess = (uploadedImages) => {
    setImages([...images, ...uploadedImages]);
    setShowUpload(false);
  };
  
  const handleShareClick = async () => {
    // If already shared, just show modal
    if (gallery.is_public && gallery.share_hash) {
      setShareUrl(`https://my-galleries.com/s/${gallery.share_hash}`);
      setShowShareModal(true);
      return;
    }
    
    // Otherwise enable sharing first
    setIsEnablingShare(true);
    try {
      const data = await galleries.enableSharing(id);
      setShareUrl(data.share_url);
      setShowShareModal(true);
      
      // Update gallery state
      setGallery({
        ...gallery,
        is_public: data.is_public,
        share_hash: data.share_hash
      });
    } catch (err) {
      setError(err.message || 'Failed to enable sharing');
    } finally {
      setIsEnablingShare(false);
    }
  };
  
  const handleDisableSharing = async () => {
    try {
      await galleries.disableSharing(id);
      
      // Update gallery state
      setGallery({
        ...gallery,
        is_public: false
      });
      
      setShowShareModal(false);
    } catch (err) {
      throw new Error(err.message || 'Failed to disable sharing');
    }
  };
  
  const handleDeleteGallery = async () => {
    if (!confirm('Are you sure you want to delete this gallery? This will delete all images.')) {
      return;
    }
    
    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`http://localhost:8000/api/galleries/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to delete gallery');
      }
      
      navigate('/');
    } catch (err) {
      setError(err.message);
    }
  };
  
  const handleDeleteImage = async (imageId) => {
    if (!confirm('Are you sure you want to delete this image?')) {
      return;
    }
    
    try {
      const token = localStorage.getItem('auth_token');
      const response = await fetch(`http://localhost:8000/api/images/${imageId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to delete image');
      }
      
      setImages(images.filter(img => img.id !== imageId));
      setSelectedImage(null);
    } catch (err) {
      setError(err.message);
    }
  };
  
  const openLightbox = (image) => {
    setSelectedImage(image);
  };
  
  const closeLightbox = () => {
    setSelectedImage(null);
  };
  
  const navigateImage = (direction) => {
    const currentIndex = images.findIndex(img => img.id === selectedImage.id);
    const newIndex = direction === 'next' 
      ? (currentIndex + 1) % images.length 
      : (currentIndex - 1 + images.length) % images.length;
    setSelectedImage(images[newIndex]);
  };
  
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (!selectedImage) return;
      
      if (e.key === 'Escape') {
        closeLightbox();
      } else if (e.key === 'ArrowLeft') {
        navigateImage('prev');
      } else if (e.key === 'ArrowRight') {
        navigateImage('next');
      }
    };
    
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [selectedImage, images]);
  
  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[calc(100vh-4rem)]">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-sm sm:text-base text-gray-600">Loading gallery...</p>
        </div>
      </div>
    );
  }
  
  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          <p className="font-medium">Error</p>
          <p className="text-sm mt-1">{error}</p>
        </div>
      </div>
    );
  }
  
  if (!gallery) {
    return null;
  }
  
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      {/* Header */}
      <div className="mb-6 sm:mb-8">
        <button
          onClick={() => navigate('/')}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4 transition-colors"
        >
          <ArrowLeftIcon className="w-5 h-5" />
          <span className="text-sm sm:text-base">Back to Galleries</span>
        </button>
        
        <div className="flex flex-col sm:flex-row items-start justify-between gap-4">
          <div className="flex-1 min-w-0">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2 break-words">{gallery.name}</h1>
            {gallery.description && (
              <p className="text-gray-600 text-sm sm:text-base">{gallery.description}</p>
            )}
            <p className="text-sm text-gray-500 mt-2">
              {images.length} image{images.length !== 1 ? 's' : ''}
            </p>
          </div>
          
          <div className="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <button
              onClick={() => setShowUpload(true)}
              className="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm sm:text-base"
            >
              <CloudArrowUpIcon className="w-5 h-5" />
              <span>Upload</span>
            </button>
            <button
              onClick={handleShareClick}
              disabled={isEnablingShare}
              className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              title="Share Gallery"
            >
              <ShareIcon className="w-5 h-5" />
            </button>
            <button
              onClick={handleDeleteGallery}
              className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="Delete Gallery"
            >
              <TrashIcon className="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
      
      {/* Images Grid */}
      {images.length === 0 ? (
        <div className="text-center py-12 sm:py-16">
          <PhotoIcon className="w-16 sm:w-24 h-16 sm:h-24 mx-auto text-gray-400 mb-4" />
          <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-2">No images yet</h3>
          <p className="text-sm sm:text-base text-gray-600 mb-6">Upload your first images to this gallery</p>
          <button
            onClick={() => setShowUpload(true)}
            className="px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm sm:text-base"
          >
            Upload Images
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-4">
          {images.map((image) => (
            <div
              key={image.id}
              className="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer group relative transition-transform hover:scale-[1.02]"
              onClick={() => openLightbox(image)}
            >
              <img
                src={`http://localhost:8000/uploads/${image.thumbnail_filename}`}
                alt={image.original_filename}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity duration-200" />
            </div>
          ))}
        </div>
      )}
      
      {/* Upload Modal */}
      {showUpload && (
        <ImageUpload
          galleryId={id}
          onUploadSuccess={handleUploadSuccess}
          onClose={() => setShowUpload(false)}
        />
      )}
      
      {/* Share Modal */}
      <ShareModal
        isOpen={showShareModal}
        onClose={() => setShowShareModal(false)}
        shareUrl={shareUrl}
        onDisableSharing={handleDisableSharing}
      />
      
      {/* Lightbox */}
      {selectedImage && (
        <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center animate-fade-in">
          <button
            onClick={closeLightbox}
            className="absolute top-2 sm:top-4 right-2 sm:right-4 text-white hover:text-gray-300 text-4xl font-light z-10 w-10 h-10 sm:w-auto sm:h-auto flex items-center justify-center"
          >
            ×
          </button>
          
          <button
            onClick={() => handleDeleteImage(selectedImage.id)}
            className="absolute top-2 sm:top-4 left-2 sm:left-4 flex items-center gap-2 px-3 sm:px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors z-10 text-sm sm:text-base"
          >
            <TrashIcon className="w-4 h-4 sm:w-5 sm:h-5" />
            <span className="hidden sm:inline">Delete</span>
          </button>
          
          <div className="absolute bottom-2 sm:bottom-4 left-1/2 -translate-x-1/2 text-white text-sm bg-black bg-opacity-50 px-3 py-1 rounded">
            {images.findIndex(img => img.id === selectedImage.id) + 1} of {images.length}
          </div>
          
          {images.length > 1 && (
            <>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  navigateImage('prev');
                }}
                className="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-4xl sm:text-5xl font-light px-2 sm:px-4 bg-black bg-opacity-30 rounded-full w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center"
              >
                ‹
              </button>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  navigateImage('next');
                }}
                className="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-4xl sm:text-5xl font-light px-2 sm:px-4 bg-black bg-opacity-30 rounded-full w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center"
              >
                ›
              </button>
            </>
          )}
          
          <img
            src={`http://localhost:8000/uploads/${selectedImage.filename}`}
            alt={selectedImage.original_filename}
            className="max-w-[90vw] max-h-[80vh] sm:max-h-[90vh] object-contain"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}
    </div>
  );
}
