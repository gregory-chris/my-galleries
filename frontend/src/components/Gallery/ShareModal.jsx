import { useState } from 'react';
import { XMarkIcon, ClipboardIcon, CheckIcon, LinkSlashIcon } from '@heroicons/react/24/outline';

/**
 * ShareModal Component
 * Modal for sharing gallery with public URL
 */
export default function ShareModal({ isOpen, onClose, shareUrl, onDisableSharing }) {
  const [copied, setCopied] = useState(false);
  const [isDisabling, setIsDisabling] = useState(false);

  if (!isOpen) return null;

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const handleDisableSharing = async () => {
    if (window.confirm('Are you sure you want to disable sharing? The current link will stop working.')) {
      setIsDisabling(true);
      try {
        await onDisableSharing();
        onClose();
      } catch (err) {
        console.error('Failed to disable sharing:', err);
        setIsDisabling(false);
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        {/* Header */}
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900">Share Gallery</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition"
            aria-label="Close"
          >
            <XMarkIcon className="w-6 h-6" />
          </button>
        </div>

        {/* Share URL */}
        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Public Link
          </label>
          <div className="flex items-center gap-2">
            <input
              type="text"
              value={shareUrl}
              readOnly
              className="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-900 font-mono text-sm"
            />
            <button
              onClick={handleCopyLink}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center gap-2"
            >
              {copied ? (
                <>
                  <CheckIcon className="w-5 h-5" />
                  Copied!
                </>
              ) : (
                <>
                  <ClipboardIcon className="w-5 h-5" />
                  Copy
                </>
              )}
            </button>
          </div>
          <p className="mt-2 text-sm text-gray-600">
            Anyone with this link can view your gallery and images.
          </p>
        </div>

        {/* Disable Sharing Button */}
        <div className="border-t pt-4">
          <button
            onClick={handleDisableSharing}
            disabled={isDisabling}
            className="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <LinkSlashIcon className="w-5 h-5" />
            {isDisabling ? 'Disabling...' : 'Disable Sharing'}
          </button>
          <p className="mt-2 text-sm text-gray-600 text-center">
            This will make the gallery private again.
          </p>
        </div>
      </div>
    </div>
  );
}

