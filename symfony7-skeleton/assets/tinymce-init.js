/**
 * TinyMCE Rich Text Editor Initialization
 * For page content editing in admin area
 */

// TinyMCE will be loaded from CDN in the template
// This file initializes it when available

function initTinyMCE() {
    if (typeof tinymce === 'undefined') {
        console.warn('TinyMCE not loaded');
        return;
    }

    // Find all textareas that should be rich text editors
    const editors = document.querySelectorAll('textarea[data-editor="tinymce"], textarea[name*="[content]"]');

    if (editors.length === 0) {
        return;
    }

    tinymce.init({
        selector: 'textarea[data-editor="tinymce"], textarea[name*="[content]"]',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,

        // Image upload handler
        images_upload_handler: function (blobInfo, success, failure) {
            // For now, we'll use base64 encoding
            // In production, you'd want to upload to server
            const reader = new FileReader();
            reader.onload = function() {
                success(reader.result);
            };
            reader.onerror = function() {
                failure('Failed to read image');
            };
            reader.readAsDataURL(blobInfo.blob());
        },

        // Content filtering
        valid_elements: '*[*]',
        extended_valid_elements: 'script[src|async|defer|type|charset]',

        // Auto-save integration
        setup: function(editor) {
            editor.on('change', function() {
                editor.save(); // Save to textarea
            });

            editor.on('init', function() {
                console.log('✓ TinyMCE editor initialized');
            });
        }
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit for TinyMCE to load from CDN
        setTimeout(initTinyMCE, 500);
    });
} else {
    setTimeout(initTinyMCE, 500);
}

export { initTinyMCE };
