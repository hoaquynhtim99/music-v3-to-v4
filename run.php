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

$array_alphabets = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

// Xóa dữ liệu
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_albums");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_albums_data");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_albums_random");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_artists");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_categories");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_songs");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_songs_caption");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_songs_data");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_songs_random");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_videos");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_videos_data");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_videos_random");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_user_playlists");
$db->query("TRUNCATE TABLE " . NV4_MOD_TABLE . "_user_playlists_data");
$db->query("DELETE FROM nv4_vi_comment WHERE module='music'");

// Chuyển thể loại bài hát cũ sang thể loại mới
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_category ORDER BY weight ASC";
$result = $db->query($sql);

$weight = 0;
while ($row = $result->fetch()) {
    $weight++;

    $code = nv_genpass(4);
    while ($db->query("SELECT cat_id FROM " . NV4_MOD_TABLE . "_categories WHERE cat_code=" . $db->quote($code))->fetchColumn()) {
        $code = nv_genpass(4);
    }

    $sql = "INSERT INTO " . NV4_MOD_TABLE . "_categories (
        cat_id, cat_code, resource_avatar, resource_cover, resource_video, time_add, weight, status, vi_cat_name, vi_cat_alias, vi_cat_abintrotext, vi_cat_abkeywords,
        vi_cat_mvintrotext, vi_cat_mvkeywords
    ) VALUES (
        " . $row['id'] . ", " . $db->quote($code) . ", '', '', '', " . NV_CURRENTTIME . ", " . $weight . ", 1,
        " . $db->quote($row['title']) . ", " . $db->quote(change_alias($row['title'])) . ",
        " . $db->quote($row['description']) . ", " . $db->quote($row['keywords']) . ",
        " . $db->quote($row['description']) . ", " . $db->quote($row['keywords']) . "
    )";
    $db->query($sql);

    echo "Cat song old to cat new: " . $row['id'] . " => " . $code . "" . PHP_EOL;
}

// Chuyển thể loại video cũ sang thể loại mới
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_video_category ORDER BY weight ASC";
$result = $db->query($sql);

$arrayMapVideoCat = [];

while ($row = $result->fetch()) {

    $cat_exists = $db->query("SELECT cat_id FROM " . NV4_MOD_TABLE . "_categories WHERE vi_cat_name=" . $db->quote($row['title']))->fetchColumn();
    if ($cat_exists) {
        // Cập nhật lại
        $sql = "UPDATE " . NV4_MOD_TABLE . "_categories SET
            vi_cat_mvintrotext=" . $db->quote($row['description']) . ",
            vi_cat_mvkeywords=" . $db->quote($row['keywords']) . "
        WHERE cat_id=" . $cat_exists;
        $db->query($sql);

        $arrayMapVideoCat[$row['id']] = $cat_exists;

        echo "Cat video old update to cat new: " . $row['id'] . " " . PHP_EOL;
    } else {
        // Tạo mới
        $sql = "SELECT MAX(weight) FROM " . NV4_MOD_TABLE . "_categories";
        $weight = $db->query($sql)->fetchColumn();
        $weight++;

        $code = nv_genpass(4);
        while ($db->query("SELECT cat_id FROM " . NV4_MOD_TABLE . "_categories WHERE cat_code=" . $db->quote($code))->fetchColumn()) {
            $code = nv_genpass(4);
        }

        $sql = "INSERT INTO " . NV4_MOD_TABLE . "_categories (
            cat_code, resource_avatar, resource_cover, resource_video, time_add, weight, status, vi_cat_name, vi_cat_alias, vi_cat_abintrotext, vi_cat_abkeywords,
            vi_cat_mvintrotext, vi_cat_mvkeywords
        ) VALUES (
            " . $db->quote($code) . ", '', '', '', " . NV_CURRENTTIME . ", " . $weight . ", 1,
            " . $db->quote($row['title']) . ", " . $db->quote(change_alias($row['title'])) . ",
            " . $db->quote($row['description']) . ", " . $db->quote($row['keywords']) . ",
            " . $db->quote($row['description']) . ", " . $db->quote($row['keywords']) . "
        )";
        $new_id = $db->insert_id($sql, 'cat_id');
        if (empty($new_id)) {
            die('Error: ' . $sql);
        }

        echo "Cat video old to cat new: " . $row['id'] . " => " . $code . "" . PHP_EOL;
        $arrayMapVideoCat[$row['id']] = $new_id;
    }
}

