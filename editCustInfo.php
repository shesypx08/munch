<?php
// Legacy helper page kept valid so the project does not produce PHP parse errors.
?>
<form action="uploadProfileImage.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="profileImage" accept="image/*" required>
    <button type="submit" class="btn">Upload Image</button>
</form>
