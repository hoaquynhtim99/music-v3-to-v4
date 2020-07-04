<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate 31/05/2010, 00:36
 */

$_SERVER['SERVER_NAME'] = 'musicv3to4.customer.my';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('NV_SYSTEM', true);

// Xac dinh thu muc goc cua site
define('NV_ROOTDIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(pathinfo(__file__, PATHINFO_DIRNAME) . '/..')) );

require NV_ROOTDIR . '/includes/mainfile.php';
require NV_ROOTDIR . '/includes/core/user_functions.php';

define('NV4_MOD_TABLE', 'nv4_music');
define('NV3_MOD_TABLE', 'nv3_vi_music');

// Thống kê cho nghệ sĩ
$sql = "SELECT * FROM " . NV4_MOD_TABLE . "_artists";
$result = $db->query($sql);

while ($row = $result->fetch()) {

    $stat_singer_albums = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_albums WHERE FIND_IN_SET(" . $row['artist_id'] . ", singer_ids)")->fetchColumn());
    $stat_singer_songs = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_songs WHERE FIND_IN_SET(" . $row['artist_id'] . ", singer_ids)")->fetchColumn());
    $stat_singer_videos = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_videos WHERE FIND_IN_SET(" . $row['artist_id'] . ", singer_ids)")->fetchColumn());

    $stat_author_songs = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_songs WHERE FIND_IN_SET(" . $row['artist_id'] . ", author_ids)")->fetchColumn());
    $stat_author_videos = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_videos WHERE FIND_IN_SET(" . $row['artist_id'] . ", author_ids)")->fetchColumn());

    $sql = "UPDATE " . NV4_MOD_TABLE . "_artists SET
        stat_singer_albums=" . $stat_singer_albums . ",
        stat_singer_songs=" . $stat_singer_songs . ",
        stat_singer_videos=" . $stat_singer_videos . ",
        stat_author_songs=" . $stat_author_songs . ",
        stat_author_videos=" . $stat_author_videos . ",
    WHERE artist_id=" . $row['artist_id'];

    echo "UPDATE ARTISTS " . $row['artist_code'] . " => SA: " . $stat_singer_albums . " SS: " . $stat_singer_songs . " SV: " . $stat_singer_videos . " AS: " . $stat_author_songs . " AV: " . $stat_author_videos . "" . PHP_EOL;
}

// Thống kê cho thể loại
$sql = "SELECT * FROM " . NV4_MOD_TABLE . "_categories";
$result = $db->query($sql);

while ($row = $result->fetch()) {

    $stat_albums = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_albums WHERE FIND_IN_SET(" . $row['cat_id'] . ", cat_ids)")->fetchColumn());
    $stat_songs = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_songs WHERE FIND_IN_SET(" . $row['cat_id'] . ", cat_ids)")->fetchColumn());
    $stat_videos = intval($db->query("SELECT COUNT(*) FROM " . NV4_MOD_TABLE . "_videos WHERE FIND_IN_SET(" . $row['cat_id'] . ", cat_ids)")->fetchColumn());

    $sql = "UPDATE " . NV4_MOD_TABLE . "_categories SET
        stat_albums=" . $stat_albums . ",
        stat_songs=" . $stat_songs . ",
        stat_videos=" . $stat_videos . "
    WHERE cat_id=" . $row['cat_id'];

    echo "UPDATE CAT " . $row['cat_code'] . " => A: " . $stat_albums . " S: " . $stat_songs . " V: " . $stat_videos . "" . PHP_EOL;
}

die("COMPLETE" . PHP_EOL);