// Chuyển ca sĩ cũ sang nghệ sĩ mới
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_singer";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $code = nv_genpass(5);
    while ($db->query("SELECT artist_id FROM " . NV4_MOD_TABLE . "_artists WHERE artist_code=" . $db->quote($code))->fetchColumn()) {
        $code = nv_genpass(5);
    }

    $alphabet = nv_strtoupper(change_alias($row['tenthat']));
    $alphabet = $alphabet{0};
    if (!in_array($alphabet, $array_alphabets)) {
        $alphabet = '';
    }

    $searchkey = ' ' . trim(str_replace('-', ' ', strtolower(change_alias($row['tenthat'])))) . ' ';

    $sql = "INSERT INTO " . NV4_MOD_TABLE . "_artists (
        artist_id, artist_code, artist_type, resource_avatar, resource_cover, time_add, status,
        vi_artist_name, vi_artist_alias, vi_artist_alphabet, vi_artist_searchkey,
        vi_singer_prize, vi_singer_info, vi_singer_introtext, vi_singer_keywords,
        vi_author_prize, vi_author_info, vi_author_introtext, vi_author_keywords
    ) VALUES (
        " . $row['id'] . ", " . $db->quote($code) . ", 0, '', '', " . NV_CURRENTTIME . ", 1,
        " . $db->quote($row['tenthat']) . ", " . $db->quote($row['ten']) . ", " . $db->quote($alphabet) . ", " . $db->quote($searchkey) . ",
        '', " . $db->quote($row['introduction']) . ", '', '',
        '', '', '', ''
    )";
    $db->query($sql);

    echo "SINGER: " . $row['id'] . " => " . $code . "" . PHP_EOL;
}

/*
 * Chuyển nhạc sĩ sang nghệ sĩ mới
 * Nếu chưa có tạo mới
 * Nếu có cập nhật loại thành ca nhạc sĩ
 */
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_author";
$result = $db->query($sql);

$arrayMapAuthorID = [];

while ($row = $result->fetch()) {
    // Xem thử có trùng không
    $artist_exists = $db->query("SELECT artist_id FROM " . NV4_MOD_TABLE . "_artists WHERE vi_artist_name=" . $db->quote($row['tenthat']))->fetchColumn();
    if ($artist_exists) {
        // Cập nhật loại và mô tả
        $sql = "UPDATE " . NV4_MOD_TABLE . "_artists SET
            artist_type=2,
            vi_author_info=" . $db->quote($row['introduction']) . "
        WHERE artist_id=" . $artist_exists;
        $arrayMapAuthorID[$row['id']] = $artist_exists;

        echo "AUTHOR: " . $row['id'] . " UPDATE" . PHP_EOL;
    } else {
        // Tạo mới
        $code = nv_genpass(5);
        while ($db->query("SELECT artist_id FROM " . NV4_MOD_TABLE . "_artists WHERE artist_code=" . $db->quote($code))->fetchColumn()) {
            $code = nv_genpass(5);
        }

        $alphabet = nv_strtoupper(change_alias($row['tenthat']));
        $alphabet = $alphabet{0};
        if (!in_array($alphabet, $array_alphabets)) {
            $alphabet = '';
        }

        $searchkey = ' ' . trim(str_replace('-', ' ', strtolower(change_alias($row['tenthat'])))) . ' ';

        $sql = "INSERT INTO " . NV4_MOD_TABLE . "_artists (
            artist_code, artist_type, resource_avatar, resource_cover, time_add, status,
            vi_artist_name, vi_artist_alias, vi_artist_alphabet, vi_artist_searchkey,
            vi_singer_prize, vi_singer_info, vi_singer_introtext, vi_singer_keywords,
            vi_author_prize, vi_author_info, vi_author_introtext, vi_author_keywords
        ) VALUES (
            " . $db->quote($code) . ", 1, '', '', " . NV_CURRENTTIME . ", 1,
            " . $db->quote($row['tenthat']) . ", " . $db->quote($row['ten']) . ", " . $db->quote($alphabet) . ", " . $db->quote($searchkey) . ",
            '', '', '', '',
            '', " . $db->quote($row['introduction']) . ", '', ''
        )";
        $artist_id = $db->insert_id($sql, 'artist_id');

        if (!$artist_id) {
            die('ERROR: ' . $sql);
        }

        $arrayMapAuthorID[$row['id']] = $artist_id;

        echo "AUTHOR NEW: " . $row['id'] . " => " . $code . "" . PHP_EOL;
    }
}

