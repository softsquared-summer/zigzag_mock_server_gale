<?php

//하트 추가
function postHeart($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Heart (user_id,item_id) VALUES (?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $item_id]);

    $st = null;
    $pdo = null;
}

//하트 삭제
function deleteHeart($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Heart SET is_deleted = 'Y' where user_id = ? and item_id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

//하트 개수 조회
function getHearts($user_id,$page)
{
    $pdo = pdoSqlConnect();
    $query_num = "select concat('찜한 상품 ', count(id)) as num, ' ' as list from Heart where user_id = ?";

    $st_num = $pdo->prepare($query_num." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st_num->execute([$user_id]);
    $st_num->setFetchMode(PDO::FETCH_ASSOC);
    $res_num = $st_num->fetchAll();

    $query_body = "
select

Item.id as item_id,

' ' as image,

if(
    Mall.shipment = 0,
    'Y',
    'N'
    )
as is_free_ship,

Mall.name as mall_name,

Item.name as item_name,

concat(Item.discount,'%') as discount,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3)),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3))
    ) as price

from Item

left join Mall
on Item.mall_id = Mall.id

inner join item_category
on item_category.id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id

where Heart.user_id = ?
";

    $st_body = $pdo->prepare($query_body." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st_body->execute([$user_id]);
    $st_body->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st_body->fetchAll();

    $query_image = "select

item_image.image_url1 as image_url1,
item_image.image_url2 as image_url2

from Item

inner join (
    select
    group_concat(case when item_image.id%2=1 then image_url end) image_url1,
    group_concat(case when item_image.id%2=0 then image_url end) image_url2,
    item_id as id
    from item_image
    group by item_id
    )item_image
on Item.id = item_image.id

left join Mall
on Item.mall_id = Mall.id

inner join item_category
on item_category.id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id

where Heart.user_id = ?";


    $st2 = $pdo->prepare($query_image." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);

    $st2->execute([$user_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    for($i=0;$i<count($res_body);$i++){
        $res_body[$i]["image"] = $res_image[$i];
        $res_num[$i]["list"] = $res_body[$i];
    }

    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res[0];
}