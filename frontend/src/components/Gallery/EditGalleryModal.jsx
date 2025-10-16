import { useState, useEffect } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';

export default function EditGalleryModal({ isOpen, onClose, gallery, onGalleryUpdated }) {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  
  // Pre-populate form when gallery changes
  useEffect(() => {
    if (gallery) {
      setName(gallery.name || '');
      setDescription(gallery.description || '');
    }
  }, [gallery]);
  
  if (!isOpen || !gallery) return null;
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    
    // Validation
    if (!name || name.trim().length === 0) {
      setError('Gallery name is required');
      return;
    }
    
    if (name.length > 100) {
      setError('Gallery name must be 100 characters or less');
      return;
    }
    
    if (!/^[a-zA-Z0-9\s.,\-'!]{1,100}$/.test(name)) {
      setError('Gallery name contains invalid characters. Use only letters, numbers, spaces, and basic punctuation');
      return;
    }
    
    if (description.length > 500) {
      setError('Description must be 500 characters or less');
      return;
    }
    
    setLoading(true);
    
    try {
      await onGalleryUpdated(gallery.id, name, description);
      onClose();
    } catch (err) {
      setError(err.message || 'Failed to update gallery');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-2xl font-bold text-gray-900">Edit Gallery</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon className="w-6 h-6" />
          </button>
        </div>
        
        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="bg-red-50 text-red-600 px-4 py-3 rounded-lg text-sm">
              {error}
            </div>
          )}
          
          <div>
            <label htmlFor="edit-gallery-name" className="block text-sm font-medium text-gray-700 mb-2">
              Gallery Name *
            </label>
            <input
              id="edit-gallery-name"
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="My Summer Vacation"
              maxLength={100}
              required
            />
            <p className="text-xs text-gray-500 mt-1">{name.length}/100 characters</p>
          </div>
          
          <div>
            <label htmlFor="edit-gallery-description" className="block text-sm font-medium text-gray-700 mb-2">
              Description (Optional)
            </label>
            <textarea
              id="edit-gallery-description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Photos from our trip..."
              rows={3}
              maxLength={500}
            />
            <p className="text-xs text-gray-500 mt-1">{description.length}/500 characters</p>
          </div>
          
          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors font-medium"
            >
              {loading ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

