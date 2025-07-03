<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
</head>
<body>
    <form method="post">
        <h2>Post to Board</h2>
        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>

                <label for="content">Content</label>
                <textarea id="content" name="content" required></textarea>
            </div>
            <div id="headerZone"
                style="margin-top:10px; border:2px dashed #ccc; padding:10px; text-align:center;"
                ondrop="handleHeaderDrop(event)" ondragover="allowDrop(event)">
                <p style="font-size:0.9em; color:#555;">Drag and drop a header image here 📷</p>
            </div>
            <div id="postPreviewContainer" style="margin-top:15px;"></div>
            <!-- Button to reveal editor -->
            <button type="button" id="showEditorBtn" onClick="showEditor()">Add to Post</button>

            <!-- Text Input Container -->
            <div id="textInputContainer" style="display:none; margin-top:10px;">
              <textarea id="extraText" rows="4" cols="50" placeholder="Type here..." required></textarea>
            </div>

            <!-- Image Drop Container -->
            <div id="imageDropZone"
                style="display: none; margin-top:10px; border:2px dashed #ccc; padding:10px; text-align:center;"
                ondrop="handleDrop(event)" ondragover="allowDrop(event)">
                <p style="font-size:0.9em; color:#555;">Drag and drop images here 📷</p>
                <div id="imagePreviewContainer"></div>
            </div>
            
            
            <button type="button" id="addBlockBtn" style="display:none; margin-top:10px;" onClick="addBlock()">Add Block</button>

            <button type="submit" id="submitBtn" style="margin-top:15px;">Submit Post</button>
        </div>
    </form>
    <script src="script/postBoard.js" defer></script>
</body>
</html>