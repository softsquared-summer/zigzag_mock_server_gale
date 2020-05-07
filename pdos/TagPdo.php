<?php

//태그 추가
function postTag($user_id,$mall_id,$tag_name)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Tag (user_id,mall_id,name) VALUES (?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$mall_id,$tag_name]);

    $st = null;
    $pdo = null;
}

//최근 사용한 태그 조회
function getTagRecent($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT id as tag_id, name as tag_name FROM Tag where user_id = ? order by create_at desc";

    $st = $pdo->prepare($query." limit 0, 6;");
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//태그 조회
function getTag($user_id,$page)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT
    id as tag_id,
    name as tag_name,
    count(id) as tag_num

FROM Tag

where user_id = ? and is_deleted = 'N'
GROUP BY id
order by create_at";

    $st = $pdo->prepare($query." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//태그 삭제
function deleteTag($user_id, $tag_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Tag SET is_deleted = 'Y' where user_id = ? and id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}