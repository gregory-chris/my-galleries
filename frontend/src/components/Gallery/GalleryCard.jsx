import { PhotoIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { useNavigate } from 'react-router-dom';

export default function GalleryCard({ gallery, onEdit, onDelete }) {
  const navigate = useNavigate();
  
  const handleCardClick = () => {
    navigate(`/gallery/${gallery.id}`);
  };
  
  const handleEdit = (e) => {
    e.stopPropagation();
    onEdit(gallery);
  };
  
  const handleDelete = (e) => {
    e.stopPropagation();
    onDelete(gallery);
  };
  
  return (
    <div
      onClick={handleCardClick}
      className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer overflow-hidden group"
    >
      {/* Thumbnail / Placeholder */}
      <div className="h-48 bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center">
        <PhotoIcon className="w-16 h-16 text-gray-400" />
      </div>
      
      {/* Content */}
      <div className="p-4">
        <div className="flex items-start justify-between mb-2">
          <h3 className="text-lg font-semibold text-gray-900 truncate flex-1">
            {gallery.name}
          </h3>
          <div className="flex gap-1 ml-2">
            <button
              onClick={handleEdit}
              className="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
              title="Edit gallery"
            >
              <PencilIcon className="w-4 h-4" />
            </button>
            <button
              onClick={handleDelete}
              className="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
              title="Delete gallery"
            >
              <TrashIcon className="w-4 h-4" />
            </button>
          </div>
        </div>
        
        {gallery.description && (
          <p className="text-sm text-gray-600 mb-3 line-clamp-2">
            {gallery.description}
          </p>
        )}
        
        <div className="flex items-center justify-between text-sm text-gray-500">
          <span>{gallery.image_count || 0} {gallery.image_count === 1 ? 'image' : 'images'}</span>
          <span className="text-xs">{new Date(gallery.created_at).toLocaleDateString()}</span>
        </div>
      </div>
    </div>
  );
}

