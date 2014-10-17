<?php
$count = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $folder_name=$_POST['folder_name'];
//    echo $folder_name;
    mkdir(__DIR__ .'/radioframa/music/sugeridos/'.$folder_name."/",0777);
    
    foreach ($_FILES['files']['name'] as $i => $name) {
        if (strlen($_FILES['files']['name'][$i]) > 1) {
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], __DIR__ .'/radioframa/music/sugeridos/'.$folder_name.'/'.$name)) {
                $count++;
            }
        }
    }
    if (count($_FILES)>0){
        echo "<script>alert('Subido!')</script>";
    }
//    var_dump($_FILES);
}
?> 

<html>
    <body>
        <h1>
            Sugerile a Frama
        </h1>
<form method="post" enctype="multipart/form-data">
    <label for="folder_name">Nombre de Artista o Album</label>
    <input type="text" value="" name="folder_name"/>
    <br>
    <input type="file" name="files[]" id="files" multiple="" directory="" webkitdirectory="" mozdirectory="">
    <br/>
    <input class="button" type="submit" value="Upload" />
    


</form>

        <div>
            <h2>Sugeridos</h2>
            <ul>
                <?php
                if ($handle = opendir(__DIR__ . '/radioframa/music/sugeridos/')) {
                    /* This is the correct way to loop over the directory. */

                    while (false !== ($entry = readdir($handle))) {
                        $extension = preg_replace('/.*\./', '', $entry);

                        if ($extension == 'mp3' || $extension == 'MP3') {
                            echo "<li>$entry</li>";
                        }
                    }

                    closedir($handle);
                }
                ?>
            </ul>
        </div>

<!--        <div>
            <h2>Playlist</h2>
            <ul>
                <?php
                if ($handle = opendir(__DIR__ . '/playlist/')) {
                    /* This is the correct way to loop over the directory. */

                    while (false !== ($entry = readdir($handle))) {
                        $extension = preg_replace('/.*\./', '', $entry);

                        if ($extension == 'mp3' || $extension == 'MP3') {
                            echo "<li>$entry</li>";
                        }
                    }

                    closedir($handle);
                }
                ?>
            </ul>
        </div>-->



        <div style="position: absolute; bottom: 10px;right: 10px;font-size: 12px;">
            <b>Radio Frama versi&oacute;n 0 realese 0.25</b>
        </div>
    </body>
</html> 
