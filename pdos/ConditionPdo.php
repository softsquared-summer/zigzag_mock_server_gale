<?php

//페이지 오버되었는지 확인
function isOverPage($text,$page){

    $pdo = pdoSqlConnect();
    $query = "select if(ceil(count(id)/6)<?,1,0) as exist from ".$text;


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$page]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//페이지 오버되었는지 확인
function isItemOverPage($keyword_input,$category_input,$ship_input,$filter_input,$page){

    $pdo = pdoSqlConnect();
    $query = "
select if(ceil(count(Item.id)/6)<?,1,0) as exist from Item

left join Mall on Item.mall_id = Mall.id
inner join item_category on item_category.id = Item.id
left join Heart on Heart.item_id = Item.id
left join User on User.id = Heart.user_id

";


    $st = $pdo->prepare($query." where ".$keyword_input." and ".$category_input." and ".$ship_input." ".$filter_input);
    //    $st->execute([$param,$param]);
    $st->execute([$page]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//페이지 오버되었는지 확인
function isMallOverPage($tag_id,$page){

    $pdo = pdoSqlConnect();
    $query = "
select

if(ceil(count(Mall.id)/6)<?,1,0) as exist

from  Mall

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

left join (
    select
count(
	if(
		Favorite.mall_id = Mall.id,
        1,
        null
    )
) as num,
ifnull(
	Favorite.mall_id,
    0
) as mall_id
from Mall

left join Favorite
on Mall.id = Favorite.mall_id

group by Mall.id
    ) mall_rank
on mall_rank.mall_id = Mall.id

left join Tag
    on Tag.mall_id = Mall.id

where Tag.id = ?

";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$page,$tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//페이지 오버되었는지 확인
function isBasketOverPage($user_id,$page){

    $pdo = pdoSqlConnect();
    $query = "
select if(ceil(count(Orders.id)/6)<?,1,0) as exist

from Orders

inner join Item
on Orders.item_id = Item.id

left join Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.status=000
";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$page,$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 이메일인지 판단
function isExistEmail($email){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where email = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 번호인지 판단
function isExistPhone($phone){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where phone = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 쇼핑몰인지 판단
function isExistMall($mall_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Mall where id = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$mall_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 태그인지 판단
function isExistTag($user_id,$tag_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Tag where user_id = ? and id = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 아이템인지 판단
function isExistItem($item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Item where id = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 하트인지 판단
function isExistHeart($user_id,$item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Heart where user_id = ? and item_id = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 즐겨찾기인지 판단
function isExistFavorite($user_id,$mall_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Favorite where user_id = ? and mall_id = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$mall_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 카테고리인지 판단
function isExistCategory($text){
    $temp = categoryTextToCode($text);
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM item_category where category_code = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$temp]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

function isExistCategoryDetail($text){
    $temp = categoryDetailTextToCode($text);
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM item_category where category_detail_code = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$temp]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 리뷰인지 판단
function isExistComment($user_id,$comment_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Comment where user_id = ? and id = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$comment_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 리뷰인지 판단
function isExistItemOnComment($user_id,$item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Comment where user_id = ? and item_id = ? and is_deleted = 'N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 리뷰인지 판단
function isExistAddress($zipcode,$address,$address_detail){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Address where zipcode = ? and address = ? and address_detail = ? and is_deleted='N') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$zipcode,$address,$address_detail]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}
//
////기존에 있는 리뷰인지 판단
//function isExistCompletion($user_id,$completion_id){
//
//    $pdo = pdoSqlConnect();
//    $query = "SELECT EXISTS(SELECT * FROM Completion where user_id = ? and id = ?) as exist";
//
//
//    $st = $pdo->prepare($query);
//    //    $st->execute([$param,$param]);
//    $st->execute([$user_id,$completion_id]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st=null;$pdo = null;
//
//    return intval($res[0]["exist"]);
//}

//상품에 존재하는 색깔인지 판단
function isExistColor($item_id, $color){

    $pdo = pdoSqlConnect();
    $query = "
select

if(
    (item_color.color1 = ?
    or item_color.color2 = ?
    or item_color.color3 = ?),
    1,
    0
    ) as exist

from Item

inner join item_color
on Item.id = item_color.id

where Item.id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$color,$color,$color,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//상품에 존재하는 사이즈인지 판단
function isExistSize($item_id, $size){

    $pdo = pdoSqlConnect();
    $query = "
select

if(
    (item_size.size1 = ?
    or item_size.size2 = ?
    or item_size.size3 = ?),
    1,
    0
    ) as exist

from Item

inner join item_size
on Item.id = item_size.id

where Item.id = ?;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$size,$size,$size,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//장바구니에 등록된 아이템이 맞는지
function isExistItemOnOrders($user_id,$item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.item_id = ?
                and Orders.status = 000) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//장바구니에 등록된 아이템이 맞는지
function isPurchaseItem($user_id,$item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.item_id = ?
                and Orders.status>100) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//장바구니에 등록된 아이템이 맞는지
function isExistOrder($user_id,$order_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.order_id = ?) as exist";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}


//장바구니에 등록된 아이템이 맞는지
function isExistBasket($user_id,$order_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.id = ?
                and Orders.status<100) as exist";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$order_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//유저가 산 아이템이 맞는지
function isPurchaseUser($user_id,$item_id,$item_color,$item_size){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.item_id = ? 
                and Orders.color = ? and Orders.size = ? and Orders.status>100) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id,$item_color,$item_size]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//이미 리뷰를 작성했는지
function isAlreayComment($user_id,$item_id,$item_color,$item_size){

    $pdo = pdoSqlConnect();
    $query = "
select

exists(select * from Comment

inner join comment_option
on Comment.id = comment_option.id

where Comment.user_id = ? and Comment.item_id = ? and Comment.is_deleted = 'N'
  and comment_option.color = ? and comment_option.size = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id,$item_color,$item_size]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//삭제된 유저인지 판단
function isDeletedUser($email){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where email = ? and is_deleted = 'Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//삭제된 유저인지 판단
function isDeletedTag($user_id, $tag_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Tag where user_id = ? and id=? and is_deleted = 'Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//삭제된 하트인지 판단
function isDeletedHeart($user_id, $item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Heart where user_id = ? and item_id=? and is_deleted = 'Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//삭제된 즐겨찾기인지 판단
function isDeletedFavorite($user_id, $mall_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Favorite where user_id = ? and mall_id=? and is_deleted = 'Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $mall_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//장바구니에서 삭제된 상품이 맞는지
function isDeletedItemOnOrders($user_id, $item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.item_id = ?
                and Orders.status=999) as exist";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

function isValidUser($email, $password){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User WHERE email= ? AND password = ?) AS exist;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email, $password]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);

}

function isSameCategory($category, $category_detail){
    $pdo = pdoSqlConnect();
    $query = "SELECT if(left($category,1)=left($category_detail,1),1,0) AS exist from item_category;";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$category, $category_detail]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);

}
//입력받은 이미지 확장자가 png인가
function isImagePNG($image_url){
    $pdo = pdoSqlConnect();
    $query = "select if(right(?,3) = 'png',1,0) as exist;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$image_url]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//5개의 값이 모두 다른지 확인
function isAllDifferent($id0,$id1,$id2,$id3,$id4){
    $array[] = (Object)Array();
    $array[0] = $id0; $array[1] = $id1; $array[2] = $id2; $array[3] = $id3; $array[4] = $id4;
    for($i=0;$i<4;$i++){
        for($j=$i+1;$j<5;$j++){
            if($array[$i] == $array[$j]){
                return false;
                break;
            }
        }
    }
    return true;
}