<?php
foreach ($_GET['listItem'] as $position => $item)
{
    $sql[] = "UPDATE playlistItems SET seq = $position WHERE playlistItemID = $item"; 
}

print_r ($sql); 
?>