import { useState, useRef } from 'react';
import { XMarkIcon, PhotoIcon, CloudArrowUpIcon } from '@heroicons/react/24/outline';
import uploadLimits from '../../config/upload-limits.json';

// Use server-side configuration values
const MAX_FILE_SIZE = uploadLimits.upload.maxFileSize;
const MAX_FILES = uploadLimits.upload.maxFiles;
const MAX_TOTAL_SIZE = uploadLimits.upload.maxTotalSize;
const ALLOWED_TYPES = uploadLimits.upload.allowedTypes;

// Format bytes to human readable
const formatBytes = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

export default function ImageUpload({ galleryId, onUploadSuccess, onClose }) {
  const [files, setFiles] = useState([]);
  const [previews, setPreviews] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState('');
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);
  
  const validateFiles = (filesToValidate) => {
    const errors = [];
    
    // Check number of files
    if (filesToValidate.length > MAX_FILES) {
      errors.push(`Maximum ${MAX_FILES} files allowed per upload`);
      return errors;
    }
    
    // Check individual file sizes and types
    let totalSize = 0;
    for (const file of filesToValidate) {
      if (file.size > MAX_FILE_SIZE) {
        errors.push(`${file.name}: File size exceeds ${formatBytes(MAX_FILE_SIZE)} limit`);
      }
      
      if (!ALLOWED_TYPES.includes(file.type)) {
        errors.push(`${file.name}: Invalid file type. Allowed: ${uploadLimits.upload.allowedExtensions.map(e => e.toUpperCase()).join(', ')}`);
      }
      
      totalSize += file.size;
    }
    
    // Check total size
    if (totalSize > MAX_TOTAL_SIZE) {
      errors.push(`Total size exceeds ${formatBytes(MAX_TOTAL_SIZE)} limit`);
    }
    
    return errors;
  };
  
  const handleFiles = (newFiles) => {
    const validationErrors = validateFiles(newFiles);
    
    if (validationErrors.length > 0) {
      setError(validationErrors.join('. '));
      return;
    }
    
    setError('');
    
    // Calculate total size for display
    const totalSize = Array.from(newFiles).reduce((sum, file) => sum + file.size, 0);
    const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);
    
    // Show warning if approaching limits (75% of max)
    const warningThreshold = MAX_TOTAL_SIZE * 0.75;
    if (totalSize > warningThreshold) {
      setError(`Warning: Total size is ${totalSizeMB}MB (approaching ${formatBytes(MAX_TOTAL_SIZE)} limit). Upload may take longer.`);
    }
    
    setFiles(newFiles);
    
    // Create previews
    const newPreviews = [];
    for (const file of newFiles) {
      const reader = new FileReader();
      reader.onloadend = () => {
        newPreviews.push({
          file: file,
          url: reader.result,
          name: file.name,
          size: file.size
        });
        
        if (newPreviews.length === newFiles.length) {
          setPreviews(newPreviews);
        }
      };
      reader.readAsDataURL(file);
    }
  };
  
  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };
  
  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    const droppedFiles = Array.from(e.dataTransfer.files);
    handleFiles(droppedFiles);
  };
  
  const handleFileInput = (e) => {
    const selectedFiles = Array.from(e.target.files);
    handleFiles(selectedFiles);
  };
  
  const removeFile = (index) => {
    const newFiles = files.filter((_, i) => i !== index);
    const newPreviews = previews.filter((_, i) => i !== index);
    setFiles(newFiles);
    setPreviews(newPreviews);
  };
  
  // Use the formatBytes function defined at the top instead
  const formatFileSize = formatBytes;
  
  const handleUpload = async () => {
    if (files.length === 0) {
      setError('Please select files to upload');
      return;
    }
    
    setUploading(true);
    setProgress(0);
    setError('');
    
    try {
      const formData = new FormData();
      files.forEach((file) => {
        formData.append('files[]', file);
      });
      
      const token = localStorage.getItem('auth_token');
      
      const xhr = new XMLHttpRequest();
      
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          setProgress(Math.round(percentComplete));
        }
      });
      
      xhr.addEventListener('load', () => {
        if (xhr.status === 201) {
          const data = JSON.parse(xhr.responseText);
          onUploadSuccess(data.uploaded);
          setFiles([]);
          setPreviews([]);
          onClose();
        } else {
          const error = JSON.parse(xhr.responseText);
          setError(error.error || 'Upload failed');
          setUploading(false);
        }
      });
      
      xhr.addEventListener('error', () => {
        setError('Network error during upload');
        setUploading(false);
      });
      
      xhr.open('POST', `http://localhost:8000/api/galleries/${galleryId}/images`);
      xhr.setRequestHeader('Authorization', `Bearer ${token}`);
      xhr.send(formData);
      
    } catch (err) {
      setError(err.message || 'Upload failed');
      setUploading(false);
    }
  };
  
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 animate-fade-in">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col animate-slide-up">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-2xl font-bold text-gray-900">Upload Images</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
            disabled={uploading}
          >
            <XMarkIcon className="w-6 h-6" />
          </button>
        </div>
        
        {/* Content */}
        <div className="p-6 overflow-y-auto flex-1">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
              <p className="font-medium">Upload Error</p>
              <p className="mt-1">{error}</p>
            </div>
          )}
          
          {/* Upload Zone */}
          {previews.length === 0 && (
            <div
              className={`border-2 border-dashed rounded-lg p-8 sm:p-12 text-center transition-colors ${
                dragActive
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-300 hover:border-gray-400'
              }`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
            >
              <CloudArrowUpIcon className="w-12 sm:w-16 h-12 sm:h-16 mx-auto text-gray-400 mb-4" />
              <p className="text-base sm:text-lg font-medium text-gray-900 mb-2">
                Drag and drop images here
              </p>
              <p className="text-sm text-gray-600 mb-4">
                or click to browse
              </p>
              <button
                onClick={() => fileInputRef.current?.click()}
                className="px-4 sm:px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm sm:text-base"
                disabled={uploading}
              >
                Select Files
              </button>
              <input
                ref={fileInputRef}
                type="file"
                multiple
                accept="image/jpeg,image/png,image/gif,image/webp"
                onChange={handleFileInput}
                className="hidden"
              />
              <p className="text-xs text-gray-500 mt-4">
                Max {MAX_FILES} files • Max {formatBytes(MAX_FILE_SIZE)} per file • Max {formatBytes(MAX_TOTAL_SIZE)} total
              </p>
            </div>
          )}
          
          {/* Preview Grid */}
          {previews.length > 0 && (
            <div>
              <div className="flex items-center justify-between mb-4">
                <p className="text-sm text-gray-600">
                  {previews.length} file{previews.length !== 1 ? 's' : ''} selected
                </p>
                {!uploading && (
                  <button
                    onClick={() => fileInputRef.current?.click()}
                    className="text-sm text-blue-600 hover:text-blue-700"
                  >
                    Add more files
                  </button>
                )}
                <input
                  ref={fileInputRef}
                  type="file"
                  multiple
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  onChange={handleFileInput}
                  className="hidden"
                />
              </div>
              
              <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
                {previews.map((preview, index) => (
                  <div key={index} className="relative group">
                    <img
                      src={preview.url}
                      alt={preview.name}
                      className="w-full h-24 sm:h-32 object-cover rounded-lg"
                    />
                    {!uploading && (
                      <button
                        onClick={() => removeFile(index)}
                        className="absolute top-1 right-1 sm:top-2 sm:right-2 bg-red-500 text-white p-1 rounded-full opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity"
                      >
                        <XMarkIcon className="w-3 h-3 sm:w-4 sm:h-4" />
                      </button>
                    )}
                    <div className="mt-1 text-xs text-gray-600 truncate">
                      {preview.name}
                    </div>
                    <div className="text-xs text-gray-500">
                      {formatFileSize(preview.size)}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
          
          {/* Upload Progress */}
          {uploading && (
            <div className="mt-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium text-gray-700">
                  Uploading {files.length} file{files.length !== 1 ? 's' : ''}...
                </span>
                <span className="text-sm text-gray-600">{progress}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div
                  className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  style={{ width: `${progress}%` }}
                />
              </div>
            </div>
          )}
        </div>
        
        {/* Footer */}
        <div className="px-6 py-4 border-t flex items-center justify-end gap-3">
          <button
            onClick={onClose}
            className="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors"
            disabled={uploading}
          >
            Cancel
          </button>
          <button
            onClick={handleUpload}
            disabled={uploading || files.length === 0}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors font-medium"
          >
            {uploading ? 'Uploading...' : `Upload ${files.length} file${files.length !== 1 ? 's' : ''}`}
          </button>
        </div>
      </div>
    </div>
  );
}

