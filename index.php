<?php

$imageHash = md5(@$_REQUEST['id']);
$userId    = md5(@$_REQUEST['pwuid']);
$email     = !empty($_REQUEST['email']) ? $_REQUEST['email'] : null;

define('DATA_FILE', __DIR__ . '/data/data.sql');

$needInit = !file_exists(DATA_FILE);

$db = new SQLite3(DATA_FILE);

if (!empty($_REQUEST['_showStat']) && $_REQUEST['pass'] === file_get_contents(__DIR__ . '/data/pass')) {

    if ($needInit) {
        $db->query('
        	CREATE TABLE stats (
        		id INTEGER PRIMARY KEY,
        		userId TEXT,
        		imageHash TEXT,
        		imageNumber TEXT,
        		email TEXT,
        		server TEXT
        	);
        ');
    }

    $results = $db->query('SELECT * FROM stats');

    $votesAll = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $votesAll[$row['userId']][$row['imageHash']] = $row;
    }

    $out = array();
    foreach ($votesAll as $_userId => $_images) {
        foreach ((array) $_images as $_image) {
            $_srv = unserialize($_image['server']);
            @$out[$_srv['REMOTE_ADDR']]['cnt'] += 1;
            @$out[$_srv['REMOTE_ADDR']]['via'][$_srv['HTTP_VIA']] += 1;
            @$out[$_srv['REMOTE_ADDR']]['useragent'][$_srv['HTTP_USER_AGENT']] += 1;
            @$out[$_srv['REMOTE_ADDR']]['ref'][$_srv['HTTP_REFERER']] += 1;
            @$out[$_srv['REMOTE_ADDR']]['img'][$_image['imageNumber']] += 1;
            @$out[$_srv['REMOTE_ADDR']]['email'][$_image['email']] += 1;
        }
    }

    uasort($out, function($a, $b) { return $a['cnt'] < $b['cnt']; });

    print '<pre>';
    print_r($out);
    die;
}


if (empty($_REQUEST['pwuid'])) die;
if (!stristr($_SERVER['HTTP_REFERER'], 'r-style.com/konkurs')) die;
//if (!stristr($_SERVER['HTTP_USER_AGENT'], 'Mozilla/')) die;


if (!empty($_REQUEST['get'])) {

    $results = $db->query('SELECT * FROM stats');

    $votesAll = array();
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $votesAll[$row['userId']][$row['imageHash']] = $row;
    }

    $summary = array();
    $voited = array();
    foreach ($votesAll as $_images) {
        foreach ((array) $_images as $_iid => $i) {
            if (!$i) continue;
            @$summary[$i['imageNumber']]++;

            if (empty($votesAll[$userId][$_iid])) continue;
            @$voited[$i['imageNumber']]++;
        }
    }

    print 'Pw.showStat(' . json_encode($summary) .', ' . json_encode($voited) .');';
    die;
}

if (!$email) {
    die('alert("Для голосования обязательно необходимо указать email!")');
}

if (empty($_REQUEST['id']) || !$email) die;

// проверяем не голосовали ли ранее за эту картинку с этим имейлом
$results = $db->query('SELECT COUNT(*) as cnt FROM stats WHERE imageHash = "'. sqlite_escape_string($imageHash) .'" AND email = "'. sqlite_escape_string($email) .'"');
$row = $results->fetchArray(SQLITE3_ASSOC);
if (!empty($row['cnt'])) {
    die('alert("Вы уже голосовали ранее за эту картинку")');
}

$db->exec("
    INSERT INTO stats
    (userId, imageHash, imageNumber, email, server)
    VALUES ('". sqlite_escape_string($userId) ."',
            '". sqlite_escape_string($imageHash) ."',
            '". sqlite_escape_string($_REQUEST['id']) ."',
            '". sqlite_escape_string($email) ."',
            '". sqlite_escape_string(serialize($_SERVER)) ."'
    )"
);
