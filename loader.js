$(document).ready(function() {
    const dropZone = $('#dropZone');
    const fileInput = $('#inpt');
    const filePreview = $('#filePreview');
    const form = $('#contact');
    const submitButton = $('#submit');
    const alertBox = $('#alertBox');
    const maxFileSize = 4 * 1024 * 1024; // 4MB in bytes
    let selectedFiles = [];

    // Fetch and inject CSRF token
    $.get('uploader.php', function(data) {
        form.prepend(data);
    });

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.on(eventName, preventDefaults);
        $(document).on(eventName, preventDefaults);
    });

    // Highlight drop zone when file is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.on(eventName, highlight);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.on(eventName, unhighlight);
    });

    // Handle dropped files
    dropZone.on('drop', handleDrop);
    
    // Handle click to upload
    dropZone.on('click', (e) => {
        // Prevent event bubbling from the input, which would cause a recursive loop
        if (e.target.tagName !== 'INPUT') {
            fileInput.trigger('click');
        }
    });
    
    // Handle file input change
    fileInput.on('change', handleFileSelect);
    
    // Handle form submission
    form.on('submit', handleSubmit);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropZone.addClass('highlight');
    }

    function unhighlight(e) {
        dropZone.removeClass('highlight');
    }

    function handleDrop(e) {
        const dt = e.originalEvent.dataTransfer;
        const files = Array.from(dt.files);
        handleFiles(files);
    }

    function handleFileSelect(e) {
        const files = Array.from(e.target.files);
        handleFiles(files);
    }

    function handleFiles(files) {
        // Filter out files that are too large or already selected
        files.forEach(file => {
            if (file.size > maxFileSize) {
                showAlert('error', 'File "' + file.name + '" is too large. Maximum size is 4MB.');
            } else if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        });
        updateFilePreview();
    }

    function updateFilePreview() {
        filePreview.empty();
        if (selectedFiles.length === 0) {
            filePreview.hide();
            dropZone.show();
            return;
        }
        selectedFiles.forEach((file, idx) => {
            const fileRow = $('<div class="file-row"></div>');
            fileRow.append('<i class="fas fa-file"></i>');
            fileRow.append('<span>' + file.name + ' (' + Math.round(file.size/1024) + ' KB)</span>');
            const removeBtn = $('<button type="button" class="remove-file"><i class="fas fa-times"></i></button>');
            removeBtn.on('click', function() {
                selectedFiles.splice(idx, 1);
                updateFilePreview();
            });
            fileRow.append(removeBtn);
            filePreview.append(fileRow);
        });
        filePreview.show();
        dropZone.hide();
    }

    function handleSubmit(e) {
        e.preventDefault();
        
        // Basic validation
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        if (selectedFiles.length === 0) {
            showAlert('error', 'Please select at least one file.');
            return;
        }
        const formData = new FormData(form[0]);
        // Remove any files from the input (we'll append manually)
        formData.delete('inpt[]');
        selectedFiles.forEach(file => {
            formData.append('inpt[]', file);
        });
        // Show loading state
        submitButton.prop('disabled', true);
        submitButton.find('.button-text').hide();
        submitButton.find('.loading-spinner').show();
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    form[0].reset();
                    selectedFiles = [];
                    updateFilePreview();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'An error occurred. Please try again later.');
            },
            complete: function() {
                // Reset button state
                submitButton.prop('disabled', false);
                submitButton.find('.loading-spinner').hide();
                submitButton.find('.button-text').show();
            }
        });
    }

    function showAlert(type, message) {
        alertBox
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .show();
        setTimeout(() => {
            alertBox.fadeOut();
        }, 5000);
    }
});
