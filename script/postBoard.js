// State
let headerImageData = '';
let extraBlocks = [];
let currentImageDataArray = []; // For multiple images

// Show text input + image drop zone, hide "Add to Post" button
function showEditor() {
    document.getElementById('showEditorBtn').style.display = 'none';
    document.getElementById('textInputContainer').style.display = 'block';
    document.getElementById('imageDropZone').style.display = 'block';
    document.getElementById('addBlockBtn').style.display = 'inline-block';
}

// Drag and drop handler helpers
function allowDrop(event) {
    event.preventDefault();
}

function handleHeaderDrop(event) {
    event.preventDefault();
    const files = event.dataTransfer.files;
    if (files.length > 0 && files[0].type.startsWith('image/')) {
        const file = files[0];
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
    } else {
        alert("Only image files are allowed.");
    }
}

// Updated to support multiple images
function handleDrop(event) {
    event.preventDefault();
    const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
    if (files.length === 0) {
        alert("Only image files are allowed.");
        return;
    }

    const idz = document.getElementById('imageDropZone');

    // Do NOT clear previews or array, just append new ones!
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Insert preview image
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = "Block image";
            img.style.maxWidth = "100px";
            img.style.margin = "5px";
            idz.appendChild(img);
            currentImageDataArray.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });
}

// Add the text+images block to preview and state
function addBlock() {
    const text = document.getElementById('extraText').value.trim();
    if (!text) {
        alert("Please provide text for this block.");
        return;
    }

    // Save block
    extraBlocks.push({
        text: text,
        images: [...currentImageDataArray]
    });

    // Render preview
    const wrapper = document.createElement('div');
    wrapper.className = 'preview-block';
    if (text) {
        const textPara = document.createElement('p');
        textPara.textContent = text;
        wrapper.appendChild(textPara);
    }
    if (currentImageDataArray.length > 0) {
        currentImageDataArray.forEach(imgData => {
            const img = document.createElement('img');
            img.src = imgData;
            img.alt = "Block image";
            img.style.maxWidth = "100px";
            img.style.margin = "5px";
            wrapper.appendChild(img);
        });
    }
    document.getElementById('postPreviewContainer').appendChild(wrapper);

    // Reset editor for new block
    document.getElementById('extraText').value = '';
    currentImageDataArray = [];
    // Remove all images inside imageDropZone except the instruction text
    const idz = document.getElementById('imageDropZone');
    idz.innerHTML = `<p style="font-size:0.9em; color:#555;">Drag and drop images here 📷</p>`;
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