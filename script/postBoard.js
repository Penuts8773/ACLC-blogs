// State
let headerImageData = '';
let extraBlocks = [];
let currentImageDataArray = []; // For multiple images

// Show text input + image drop zone, hide "Add to Post" button

// Add an image block
function allowDrop(event) {
    event.preventDefault();
}

function handleHeaderDrop(event) {
    event.preventDefault();

    const hz = document.getElementById('headerZone');

    const files = event.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        const file = files[0];
        const reader = new FileReader();
        reader.onload = function (e) {
            hz.querySelectorAll('img').forEach(img => img.remove());

            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = "Header image";
            img.style.maxWidth = "100%";
            img.style.marginTop = "10px";
            hz.appendChild(img);

            window.headerImageData = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        alert("Only image files are allowed.");
    }
}

// Make drop zone clickable
document.addEventListener('DOMContentLoaded', function () {
    const hz = document.getElementById('headerZone');
    const dropZone = hz.querySelector('#dropzone');
    const fileInput = hz.querySelector('input[type="file"]');

    hz.addEventListener('click', function (e) {
        // Ignore clicks directly on the file input itself
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            const fakeEvent = {
                preventDefault: () => {},
                dataTransfer: { files: fileInput.files }
            };
            handleHeaderDrop(fakeEvent);
        }
    });

    attachHeaderImageInputListener();
});

function attachHeaderImageInputListener() {
    const headerInput = document.querySelector('#headerZone input[type="file"]');
    if (headerInput) {
        headerInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file || !file.type.startsWith('image/')) {
                alert("Only image files are allowed.");
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove any previous header images
                const hz = document.getElementById('headerZone');
                hz.querySelectorAll('img').forEach(img => img.remove());
                // Insert new image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = "Header image";
                img.style.maxWidth = "100%";
                img.style.marginTop = "10px";
                hz.appendChild(img);
                headerImageData = e.target.result;
            };
            reader.readAsDataURL(file);
            // Reset input so same file can be selected again if needed
            event.target.value = '';
        });
    }
}

// Handle form submission; collect all data into hidden fields
document.getElementById('postForm').addEventListener('submit', function(e) {
    // Before submitting, add extra blocks as hidden fields
    // Remove previous hidden fields if any
    document.querySelectorAll('.hidden-extra-block').forEach(el => el.remove());
    extraBlocks.forEach((block, idx) => {
        const inputText = document.createElement('input');
        inputText.type = 'hidden';
        inputText.name = `extraBlocks[${idx}][text]`;
        inputText.value = block.text;
        inputText.className = 'hidden-extra-block';
        this.appendChild(inputText);

        if (block.images && block.images.length > 0) {
            block.images.forEach((imgData, imgIdx) => {
                const inputImage = document.createElement('input');
                inputImage.type = 'hidden';
                inputImage.name = `extraBlocks[${idx}][images][${imgIdx}]`;
                inputImage.value = imgData;
                inputImage.className = 'hidden-extra-block';
                this.appendChild(inputImage);
            });
        }
    });
    // Also add header image as a hidden field
    let headerField = document.getElementById('headerImageField');
    if (!headerField) {
        headerField = document.createElement('input');
        headerField.type = 'hidden';
        headerField.id = 'headerImageField';
        headerField.name = 'headerImage';
        this.appendChild(headerField);
    }
    headerField.value = headerImageData;
    // Proceed to submit
    // e.preventDefault(); // Uncomment to debug and prevent actual submit
});




function addTextBlock() {
        const container = document.getElementById('blocksContainer');
        const blockIdx = container.children.length;
        const blockDiv = document.createElement('div');
        blockDiv.className = 'block';
        blockDiv.innerHTML = `
            <label>Text Block</label>
            <textarea name="block_content[]" rows="4" cols="50" required></textarea>
            <input type="hidden" name="block_type[]" value="text">
            <button type="button" onclick="this.parentNode.remove()">Remove</button>
            <hr>
        `;
        container.appendChild(blockDiv);
    }

    // Add an image block
    function addImageBlock() {
        const container = document.getElementById('blocksContainer');
        const blockIdx = container.children.length;
        const blockDiv = document.createElement('div');
        blockDiv.className = 'block';
        blockDiv.innerHTML = `
            <label>Image Block</label>
            <div class="image-drop-zone" style="border:2px dashed #ccc; padding:10px; text-align:center; margin-bottom:5px;" draggable="false">
                <p style="font-size:0.9em; color:#555;">Drag and drop an image here or click to select</p>
                <input type="file" name="block_content[]" accept="image/*" style="display:none;" required>
                <img src="" style="display:none; max-width:100%; max-height:150px; margin-top:5px;" alt="Preview">
            </div>
            <input type="hidden" name="block_type[]" value="image">
            <button type="button" onclick="this.parentNode.remove()">Remove</button>
            <hr>
        `;
        container.appendChild(blockDiv);

        // Setup drag-and-drop and preview
        const dropZone = blockDiv.querySelector('.image-drop-zone');
        const fileInput = dropZone.querySelector('input[type="file"]');
        const imgPreview = dropZone.querySelector('img');

        // Click to open file dialog
        dropZone.addEventListener('click', function(e) {
            if (e.target === dropZone || e.target.tagName === 'P') fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    imgPreview.src = ev.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(fileInput.files[0]);
            }
        });

        // Drag over
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.style.background = '#eef';
        });
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.style.background = '';
        });
        // Drop
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.style.background = '';
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                fileInput.files = e.dataTransfer.files;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    imgPreview.src = ev.target.result;
                    imgPreview.style.display = 'block';
                };
                reader.readAsDataURL(e.dataTransfer.files[0]);
            }
        });
    }