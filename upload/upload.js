// Upload Notes JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initUploadForm();
    initFileUpload();
});

function initUploadForm() {
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleUpload();
        });
    }
}

function handleUpload() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);
    
    // Validate required fields
    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value;
    const file = document.getElementById('file').files[0];
    
    if (!title) {
        showNotification('Please enter a note title', 'error');
        return;
    }
    
    if (!category) {
        showNotification('Please select a category', 'error');
        return;
    }
    
    if (!file) {
        showNotification('Please select a file to upload', 'error');
        return;
    }
    
    // Show upload progress
    showNotification('Uploading your notes...', 'info');
    
    // Submit the form
    form.submit();
}

function handleFileSelect(input) {
    const file = input.files[0];
    const display = input.parentElement.querySelector('.file-upload-display');
    
    if (file) {
        display.innerHTML = `
            <i class="fas fa-file"></i>
            <span class="upload-text">${file.name}</span>
            <small class="upload-hint">${formatFileSize(file.size)}</small>
        `;
        display.classList.add('file-selected');
    } else {
        display.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <span class="upload-text">Choose a file to upload</span>
            <small class="upload-hint">PDF, DOC, DOCX, or ZIP (Max 50MB)</small>
        `;
        display.classList.remove('file-selected');
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showNotification(message, type = 'info') {
    // Get or create notification container
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        notification.classList.add('removing');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
            // Remove container if empty
            if (container && container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 3000);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || icons.info;
}