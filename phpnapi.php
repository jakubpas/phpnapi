#!/usr/bin/php
<?php
/**
 * @author Jakub Pas <jakubpas@gmail.com>
 */
if ($argc < 2) {
    echo 'Usage: ' . $argv[0] . ' [lang] file1, file2...' . PHP_EOL;
    exit;
}
array_shift($argv);
$lang = in_array($argv[0], ['PL', 'EN']) ? array_shift($argv) : 'PL';

foreach ($argv as $file) {
    if (!file_exists($file)) {
        echo 'File ' . $file . ' not found' . PHP_EOL;
        continue;
    }
    if (!download($file, $lang)) {
        echo 'Subtitles for ' . $file . ' not found' . PHP_EOL;
        continue;
    }

}

function download($file, $lang)
{
    $md5 = md5(file_get_contents($file, false, null, 0, 10485760));
    $checksum = checksum($md5);
    $url = "http://napiprojekt.pl/unit_napisy/dl.php?l=$lang&f=$md5&t=$checksum&v=other&kolejka=false&nick=&pass=&napios=posix";
    $compressedFile = reset(explode(".", $file)) . '.7z';
    $subtitlesFile = reset(explode(".", $file)) . '.srt';
    $subs = file_get_contents($url);
    if ($subs == 'NPc0') {
        return false;
    }
    file_put_contents($compressedFile, $subs);
    shell_exec(
        '7z x -y -piBlm8NTigvru0Jr0 "' . $compressedFile . '" 2>/dev/null 1>/dev/null && mv ' . $md5 . '.txt "' . $subtitlesFile . '"'
    );
    unlink($compressedFile);
    echo 'Downloaded subtitles for ' . $file . PHP_EOL;
    return true;
}

function checksum($md5)
{
    $checksum = '';
    $idx = [0xe, 0x3, 0x6, 0x8, 0x2];
    $mul = [2, 2, 5, 4, 3];
    $add = [0, 0xd, 0x10, 0xb, 0x5];
    for ($i = 0; $i < count($idx); $i++) {
        $a = $add[$i];
        $m = $mul[$i];
        $d = $idx[$i];
        $t = $a + hexdec($md5[$d]);
        $v = hexdec(substr($md5, $t, 2));
        $checksum .= substr(dechex($v * $m), -1);
    }
    return $checksum;
}
