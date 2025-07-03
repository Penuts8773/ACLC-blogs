<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Board - ACLC Blogs</title>
</head>
<body class="postBoard-body">
    <form method="post">
        <h2>Post to Board</h2>
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>

        <label for="content">Content</label>
        <textarea id="content" name="content" required></textarea>

        <label for="option">Choose: </label>
        <button type="submit" name="option" value="image">Add image</button>
        <button type="submit" name="option" value="text">Add text</button>
    </form>
    
</body>
</html>