<?php

//장바구니 추가
function postBasket($user_id, $item_id, $size,$color,$num)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Orders (user_id,item_id,size,color,num,status ) VALUES (?,?,?,?,?,000);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;
}

//직접구매
function postOrder($user_id, $item_id, $size,$color,$num)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Orders (user_id,item_id,size,color,num,status ) VALUES (?,?,?,?,?,-999);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;
}

//장바구니 아이템 리스트 조회
function getBaskets($user_id,$page)
{
    $pdo = pdoSqlConnect();
    $query_num = "select count(id) as num, ' ' as list from Orders
                where Orders.user_id = ? and Orders.status=000";

    $st_num = $pdo->prepare($query_num." limit ".(($page-1)*6).", 6;");
    //    $st->execute([$param,$param]);
    $st_num->execute([$user_id]);
    $st_num->setFetchMode(PDO::FETCH_ASSOC);
    $res_num = $st_num->fetchAll();

    $query_body = "select

Item.id as item_id,

Mall.name as mall_name,

Item.name as item_name,

' ' as image,

Orders.size as size,

Orders.color as color,

Orders.num as num,

if(
    Item.price<100000,
    concat(substr(Item.price,1,2),',',substr(Item.price,-3)),
    concat(substr(Item.price,1,3),',',substr(Item.price,-3))
    ) as price,

Mall.shipment as ship

from Orders

inner join Item
on Orders.item_id = Item.id

left join Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.status=000
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

inner join Orders
on Orders.item_id = Item.id
where Orders.user_id = ? and Orders.status=000
";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);
    $st2->execute([$user_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    for($i=0;$i<count($res_body);$i++){
        $res_body[$i]["image"] = $res_image[$i];
        $res_num[0]["list"] = $res_body;
    }

    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res;
}

//장바구니 삭제
function deleteBaskets($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Orders SET status=999 where user_id = ? and item_id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

//총액 계산
function totalPay($item1,$item2,$item3,$item4,$item5)
{
    $pdo = pdoSqlConnect();

    $query = "select Item.price as price,Mall.shipment as ship from Item left join Mall on Mall.id = Item.mall_id 
                    where Item.id=?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$item1]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_item1 = $st->fetchAll();
    $st = null;

    if($item2 == -2){
        $res_item2[][] = (Object)Array();
        $res_item2[0]['price'] = 0;
        $res_item2[0]['ship'] = 0;
    }
    else{
        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([$item2]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res_item2 = $st->fetchAll();
        $st = null;
    }

    if($item3 == -3){
        $res_item3[][] = (Object)Array();
        $res_item3[0]['price'] = 0;
        $res_item3[0]['ship'] = 0;
    }
    else{
        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([$item3]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res_item3 = $st->fetchAll();
        $st = null;
    }

    if($item4 == -4){
        $res_item4[][] = (Object)Array();
        $res_item4[0]['price'] = 0;
        $res_item4[0]['ship'] = 0;
    }
    else{
        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([$item4]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res_item4 = $st->fetchAll();
        $st = null;
    }

    if($item5 == -5){
        $res_item5[][] = (Object)Array();
        $res_item5[0]['price'] = 0;
        $res_item5[0]['ship'] = 0;
    }
    else{
        $st = $pdo->prepare($query);
        //    $st->execute([$param,$param]);
        $st->execute([$item5]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res_item5 = $st->fetchAll();
        $st = null;
    }

    $price_raw = $res_item1[0]['price'] +
             $res_item2[0]['price'] +
             $res_item3[0]['price'] +
             $res_item4[0]['price'] +
             $res_item5[0]['price'];
    $ship_raw = $res_item1[0]['ship'] +
            $res_item2[0]['ship'] +
            $res_item3[0]['ship'] +
            $res_item4[0]['ship'] +
            $res_item5[0]['ship'];
    $total_raw = $price_raw + $ship_raw;


    switch ($price_raw){
        case 0:
            $price = "0원";
            break;
        case $price_raw < 100000:
            $price = substr($price_raw,0,2).",".substr($price_raw,-3)."원";
            break;
        default:
            $price = substr($price_raw,0,3).",".substr($price_raw,-3)."원";
            break;
    }

    switch ($ship_raw){
            case 0:
                $ship = "0원";
                break;
            case $ship_raw < 10000:
                $ship = substr($ship_raw,0,1).",".substr($ship_raw,-3)."원";
                break;
            default:
                $ship = substr($ship_raw,0,2).",".substr($ship_raw,-3)."원";
                break;
        }

    switch ($total_raw) {
        case 0:
            $total = "0원";
            break;
        case $total_raw < 100000:
            $total = substr($total_raw,0,2).",".substr($total_raw,-3)."원";
            break;
        default:
            $total = substr($total_raw,0,3).",".substr($total_raw,-3)."원";
            break;
    }

    $query = "select ' ' as price, ' ' as ship, ' ' as total;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$item1]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();
    $st = null;

    $res[0]['price'] = $price;
    $res[0]['ship'] = $ship;
    $res[0]['total'] = $total;

    $pdo = null;
    return $res[0];
}

//배송지 추가
function postAddress($user_id,$name,$phone,$zipcode,$address,$address_detail,$memo)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Address (user_id,name,phone,zipcode,address,address_detail,memo) VALUES (?,?,?,?,?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$name,$phone,$zipcode,$address,$address_detail,$memo]);

    $st = null;
    $pdo = null;
}
//결제
function payment($user_id,$item1,$item2,$item3,$item4,$item5)
{
    $pdo = pdoSqlConnect();

    $query1 = "UPDATE Orders SET status=101 where user_id = ".$user_id." and id = ".$item1.";";
    $query2 = "UPDATE Orders SET status=101 where user_id = ".$user_id." and id = ".$item2.";";
    $query3 = "UPDATE Orders SET status=101 where user_id = ".$user_id." and id = ".$item3.";";
    $query4 = "UPDATE Orders SET status=101 where user_id = ".$user_id." and id = ".$item4.";";
    $query5 = "UPDATE Orders SET status=101 where user_id = ".$user_id." and id = ".$item5.";";


    if($item5 != -5){
        $st = $pdo->prepare($query1.' '.$query2.' '.$query3.' '.$query4.' '.$query5);
        $st->execute([]);
        $st = null;
    }
    else if($item4 != -4){
        $st = $pdo->prepare($query1.' '.$query2.' '.$query3.' '.$query4);
        $st->execute([]);
        $st = null;
    }
    else if($item3 != -3){
        $st = $pdo->prepare($query1.' '.$query2.' '.$query3);
        $st->execute([]);
        $st = null;
    }
    else if($item2 != -2){
        $st = $pdo->prepare($query1.' '.$query2);
        $st->execute([]);
        $st = null;
    }
    else{
        $st = $pdo->prepare($query1);
        $st->execute([]);
        $st = null;
    }

    $pdo = null;
}