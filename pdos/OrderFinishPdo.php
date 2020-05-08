<?php

//주문 완료 조회
function getOrders($user_id,$page)
{
    $pdo = pdoSqlConnect();
    $query_num = "select
concat(
        right(cast(year(Orders.update_at) as char),2),
    '.',
    if(month(Orders.update_at)<10,
        concat(0,month(Orders.update_at)),
        month(Orders.update_at)
        ),
    '.',
    if(day(Orders.update_at)<10,
        concat(0,day(Orders.update_at)),
        day(Orders.update_at)
        )
    )  as date,

Orders.order_id as order_id,
' ' as list
from Orders

where Orders.user_id = ? and 100<Orders.status and Orders.status<200";

    $st_num = $pdo->prepare($query_num." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st_num->execute([$user_id]);
    $st_num->setFetchMode(PDO::FETCH_ASSOC);
    $res_num = $st_num->fetchAll();
    $st_num = null;

    $order_id[] = (Object)Array();

    for($i = 0; $i<count($res_num); $i++){
        $order_id[$i] = $res_num[$i]['order_id'];
    }

    $query_body = "select

Orders.id as ordered_item_id,

Item.id as item_id,

Mall.name as mall_name,

' ' as mall_image,

Item.name as item_name,

' ' as item_image,

Orders.size as size,

Orders.color as color,

Orders.num as num,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3),'원'),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3),'원')
    ) as price,

Mall.shipment as ship,

Orders.status as status

from Orders

inner join Item
on Orders.item_id = Item.id

left join Mall
on Item.mall_id = Mall.id

where Orders.user_id = ?and 100<Orders.status and Orders.status<200
";

    $st_body = $pdo->prepare($query_body." limit ".(($page-1)*6).", 6;");
    $st_body->execute([$user_id]);
    $st_body->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st_body->fetchAll();


    for($i = 0; $i<count($res_num); $i++){
        $res_body[$i]['status'] = statusCodeToText($res_body[$i]['status']);
    }


    $query_image = "select

item_image.image_url1 as image_url1,
item_image.image_url2 as image_url2

from Orders

inner join (
    select
    group_concat(case when item_image.id%2=1 then image_url end) image_url1,
    group_concat(case when item_image.id%2=0 then image_url end) image_url2,
    item_id as id
    from item_image
    group by item_id
    )item_image
on Orders.item_id = item_image.id

where Orders.user_id = ? and 100<Orders.status and Orders.status<200";


    $st2 = $pdo->prepare($query_image." limit ".(($page-1)*6).", 6;");
    $st2->execute([$user_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    $query_image = "select image_url from Orders
inner join Item on Item.id = Orders.item_id
inner join Mall on Mall.id = Item.mall_id
inner join mall_image on mall_image.id = Mall.id
where Orders.user_id = ? and 100<Orders.status and Orders.status<200";


    $st3 = $pdo->prepare($query_image." limit ".(($page-1)*6).", 6;");
    $st3->execute([$user_id]);
    $st3->setFetchMode(PDO::FETCH_ASSOC);
    $res_mall_image = $st3->fetchAll();

    for($i = 0; $i<count($res_num);$i++){
        $res_body[$i]["item_image"] = $res_image[$i];
        $res_body[$i]["mall_image"] = $res_mall_image[$i];
        $res_num[$i]["list"] = $res_body[$i];
    }



    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res;
}

//주문 완료 조회
function getOrdersWithItem($user_id,$item_id,$page)
{
    $pdo = pdoSqlConnect();
    $query_num = "select
concat(
        right(cast(year(Orders.update_at) as char),2),
    '.',
    if(month(Orders.update_at)<10,
        concat(0,month(Orders.update_at)),
        month(Orders.update_at)
        ),
    '.',
    if(day(Orders.update_at)<10,
        concat(0,day(Orders.update_at)),
        day(Orders.update_at)
        )
    )  as date,

Orders.order_id as order_id,
' ' as list
from Orders

where Orders.user_id = ? and Orders.item_id = ? and 100<Orders.status and Orders.status<200";

    $st_num = $pdo->prepare($query_num." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st_num->execute([$user_id,$item_id]);
    $st_num->setFetchMode(PDO::FETCH_ASSOC);
    $res_num = $st_num->fetchAll();
    $st_num = null;

    $order_id[] = (Object)Array();

    for($i = 0; $i<count($res_num); $i++){
        $order_id[$i] = $res_num[$i]['order_id'];
    }

    $query_body = "select

Orders.id as ordered_item_id,

Item.id as item_id,

Mall.name as mall_name,

' ' as mall_image,

Item.name as item_name,

' ' as item_image,

Orders.size as size,

Orders.color as color,

Orders.num as num,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3),'원'),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3),'원')
    ) as price,

