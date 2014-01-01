<?

/*
  File functions
 */
global $img_extensions;
$img_extensions = Array('gif', 'jpg', 'jpeg', 'png');

// ----------------- get file list of selected directory -- begin ----------
function ls($dirname) {
    if (is_dir($dirname)) {
        if ($handle = opendir($dirname)) {
            $subdirs = Array();
            $files = Array();
            while ($entry = readdir($handle)) {
                if ($entry != ".." && $entry != ".") {
                    if (@is_dir($dirname . "/" . $entry)) {
                        $subdirs[] = $entry;
                    } else {
                        if ($entry != ".." && $entry != ".")
                            $files[] = $entry;
                    }
                }
            }
            closedir($handle);
            return Array('dirs' => $subdirs, 'files' => $files);
        }
    }
    else
        return false;
}

// ----------------- get file list of selected directory -- end ------------

function file_extention($file) {
    return substr($file, strrpos($file, ".") + 1);
}

function upload_file($userfile, $desteny_dir, $prefix = 'no', $ext = 'no') {
    global $_FILES;
    //prn($_FILES);
    $tmp_name = $_FILES[$userfile]['tmp_name'];

    if (!is_uploaded_file($tmp_name))
        return false;

    $old_name = $_FILES[$userfile]['name'];
    $new_name = substr($old_name, 0, strrpos($old_name, "."));

    if ($prefix != 'no')
        $new_name = $prefix . $new_name;
    if ($ext != 'no')
        $new_name .= "." . $ext;
    else
        $new_name .= "." . file_extention($old_name);

    $err = @move_uploaded_file($tmp_name, $desteny_dir . "/" . $new_name);
    if (function_exists("chmod"))
        chmod($desteny_dir . "/" . $new_name, 0644);

    if (!$err)
        return 0;
    return $new_name;
}

// -------------------- recursive list of directory -- begin ---------------
function ls_r($dirname) {
    $response = Array();
    $dirs = Array();
    $response[] = $dirname;
    $dirs[] = $dirname;
    $depth = 0;

    while (count($dirs) > 0 && $depth++ < 10) {
        $drs = Array();
        foreach ($dirs as $drnm) {
            $r1 = ls($drnm);
            if (is_array($r1['files'])) {
                foreach ($r1['files'] as $dr)
                    $response[] = $drnm . '/' . $dr;
            }
            if (is_array($r1['dirs'])) {
                foreach ($r1['dirs'] as $dr) {
                    $drs[] = $drnm . '/' . $dr;
                    $response[] = $drnm . '/' . $dr;
                }
            }
        }
        $dirs = $drs;
    }
    return $response;
}

// -------------------- recursive list of directory -- end -----------------
// --------------------  delete directory recursively -- begin -------------
function rm_r($dirname) {
    if (is_file($dirname)) {
        unlink($dirname);
        return true;
    }
    $to_delete = ls_r($dirname);
    $to_delete = array_reverse($to_delete);
    foreach ($to_delete as $entry) {
        if (is_file($entry))
            unlink($entry);
        if (is_dir($entry))
            rmdir($entry);
    }
}

// -------------------- delete directory recursively -- end ----------------
//--------------------------- write_to_file -- begin -----------------------
function write_to_file($file_path, $file_content) {
    $fp = fopen($file_path, 'w');
    if ($fp) {
        fwrite($fp, $file_content);
        fclose($fp);
    }
    else
        return false;
}

//--------------------------- write_to_file -- end -------------------------
# run("lib/pclzip.lib");
function unzip($from_file, $to_dir) {
    // prn("$from_file, $to_dir");
    // return;
    $o_zip = new PclZip($from_file);
    $filelist = $o_zip->listContent();
    // prn($filelist);
    // return;
    $cnt = count($filelist);
    $to_extract = Array();
    for ($i = 0; $i < $cnt; $i++)
        if (preg_match('/\.(' . allowed_file_extension . ')$/', $filelist[$i]['filename']) || $filelist[$i]['folder'] == 1)
            $to_extract[] = $i;
    if (count($to_extract) > 0)
        $o_zip->extractByIndex(join(',', $to_extract), $to_dir);
    //$o_zip->extract($to_dir);
}

