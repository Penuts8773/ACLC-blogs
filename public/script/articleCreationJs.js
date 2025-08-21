document.querySelector('form').onsubmit = (e) => {
    e.preventDefault();
    
    // Validate thumbnail (first block must be image)
    const firstBlockType = document.querySelector('input[name="types[]"]').value;
    const firstBlockContent = document.querySelector('input[name="blocks[]"]').value;
    
    if (firstBlockType !== 'image' || !firstBlockContent) {
        alert('First block must be an image (thumbnail) and is required.');
        return;
    }
    
    showConfirmModal('Are you sure you want to publish this article?', () => {
        e.target.submit();
    });
};

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
    const wrapper = select.closest('.block').querySelector('.block-content');
    wrapper.innerHTML = ''; // clear previous content

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

        wrapper.appendChild(drop);
    } else {
        wrapper.innerHTML = `<textarea name="blocks[]" placeholder="Content" required></textarea>`;
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