Mall.shipment as ship,

Orders.status as status

from Orders

inner join Item
on Orders.item_id = Item.id

left join Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.item_id = ? and 100<Orders.status and Orders.status<200
";

    $st_body = $pdo->prepare($query_body." limit ".(($page-1)*6).", 6;");
    $st_body->execute([$user_id,$item_id]);
    $st_body->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st_body->fetchAll();


    for($i = 0; $i<count($res_num); $i++){
        $res_body[$i]['status'] = statusCodeToText($res_body[$i]['status']);
    }


    $query_image = "select

item_image.image_url1 as image_url1,
item_image.image_url2 as image_url2

from Orders

inner join (
    select
    group_concat(case when item_image.id%2=1 then image_url end) image_url1,
    group_concat(case when item_image.id%2=0 then image_url end) image_url2,
    item_id as id
    from item_image
    group by item_id
    )item_image
on Orders.item_id = item_image.id

where Orders.user_id = ? and Orders.item_id = ? and 100<Orders.status and Orders.status<200";


    $st2 = $pdo->prepare($query_image." limit ".(($page-1)*6).", 6;");
    $st2->execute([$user_id,$item_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    $query_image = "select image_url from Orders
inner join Item on Item.id = Orders.item_id
inner join Mall on Mall.id = Item.mall_id
inner join mall_image on mall_image.id = Mall.id
where Orders.user_id = ? and Orders.item_id = ? and 100<Orders.status and Orders.status<200";


    $st3 = $pdo->prepare($query_image." limit ".(($page-1)*6).", 6;");
    $st3->execute([$user_id,$item_id]);
    $st3->setFetchMode(PDO::FETCH_ASSOC);
    $res_mall_image = $st3->fetchAll();

    for($i = 0; $i<count($res_num);$i++){
        $res_body[$i]["item_image"] = $res_image[$i];
        $res_body[$i]["mall_image"] = $res_mall_image[$i];
        $res_num[$i]["list"] = $res_body[$i];
    }

    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res;
}

//주문 완료 상세 조회
function getOrderDetail($user_id,$order_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select
Orders.status as status,
Orders.status as status_text,
'기업은행 04797044697382' as account,
'지그재그(크로키닷컴(주))' as host,
concat(
    year(Orders.update_at),
    '년 ',
    if(
        month(date_add(Orders.update_at,INTERVAL 3 DAY))<10,
        concat(0,month(date_add(Orders.update_at,INTERVAL 3 DAY))),
        month(date_add(Orders.update_at,INTERVAL 3 DAY))
        ),
    '월 ',
        if(
        day(date_add(Orders.update_at,INTERVAL 3 DAY))<10,
        concat(0,day(date_add(Orders.update_at,INTERVAL 3 DAY))),
        day(date_add(Orders.update_at,INTERVAL 3 DAY))
        ),
    '일까지'
    ) as date,
if(
    sum(Item.price)<100000,
    concat(substr(sum(Item.price),1,2),',',substr(sum(Item.price),-3),'원'),
    concat(substr(sum(Item.price),1,3),',',substr(sum(Item.price),-3),'원')
    ) as price
from Orders

inner join Item on Item.id = Orders.item_id

where Orders.user_id = ? and Orders.order_id = ?

group by status,status_text,account,host,date";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$order_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    for($i = 0; $i<count($res); $i++){
        $res[$i]['status'] = statusCodeToText($res[$i]['status']);
        $res[$i]['status_text'] = statusToComment($res[$i]['status_text']);
    }

    $st = null;
    $pdo = null;

    return $res[0];
}

//주문 취소
function deleteOrder($user_id, $item_id)
{
    $pdo = pdoSqlConnect();

    $query = "UPDATE Orders SET status=201 where user_id = ? and id = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $query = null;
    $st = null;
    $pdo = null;

    return;
}