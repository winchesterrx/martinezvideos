<?php
function getYouTubeVideoID($url) {
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|.+\?v=))([^"&?\/\s]{11})/', $url, $matches);
    return $matches[1] ?? null;
}
?>
