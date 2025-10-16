import { PhotoIcon } from '@heroicons/react/24/outline';

export default function Dashboard() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-8">
        <h1 className="text-3xl font-bold text-gray-900">My Galleries</h1>
        <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
          Create Gallery
        </button>
      </div>
      
      {/* Empty state */}
      <div className="text-center py-16">
        <PhotoIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h3 className="text-xl font-semibold text-gray-900 mb-2">No galleries yet</h3>
        <p className="text-gray-600 mb-6">Get started by creating your first gallery</p>
        <button className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
          Create Your First Gallery
        </button>
      </div>
    </div>
  );
}