// Chuyển bài hát
$sql = "SELECT * FROM " . NV3_MOD_TABLE;
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $code = nv_genpass(8);
    while ($db->query("SELECT song_id FROM " . NV4_MOD_TABLE . "_songs WHERE song_code=" . $db->quote($code))->fetchColumn()) {
        $code = nv_genpass(8);
    }

    $searchkey = ' ' . trim(str_replace('-', ' ', strtolower(change_alias($row['tenthat'])))) . ' ';

    $nhacsi = isset($arrayMapAuthorID[$row['nhacsi']]) ? $arrayMapAuthorID[$row['nhacsi']] : '';

    $sql = "INSERT INTO " . NV4_MOD_TABLE . "_songs (
        song_id, song_code, cat_ids, singer_ids, author_ids, album_ids, resource_avatar, resource_cover,
        uploader_id, uploader_name, stat_views, stat_likes, stat_comments, stat_shares, stat_downloads,
        stat_hit, time_add, time_update, is_official, show_inhome, caption_supported, status,
        vi_song_name, vi_song_alias, vi_song_searchkey, vi_song_introtext, vi_song_keywords
    ) VALUES (
        " . $row['id'] . ", " . $db->quote($code) . ", " . $db->quote($row['theloai']) . ", " . $db->quote($row['casi']) . ", " . $db->quote($nhacsi) . ",
        '', '', '', " . $row['userid'] . ", '', " . $row['numview'] . ", " . $row['binhchon'] . ", 0, 0, 0,
        0, " . $row['dt'] . ", 0, 1, 1, '', 1,
        " . $db->quote($row['tenthat']) . ", " . $db->quote($row['ten']) . ", " . $db->quote($searchkey) . ",
        '', ''
    )";
    $db->query($sql);

    // File nhạc của bài hát này
    if (!empty($row['duongdan']) and preg_match('/^http/', $row['duongdan'])) {
        $sql = "INSERT INTO " . NV4_MOD_TABLE . "_songs_data (
            song_id, quality_id, resource_server_id, resource_path, resource_duration, status
        ) VALUES (
            " . $row['id'] . ", 1, -1, " . $db->quote($row['duongdan']) . ", " . $row['duration'] . ", 1
        )";
        $db->query($sql);
    }

    echo "SONG NEW: " . $row['id'] . " => " . $code . "" . PHP_EOL;
}

// Chuyển lời bài hát
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_lyric WHERE text_lyric!=''";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $sql= "UPDATE " . NV4_MOD_TABLE . "_songs SET caption_supported='vi' WHERE song_id=" . $row['songid'];
    if ($db->exec($sql)) {
        $sql = "INSERT INTO " . NV4_MOD_TABLE . "_songs_caption (
            song_id, caption_lang, caption_file, caption_data, is_default, weight, status
        ) VALUES (
            " . $row['id'] . ", 'vi', '', " . $db->quote($row['text_lyric']) . ", 1, 1, 1
        )";
        $db->query($sql);

        echo "LYRIC SONG: " . $row['songid'] . " " . PHP_EOL;
    } else {
        echo "LYRIC BUT NO SONG: " . $row['songid'] . " " . PHP_EOL;
    }
}

// Chuyển album
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_album";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $code = nv_genpass(8);
    while ($db->query("SELECT album_id FROM " . NV4_MOD_TABLE . "_albums WHERE album_code=" . $db->quote($code))->fetchColumn()) {
        $code = nv_genpass(8);
    }

    $searchkey = ' ' . trim(str_replace('-', ' ', strtolower(change_alias($row['tname'])))) . ' ';

    // Xử lý ảnh
    if (!empty($row['thumb'])) {
        if (!preg_match('/^http/i', $row['thumb'])) {
            $row['thumb'] = str_replace('/uploads/music/thumb/', 'albums/', $row['thumb']);
        }
    }

    $sql = "INSERT INTO " . NV4_MOD_TABLE . "_albums (
        album_id, album_code, cat_ids, singer_ids, resource_avatar, resource_cover, uploader_id, stat_views,
        time_add, is_official, show_inhome, status, vi_album_name, vi_album_alias, vi_album_searchkey, vi_album_introtext, vi_album_description, vi_album_keywords
    ) VALUES (
        " . $row['id'] . ", " . $db->quote($code) . ", '', '" . $row['casi'] . "', " . $db->quote($row['thumb']) . ",
        '', 1, " . $row['numview'] . ", " . $row['addtime'] . ", 1, 1, " . $row['active'] . ",
        " . $db->quote($row['tname']) . ", " . $db->quote($row['name']) . ", " . $db->quote($searchkey) . ", '', " . $db->quote($row['describe']) . ", ''
    )";
    $db->query($sql);

    echo "ALBUM NEW: " . $row['id'] . " => " . $code . "" . PHP_EOL;

    // Các bài hát trong album
    $num_songs = 0;
    $weight = 0;

    $listsong = array_filter(array_unique(array_map('trim', explode(',', $row['listsong']))));
    if (!empty($listsong)) {
        foreach ($listsong as $song_id) {
            $sql = "SELECT * FROM " . NV4_MOD_TABLE . "_songs WHERE song_id=" . $song_id;
            $song = $db->query($sql)->fetch();

            if (!empty($song)) {
                $weight++;

                $sql = "INSERT INTO " . NV4_MOD_TABLE . "_albums_data (
                    album_id, song_id, weight, status
                ) VALUES (
                    " . $row['id'] . ", " . $song_id . ", " . $weight . ", 1
                )";
                $db->query($sql);
            }
        }
    }

    // Cập nhật ố bài hát trong album
    $sql = "UPDATE " . NV4_MOD_TABLE . "_albums SET num_songs=" . $num_songs . " WHERE album_id=" . $row['id'];
    $db->query($sql);
}

