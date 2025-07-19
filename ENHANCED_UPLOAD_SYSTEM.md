# Enhanced File Upload System

This project has been enhanced with a robust file upload system that supports both small and large files with synchronized progress display.

## Features

### Upload Strategies
- **Small files (< 5MB)**: Direct upload for faster processing
- **Large files (≥ 5MB)**: Chunked upload with retry capability and progress tracking

### Enhanced Capabilities
- Real-time progress tracking with speed and time estimates
- Pause/Resume functionality for uploads
- Automatic retry on transient failures (with exponential backoff)
- Upload cancellation with proper cleanup
- Enhanced error handling and user feedback
- Support for multiple concurrent file uploads

## Architecture

### Frontend Components

#### New Files
- `resources/js/helpers/uploadFunctions.js` - Core upload logic with chunked upload support
- `resources/js/helpers/functionsHelpers.js` - Enhanced helper functions for upload management
- `resources/js/components/FileUploader/FileUploader.vue` - Advanced drag-and-drop upload component

#### Enhanced Files
- `resources/js/components/UI/Others/UploadProgress.vue` - Enhanced progress display with pause/resume controls
- `resources/js/store/modules/fileFunctions.js` - Updated Vuex store with enhanced upload state management

### Backend Components

#### New Controllers
- `InitChunkedUploadController` - Initialize chunked upload sessions
- `FinalizeChunkedUploadController` - Merge chunks and finalize uploads
- `CancelChunkedUploadController` - Cancel uploads and cleanup resources

#### New Models
- `UploadSession` - Manages chunked upload sessions with metadata

#### Enhanced Actions
- `StoreFileChunksAction` - Updated to support session-based chunk storage

#### Database
- `upload_sessions` table - Stores upload session metadata
- See `database_schema_upload_sessions.sql` for schema

### API Endpoints

#### Standard Upload Routes
- `POST /api/upload` - Direct file upload (small files)
- `POST /api/upload/chunks` - Legacy chunked upload

#### Enhanced Chunked Upload Routes
- `POST /api/upload/init` - Initialize upload session
- `POST /api/upload/chunks` - Upload file chunks (with session support)
- `POST /api/upload/finalize` - Finalize and merge chunks
- `POST /api/upload/cancel` - Cancel upload
- `DELETE /api/upload/cleanup` - Cleanup failed uploads

#### Context-Aware Routes
All routes are available with context prefixes:
- `/api/file-request/{token}/upload/*` - For upload requests
- `/api/sharing/upload/{token}/*` - For public sharing

## Configuration

### File Size Thresholds
- Small file threshold: 5MB (configurable in `uploadFunctions.js`)
- Chunk size: 1MB (configurable in backend)
- Session expiry: 24 hours

### Error Handling
- Automatic retry for HTTP 500, 502, 503, 504, 408 errors
- Maximum 3 retry attempts with exponential backoff
- Graceful degradation for network issues

## Usage

### Basic Upload (Legacy)
```javascript
// Use existing upload functionality
this.$uploadFiles(files)
```

### Enhanced Upload
```javascript
// Use enhanced upload with automatic strategy selection
this.$uploadFilesEnhanced(files)

// Manual upload management
this.$handleEnhancedUploading(uploadItem)

// Upload controls
this.$pauseUpload()
this.$resumeUpload()
this.$cancelCurrentUpload()
```

### FileUploader Component
```vue
<template>
  <FileUploader
    :parent-id="folderId"
    :auto-start="true"
    :max-file-size="maxSize"
    @file-uploaded="onFileUploaded"
    @all-complete="onAllComplete"
  />
</template>
```

## Testing

A test file is available at `upload_system_test.html` which demonstrates:
- File selection and drag-and-drop
- Upload strategy selection (direct vs chunked)
- Progress tracking and time estimation
- Upload controls (pause, resume, cancel)
- Error simulation and retry behavior

## Maintenance

### Session Cleanup
A cleanup command is available to remove expired upload sessions:

```bash
php artisan upload:cleanup-sessions
```

This command should be run regularly (e.g., via cron) to clean up expired sessions and chunk files.

### Monitoring
- Monitor `upload_sessions` table for stuck sessions
- Check disk usage in `chunks/sessions/` directory
- Monitor upload error rates and retry patterns

## Performance Considerations

### Memory Usage
- Chunked uploads minimize memory usage for large files
- Chunks are processed sequentially to avoid memory spikes

### Storage
- Temporary chunks are stored in `chunks/sessions/{session_id}/`
- Cleanup runs automatically on completion or failure
- Manual cleanup available via artisan command

### Network
- Chunked uploads provide better reliability over unstable connections
- Automatic retry with exponential backoff reduces server load
- Progress tracking provides better user experience

## Migration Notes

The enhanced system is backward compatible with existing upload functionality. Existing code will continue to work while new features are available through enhanced helpers.

### Breaking Changes
None - the system maintains full backward compatibility.

### New Dependencies
- Enhanced Vuex state management for upload progress
- Session-based chunk storage in backend
- New database table for upload sessions

## Future Enhancements

Potential future improvements:
- Resume interrupted uploads across browser sessions
- Parallel chunk uploads for very large files
- Client-side file validation and preprocessing
- Upload queue prioritization
- Integration with cloud storage providers
- Real-time upload analytics and monitoring