function path_create($root, $dir) {
    $rt = preg_replace('/\/+$/', '', $root);
    $len = strlen($rt);
    if (substr($dir, 0, $len + 1) == $rt . '/')
        $path = substr($dir, $len); else
        $path=$dir;
    $path = preg_replace('/^\/+/', '', $path);
    $path = preg_replace('/\/+$/', '', $path);
    //prn($root,$dir);
    $dirlist = explode('/', $path);

    if (preg_match('/\/$/', $dir))
        $file_name = ''; else
        $file_name=array_pop($dirlist);
    $path = $rt;
    foreach ($dirlist as $dr)
        if (!is_dir($path = $path . '/' . rawurlencode($dr)))
            mkdir($path);
    if (strlen($file_name) > 0)
        write_to_file($path . '/' . $file_name, '');
}

function path_delete($root, $dir, $verbose=false) {
    //$rt = ereg_replace('/+$', '', $root);
    $rt = preg_replace("/\\/+$/", '', $root);
    $len = strlen($rt);
    if (substr($dir, 0, $len + 1) == $rt . '/')
        $path = substr($dir, $len); else
        $path=$dir;
    // $path = $rt . '/' . ereg_replace('^/+', '', $path);
    $path = $rt . '/' . preg_replace("/^\\/+/", '', $path);


    if (is_file($path)) {
        unlink($path);
        $path = dirname($path);
    }
    while (strlen($path) > $len) {
        if (is_dir($path)) {
            $dir_content = ls($path);
            if ((count($dir_content['dirs']) + count($dir_content['files'])) == 0) {
                rmdir($path);
            }
            else
                break;
        }
        $path = dirname($path);
    }
    return strlen($path) == $len;
}

function encode_file_name($str) {
    $extension = file_extention($str);
    $pos = strrpos($str, ".");
    if ($pos === false){
       $tor = $str;
    }else{
       $tor=substr($str, 0, $pos);
    }
    //prn($tor,$extension);
    $tor = str_replace(
                    Array('ё', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'ы', 'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'э', 'ї','і',
                          'Ё', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я', 'Ы', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Э', '?')
                 , Array('yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi','i',
                         'yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi')
                    , $tor);
    $tor = preg_replace('/[^a-z0-9_-]/i', '-', $tor);
    if (strlen($tor) > 200)
        $tor = substr($tor, 0, 99) . '--' . substr($str, -1, 99);

    $tor = Array($tor);
    if (strlen($extension) > 0)
        $tor[] = strtolower($extension);
    return join('.', $tor);
}


function encode_dir_name($str) {
    $tor = str_replace(
                    Array('ё', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'ы', 'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'э', 'ї','і',
                          'Ё', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я', 'Ы', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Э', '?','ь','ъ')
                 , Array('yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi','i',
                         'yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi','','')
                    , $str);
    $tor = preg_replace('/[^a-z0-9_-]/i', '-', $tor);
    if (strlen($tor) > 200){
        $tor = substr($tor, 0, 99) . '--' . substr($str, -1, 99);
    }
    return $tor;
}


function get_cached_info($path, $cachetime=cachetime) {
    $filepath = '/' . $path;
    //prn(' reading '.$filepath);
    if (file_exists($filepath) && filemtime($filepath) > time() - $cachetime) {
        try {
            return unserialize(file_get_contents($filepath));
        } catch (Exception $e) {
            return false;
        }
    } else {
        return false;
    }
}

function set_cached_info($path, $info) {
    $filepath = $path;
    //prn('writing '.$filepath);
    path_create(sites_root, $filepath);
    file_put_contents($filepath, serialize($info));
}

?>