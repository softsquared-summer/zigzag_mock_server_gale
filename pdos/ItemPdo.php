<?php

//아이템 조회(쿼리스트링 없음)
function getItems($user_id, $keyword_input, $category_input, $ship_input, $filter_input,$page){

    $pdo = pdoSqlConnect();

    $query_body = "
select

Item.id as item_id,

item_category.category_code as item_category,

item_category.category_detail_code as item_category_detail,

' ' as image,

if(
    Mall.shipment = 0,
    'Y',
    'N'
    )
as is_free_ship,

if(
    Heart.user_id = ?,
    'Y',
    'N'
    )
as is_heart,

Mall.name as mall_name,

Item.name as item_name,

concat(Item.discount,'%') as discount,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3)),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3))
    ) as price

from Item

left join Mall on Item.mall_id = Mall.id
inner join item_category on item_category.id = Item.id
left join Heart on Heart.item_id = Item.id
left join User on User.id = Heart.user_id
";

    $query = $query_body." where ".$keyword_input." and ".$category_input." and ".$ship_input." ".$filter_input." limit ".(($page-1)*6).", 6;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

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
left join Mall on Item.mall_id = Mall.id
inner join item_category on item_category.id = Item.id
left join Heart on Heart.item_id = Item.id
left join User on User.id = Heart.user_id";

    $query = $query_image." where ".$keyword_input." and ".$category_input." and ".$ship_input." ".$filter_input." limit ".(($page-1)*6).", 6;";
    $st2 = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st2->execute([]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    for($i = 0; $i<count($res_body);$i++){
        $res_body[$i]["item_image"] = $res_image[$i];
        $res_body[$i]['item_category'] = categoryCodeToText($res_body[$i]['item_category']);
        $res_body[$i]['item_category_detail'] = categoryDetailCodeToText($res_body[$i]['item_category_detail']);
    }


    $res = $res_body;

    $pdo = null;

    return $res;
}

//아이템 상세 조회
function getItemDetail($user_id, $item_id){

    $pdo = pdoSqlConnect();

    $query_body = "select

Item.id as id,

Mall.name as mall_name,

' ' as image,

Item.name as item_name,

ifnull(
    Comment.num,
    0
    ) as comment_num,

if(
    Heart.user_id = ?,
    'Y',
    'N'
    )
as is_heart,

concat(Item.discount,'%') as discount,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3)),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3))
    ) as price

from Item

left join Mall
on Item.mall_id = Mall.id

left join (
    select
    count(
        if(
            Comment.item_id = Item.id,
            1,
            null
            )
        ) as num,
        ifnull(
            Comment.item_id,
            0
        ) as item_id
    from Item
    left join Comment on Comment.item_id = Item.id
    group by Item.id
    ) Comment
on Comment.item_id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id

where Item.id = ?;";
    $st = $pdo->prepare($query_body);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,  $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $query_image = "select
group_concat(case when item_image.id%2=1 then image_url end) image_url1,
group_concat(case when item_image.id%2=0 then image_url end) image_url2
from Item

inner join item_image
on Item.id = item_image.item_id

where Item.id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    $st2->execute([$item_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    $res_body[0]["image"] = $res_image[0];
    $res = $res_body;


    $st=null;
    $pdo = null;

    return $res[0];
}

//아이템 색깔 리스트 조회 API
function getItemColors($item_id)
{
    $pdo = pdoSqlConnect();
    $query = "select
Item.id as item_id,
ifnull(
    color1,
    \"없음\"
    ) as color1,

ifnull(
    color2,
    \"없음\"
    ) as color2,

ifnull(
    color3,
    \"없음\"
    ) as color3

from item_color

inner join Item
on item_color.id = Item.id

where item_color.id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

//아이템 색깔 리스트 조회 API
function getItemSizes($item_id)
{
    $pdo = pdoSqlConnect();
    $query = "select
Item.id as item_id,
ifnull(
    size1,
    \"없음\"
    ) as color1,

ifnull(
    size2,
    \"없음\"
    ) as color2,

ifnull(
    size3,
    \"없음\"
    ) as color3

from item_size

inner join Item
on item_size.id = Item.id

where item_size.id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}