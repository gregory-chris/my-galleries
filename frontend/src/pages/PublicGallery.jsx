import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { PhotoIcon } from '@heroicons/react/24/outline';
import { galleries } from '../api/client';

/**
 * PublicGallery Component
 * Public view of a shared gallery (no authentication required)
 */
export default function PublicGallery() {
  const { hash } = useParams();
  const [gallery, setGallery] = useState(null);
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedImage, setSelectedImage] = useState(null);

  useEffect(() => {
    fetchPublicGallery();
  }, [hash]);

  const fetchPublicGallery = async () => {
    setLoading(true);
    setError('');

    try {
      const data = await galleries.getByShareHash(hash);
      setGallery(data);
      setImages(data.images || []);
    } catch (err) {
      setError(err.message || 'Gallery not found');
    } finally {
      setLoading(false);
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
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-sm sm:text-base text-gray-600">Loading gallery...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center px-4">
        <div className="text-center max-w-md">
          <PhotoIcon className="w-16 sm:w-24 h-16 sm:h-24 mx-auto text-gray-400 mb-4" />
          <h1 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Gallery Not Found</h1>
          <p className="text-sm sm:text-base text-gray-600 mb-6">
            This gallery doesn't exist or is no longer being shared.
          </p>
        </div>
      </div>
    );
  }

  if (!gallery) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2 break-words">
            {gallery.name}
          </h1>
          {gallery.description && (
            <p className="text-gray-600 text-sm sm:text-base mb-2">
              {gallery.description}
            </p>
          )}
          <p className="text-sm text-gray-500">
            {images.length} image{images.length !== 1 ? 's' : ''}
          </p>
        </div>
      </div>

      {/* Images Grid */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {images.length === 0 ? (
          <div className="text-center py-12 sm:py-16">
            <PhotoIcon className="w-16 sm:w-24 h-16 sm:h-24 mx-auto text-gray-400 mb-4" />
            <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-2">
              No images yet
            </h3>
            <p className="text-sm sm:text-base text-gray-600">
              This gallery doesn't have any images yet.
            </p>
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
      </div>

      {/* Footer */}
      <div className="bg-white border-t mt-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center">
          <p className="text-sm text-gray-600">
            Create your own gallery at{' '}
            <a
              href="https://my-galleries.com"
              className="text-blue-600 hover:text-blue-700 font-medium"
            >
              my-galleries.com
            </a>
          </p>
        </div>
      </div>

      {/* Lightbox */}
      {selectedImage && (
        <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center animate-fade-in">
          <button
            onClick={closeLightbox}
            className="absolute top-2 sm:top-4 right-2 sm:right-4 text-white hover:text-gray-300 text-4xl font-light z-10 w-10 h-10 sm:w-auto sm:h-auto flex items-center justify-center"
          >
            ×
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

