<?php

//즐겨찾기 추가
    function postFavorite($user_id, $mall_id)
    {
        $pdo = pdoSqlConnect();
        $query = "INSERT INTO Favorite (user_id,mall_id) VALUES (?,?);";

        $st = $pdo->prepare($query);
        $st->execute([$user_id, $mall_id]);

        $st = null;
        $pdo = null;
    }

//즐겨찾기 쇼핑몰 조회
    function getFavorites($user_id,$page)
    {
        $pdo = pdoSqlConnect();
        $query_num = "select concat('즐겨찾기 ', count(id)) as num, ' ' as list from Favorite where user_id = ?";

        $st_num = $pdo->prepare($query_num." limit ".(($page-1)*6).", 6;");
//        $st_num = $pdo->prepare($query_num);
        //    $st->execute([$param,$param]);
        $st_num->execute([$user_id]);
        $st_num->setFetchMode(PDO::FETCH_ASSOC);
        $res_num = $st_num->fetchAll();

        $query_body = "select

Mall.id as mall_id,
mall_image.image_url as image_url,
Mall.name as mall_name,
' ' as tags

from Mall

inner join mall_image
on Mall.id = mall_image.id

inner join Favorite
on Favorite.mall_id = Mall.id
where Favorite.user_id = ?

";

        $st_body = $pdo->prepare($query_body." limit ".(($page-1)*6).", 6;");
        //    $st->execute([$param,$param]);
        $st_body->execute([$user_id]);
        $st_body->setFetchMode(PDO::FETCH_ASSOC);
        $res_body = $st_body->fetchAll();

        for($i=0;$i<count($res_body);$i++){
            $res_num[$i]["list"] = $res_body[$i];
        }

        $res = $res_num;

        $st = null;
        $pdo = null;

        return $res[0];
    }

//즐겨찾기 삭제
    function deleteFavorite($user_id, $mall_id)
    {
        $pdo = pdoSqlConnect();
        $query = "UPDATE Favorite SET is_deleted = 'Y' where user_id = ? and mall_id = ?;";

        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([$user_id, $mall_id]);
        $st->setFetchMode(PDO::FETCH_ASSOC);

        $st = null;
        $pdo = null;

        return;
    }