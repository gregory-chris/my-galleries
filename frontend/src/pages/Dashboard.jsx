import { useState, useEffect } from 'react';
import { PhotoIcon } from '@heroicons/react/24/outline';
import { galleries } from '../api/client';
import GalleryCard from '../components/Gallery/GalleryCard';
import CreateGalleryModal from '../components/Gallery/CreateGalleryModal';
import EditGalleryModal from '../components/Gallery/EditGalleryModal';
import DeleteConfirmDialog from '../components/Gallery/DeleteConfirmDialog';
import Toast from '../components/Layout/Toast';
import Loading from '../components/Layout/Loading';

export default function Dashboard() {
  const [galleryList, setGalleryList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [selectedGallery, setSelectedGallery] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [toast, setToast] = useState(null);
  
  // Fetch galleries on mount
  useEffect(() => {
    loadGalleries();
  }, []);
  
  const loadGalleries = async () => {
    try {
      setLoading(true);
      const data = await galleries.getAll();
      setGalleryList(data.galleries || []);
    } catch (err) {
      console.error('Failed to load galleries:', err);
      setToast({ message: err.message || 'Failed to load galleries', type: 'error' });
    } finally {
      setLoading(false);
    }
  };
  
  const handleCreateGallery = async (name, description) => {
    try {
      const newGallery = await galleries.create(name, description);
      setGalleryList([newGallery, ...galleryList]);
      setToast({ message: 'Gallery created successfully!', type: 'success' });
    } catch (err) {
      throw err; // Let modal handle the error
    }
  };
  
  const handleEditClick = (gallery) => {
    setSelectedGallery(gallery);
    setShowEditModal(true);
  };
  
  const handleUpdateGallery = async (id, name, description) => {
    try {
      const updatedGallery = await galleries.update(id, name, description);
      setGalleryList(galleryList.map(g => g.id === id ? updatedGallery : g));
      setToast({ message: 'Gallery updated successfully!', type: 'success' });
    } catch (err) {
      throw err; // Let modal handle the error
    }
  };
  
  const handleDeleteClick = (gallery) => {
    setSelectedGallery(gallery);
    setShowDeleteDialog(true);
  };
  
  const handleConfirmDelete = async () => {
    if (!selectedGallery) return;
    
    try {
      setDeleteLoading(true);
      await galleries.delete(selectedGallery.id);
      setGalleryList(galleryList.filter(g => g.id !== selectedGallery.id));
      setShowDeleteDialog(false);
      setSelectedGallery(null);
      setToast({ message: 'Gallery deleted successfully!', type: 'success' });
    } catch (err) {
      console.error('Failed to delete gallery:', err);
      setToast({ message: err.message || 'Failed to delete gallery', type: 'error' });
    } finally {
      setDeleteLoading(false);
    }
  };
  
  if (loading) {
    return <Loading />;
  }
  
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 sm:mb-8 gap-4">
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">My Galleries</h1>
        <button
          onClick={() => setShowCreateModal(true)}
          className="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm sm:text-base"
        >
          Create Gallery
        </button>
      </div>
      
      {/* Gallery Grid or Empty State */}
      {galleryList.length === 0 ? (
        <div className="text-center py-12 sm:py-16">
          <PhotoIcon className="w-12 sm:w-16 h-12 sm:h-16 mx-auto text-gray-400 mb-4" />
          <h3 className="text-lg sm:text-xl font-semibold text-gray-900 mb-2">No galleries yet</h3>
          <p className="text-sm sm:text-base text-gray-600 mb-6 px-4">Get started by creating your first gallery</p>
          <button
            onClick={() => setShowCreateModal(true)}
            className="px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm sm:text-base"
          >
            Create Your First Gallery
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          {galleryList.map((gallery) => (
            <GalleryCard
              key={gallery.id}
              gallery={gallery}
              onEdit={handleEditClick}
              onDelete={handleDeleteClick}
            />
          ))}
        </div>
      )}
      
      {/* Modals */}
      <CreateGalleryModal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        onGalleryCreated={handleCreateGallery}
      />
      
      <EditGalleryModal
        isOpen={showEditModal}
        onClose={() => {
          setShowEditModal(false);
          setSelectedGallery(null);
        }}
        gallery={selectedGallery}
        onGalleryUpdated={handleUpdateGallery}
      />
      
      <DeleteConfirmDialog
        isOpen={showDeleteDialog}
        onClose={() => {
          setShowDeleteDialog(false);
          setSelectedGallery(null);
        }}
        onConfirm={handleConfirmDelete}
        galleryName={selectedGallery?.name}
        loading={deleteLoading}
      />
      
      {/* Toast Notification */}
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}
    </div>
  );
}
