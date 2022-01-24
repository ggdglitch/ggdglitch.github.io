<?php
define('CHUNK_SIZE', 1024*1024); // Size (in bytes) of tiles chunk

// Read a file and display its content chunk by chunk
function readfile_chunked($filename, $retbytes = TRUE) {
    $buffer = '';
    $cnt    = 0;
    $handle = fopen($filename, 'rb');

    if ($handle === false) {
        return false;
    }
	
	$size = filesize($filename);
	
	WriteString(str_replace(getcwd() . "/", "", $filename));
	WriteLong($size);

    while (!feof($handle)) {
        $buffer = fread($handle, CHUNK_SIZE);
        echo $buffer;
		ob_flush();
        flush();

        if ($retbytes) {
            $cnt += strlen($buffer);
        }
    }

    $status = fclose($handle);

    if ($retbytes && $status) {
        return $cnt; // return num. bytes delivered like readfile() does.
    }

    return $status;
}


//Get list of allowed files
function scanD($target, $obj) {

	$excludeFiles = array("resources/mapcache.db", "update.json", "version.json");
	$clientExcludeFiles = array("Intersect Editor.exe", "Intersect Editor.pdb");
	$excludeDirectories = array("logs", "screenshots");
	$excludeExtensions = array(".dll", ".xml", ".config", ".php");

	if(is_dir($target)){

		$skipDir = false;
		$dir = str_replace(getcwd() . "/", "", $target);
		foreach ($excludeDirectories as $excludeDir) {
			if (endsWith($dir, $excludeDir . "/")) {
				$skipDir = true;
				break;
			}
		}

		if ($skipDir == false) {
			$files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

			foreach( $files as $file )
			{
				scanD( $file, $obj );

				$skip = is_dir($file);

				foreach ($excludeExtensions as $extension) {
					if (endsWith($file, $extension)) {
						$skip = true;
						break;
					}
				}

				$path = str_replace(getcwd() . "/", "", $file);

				if (in_array($path, $excludeFiles)) {
					$skip = true;	
				}

				if ($skip == false) {
					if (in_array($path,$clientExcludeFiles)) {
						$obj->Files[] = $path;
					}
					else {
						$obj->Files[] = $path;
					}
				}

			}
		}


	} 
}

function WriteString($val) {
    Write7BitEncodedInt(strlen($val));
    echo $val;
}


function Write7BitEncodedInt($val) {
    while ($val >= 0x80) {
      echo chr((($val % 256) | 0x80));
      $val >>= 7;
    }
    echo chr((($val % 256)));
}

function WriteLong($val) {
    echo chr($val % 256);
    echo chr(($val >> 8) % 256);
    echo chr(($val >> 16) % 256);
    echo chr(($val >> 24) % 256);
    echo chr(($val >> 32) % 256);
    echo chr(($val >> 40) % 256);
    echo chr(($val >> 48) % 256);
    echo chr(($val >> 56) % 256);
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}

	return (substr($haystack, -$length) === $needle);
}

$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);
if( isset($data) && !empty($data) )
{
	header('Content-Type: application/octet-stream');
	$obj->Files = [];
	$path = getcwd();
	scanD($path, $obj);

	//Permitted files are in $obj->Files[]
	foreach ($data as $file) {
		if (in_array($file, $obj->Files)) {
			//echo "File found " . $file;
			readfile_chunked(getcwd() . "/" . $file);
		}
		else {
			//echo "File not found? " . $file;	
		}
	}
	
}

?>