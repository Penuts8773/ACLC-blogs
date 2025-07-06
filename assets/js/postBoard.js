// Add a text block
function addTextBlock() {
    const container = document.getElementById('blocksContainer');
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

    const dropZone = blockDiv.querySelector('.image-drop-zone');
    const fileInput = dropZone.querySelector('input[type="file"]');
    const imgPreview = dropZone.querySelector('img');

    dropZone.addEventListener('click', function (e) {
        if (e.target === dropZone || e.target.tagName === 'P') fileInput.click();
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                imgPreview.src = ev.target.result;
                imgPreview.style.display = 'block';
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.style.background = '#eef';
    });

    dropZone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        dropZone.style.background = '';
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.style.background = '';
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            fileInput.files = e.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = function (ev) {
                imgPreview.src = ev.target.result;
                imgPreview.style.display = 'block';
            };
            reader.readAsDataURL(e.dataTransfer.files[0]);
        }
    });
}

// Initialize with one text block when page loads
addTextBlock();