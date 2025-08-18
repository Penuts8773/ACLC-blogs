document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Collect blocks data
    const blocks = [];
    
    document.querySelectorAll('.block').forEach((block, index) => {
        const type = block.querySelector('select').value;
        let content;
        
        if (type === 'text') {
            content = block.querySelector('textarea').value;
        } else {
            content = block.querySelector('input[name="blocks[]"]').value;
        }
        
        blocks.push({
            type: type,
            content: content,
            sort_order: index
        });
    });
    
    // Validate thumbnail
    if (blocks.length === 0 || blocks[0].type !== 'image') {
        alert('First block must be an image (thumbnail).');
        return;
    }
    
    // Show confirmation modal
    showConfirmModal('Save changes to this article?', () => {
        // Remove any existing hidden input
        const oldInput = document.getElementById('blocksData');
        if (oldInput) oldInput.remove();
        
        // Add hidden input with blocks data
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'blocksData';
        hiddenInput.id = 'blocksData';
        hiddenInput.value = JSON.stringify(blocks);
        this.appendChild(hiddenInput);
        
        // Submit the form
        this.submit();
    });
});

function addBlock() {
    const block = document.createElement('div');
    block.className = 'block';
    
    // Create block header
    const header = document.createElement('div');
    header.className = 'block-header';
    
    // Create select element
    const select = document.createElement('select');
    select.name = 'types[]';
    select.innerHTML = `
        <option value="text">Text</option>
        <option value="image">Image</option>
    `;
    select.onchange = (e) => handleTypeChange(e.target);
    
    // Create remove button
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-block';
    removeBtn.textContent = 'Remove';
    removeBtn.onclick = () => block.remove();
    
    // Create content container
    const content = document.createElement('div');
    content.className = 'block-content';
    
    // Create textarea as default content
    const textarea = document.createElement('textarea');
    textarea.name = 'blocks[]';
    textarea.placeholder = 'Content';
    textarea.required = true;
    
    // Assemble the block
    header.appendChild(select);
    header.appendChild(removeBtn);
    content.appendChild(textarea);
    block.appendChild(header);
    block.appendChild(content);
    
    document.getElementById('blocks').appendChild(block);
}

function handleTypeChange(select) {
    const block = select.closest('.block');
    const content = block.querySelector('.block-content');
    content.innerHTML = ''; // clear previous content

    if (select.value === 'image') {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'blocks[]';
        hiddenInput.required = true;

        const drop = document.createElement('div');
        drop.className = 'drop';
        drop.ondragover = e => e.preventDefault();
        drop.ondrop = e => handleDrop(e, drop);
        drop.onclick = () => handleClick(drop);
        drop.innerHTML = `<span>Drag & drop image or click</span>`;
        drop.appendChild(hiddenInput);

        content.appendChild(drop);
    } else {
        const textarea = document.createElement('textarea');
        textarea.name = 'blocks[]';
        textarea.placeholder = 'Content';
        textarea.required = true;
        content.appendChild(textarea);
    }
}
function handleDrop(event, dropZone) {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    if (!file.type.startsWith('image/')) return alert('Only images allowed');

    const reader = new FileReader();
    reader.onload = function(e) {
        const input = dropZone.querySelector('input[type=hidden]');
        input.value = e.target.result;
        dropZone.innerHTML = `<img src="${e.target.result}" style="max-width: 200px">`;
        dropZone.appendChild(input);
    };
    reader.readAsDataURL(file);
}

function handleClick(dropZone) {
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = e => {
        const file = e.target.files[0];
        if (!file.type.startsWith('image/')) return alert('Only images allowed');
        
        const reader = new FileReader();
        reader.onload = evt => {
            const input = dropZone.querySelector('input[type=hidden]');
            input.value = evt.target.result;
            dropZone.innerHTML = `<img src="${evt.target.result}" style="max-width: 200px">`;
            dropZone.appendChild(input);
        };
        reader.readAsDataURL(file);
    };
    fileInput.click();
}

// Remove the removeBlock function since we're handling it inline
