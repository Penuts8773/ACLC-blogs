<?php include_once __DIR__ . '/../api/gatekeeper.php'; ?>
<link rel="stylesheet" href="assets/css/postBoard.css">
<div class="postBoard-body">
    <div class="postBoard-container">
        <form method="post" action="/../api/article_create.php" id="postForm" enctype="multipart/form-data">
            <h2>Post to Board</h2>
            <div>
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>

                <!-- Subtitle removed to match DB schema -->
                </div>
                <div id="headerZone"
                    style="margin-top:10px; border:2px dashed #ccc; padding:10px; text-align:center;"
                    ondrop="handleHeaderDrop(event)" ondragover="allowDrop(event)">
                    <label>Choose or Drop Images here</label>
                    <p style="font-size:0.9em; color:#555;">Drag and drop a header image here 📷</p>
                    <input type="file" name="headerfile" accept="jpg.jpeg,.png,image/jpeg,image/png">
                    
                </div>
                
                <div id="postPreviewContainer" style="margin-top:15px;"></div>

                <!-- Dynamic Content Blocks -->
                <div id="blocksContainer" style="margin-top:10px;"></div>

                <!-- Add Block Buttons -->
                <div style="margin-top:10px;">
                    <button type="button" onclick="addTextBlock()">Add Text Block</button>
                    <button type="button" onclick="addImageBlock()">Add Image Block</button>
                </div>

                <button type="submit" id="submitBtn" style="margin-top:15px;">Submit Post</button>
            </div>
        </form>
    </div>
</div>