// Chuyển playlist
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_playlist";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $code = nv_genpass(8);
    while ($db->query("SELECT playlist_id FROM " . NV4_MOD_TABLE . "_user_playlists WHERE playlist_code=" . $db->quote($code))->fetchColumn()) {
        $code = nv_genpass(8);
    }

    $sql = "INSERT INTO " . NV4_MOD_TABLE . "_user_playlists (
        playlist_id, playlist_code, resource_avatar, resource_cover, userid, stat_views,
        time_add, privacy, vi_playlist_name, vi_playlist_introtext
    ) VALUES (
        " . $row['id'] . ", " . $db->quote($code) . ", '', '', " . $row['userid'] . ",
        " . $row['view'] . ", " . $row['time'] . ", 0,
        " . $db->quote($row['name']) . ", " . $db->quote($row['message']) . "
    )";
    $db->query($sql);

    echo "PLAYLIST NEW: " . $row['id'] . " => " . $code . "" . PHP_EOL;

    // Các bài hát trong palylist
    $num_songs = 0;
    $weight = 0;

    $listsong = array_filter(array_unique(array_map('trim', explode(',', $row['songdata']))));
    if (!empty($listsong)) {
        foreach ($listsong as $song_id) {
            $sql = "SELECT * FROM " . NV4_MOD_TABLE . "_songs WHERE song_id=" . $song_id;
            $song = $db->query($sql)->fetch();

            if (!empty($song)) {
                $weight++;

                $sql = "INSERT INTO " . NV4_MOD_TABLE . "_user_playlists_data (
                    playlist_id, song_id, weight, status
                ) VALUES (
                    " . $row['id'] . ", " . $song_id . ", " . $weight . ", 1
                )";
                $db->query($sql);
            }
        }
    }

    // Cập nhật ố bài hát trong album
    $sql = "UPDATE " . NV4_MOD_TABLE . "_user_playlists SET num_songs=" . $num_songs . " WHERE playlist_id=" . $row['id'];
    $db->query($sql);
}

// Bình luận album
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_comment_album";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $sql = "INSERT INTO nv4_vi_comment (
        module, area, id, pid, content, post_time, userid, post_name, post_email, post_ip, status, likes, dislikes
    ) VALUES (
        'music', 2, " . $row['what'] . ", 0, " . $db->quote($row['body']) . ", " . $row['dt'] . ", " . $row['userid'] . ", '', '', '', 1, 0, 0
    )";
    $db->query($sql);

    echo "COMMENT ALBUM NEW: " . $row['id'] . " " . PHP_EOL;
}

// Bình luận bài hát
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_comment_song";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $sql = "INSERT INTO nv4_vi_comment (
        module, area, id, pid, content, post_time, userid, post_name, post_email, post_ip, status, likes, dislikes
    ) VALUES (
        'music', 1, " . $row['what'] . ", 0, " . $db->quote($row['body']) . ", " . $row['dt'] . ", " . $row['userid'] . ", '', '', '', 1, 0, 0
    )";
    $db->query($sql);

    echo "COMMENT SONG NEW: " . $row['id'] . " " . PHP_EOL;
}

// Bình luận video
$sql = "SELECT * FROM " . NV3_MOD_TABLE . "_comment_video";
$result = $db->query($sql);

while ($row = $result->fetch()) {
    $sql = "INSERT INTO nv4_vi_comment (
        module, area, id, pid, content, post_time, userid, post_name, post_email, post_ip, status, likes, dislikes
    ) VALUES (
        'music', 3, " . $row['what'] . ", 0, " . $db->quote($row['body']) . ", " . $row['dt'] . ", " . $row['userid'] . ", '', '', '', 1, 0, 0
    )";
    $db->query($sql);

    echo "COMMENT VIDEO NEW: " . $row['id'] . " " . PHP_EOL;
}

die("COMPLETE" . PHP_EOL);
