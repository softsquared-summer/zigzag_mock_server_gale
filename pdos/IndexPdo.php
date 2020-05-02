<?php
//main controller

//회원가입 - 유저 추가 함수
function createUser($email, $password, $phone){

    $pdo = pdoSqlConnect();

    $query = "INSERT INTO User (email, password, phone) VALUES (?,?,?);";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email, $password, $phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return $res;
}

//회원 탈퇴
function deleteUser($email){

    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET is_deleted = 'Y' where email = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return $res;
}

//index controller

//아이템 조회(쿼리스트링 없음)
function getItems($user_id, $keyword_input, $category_input, $ship_input, $filter_input){

    $pdo = pdoSqlConnect();

    $query_body = "
select

Item.id as id,

(
    case
        when  item_category.category_code = 100 then \"아우터\"
        when  item_category.category_code = 200 then \"상의\"
        when  item_category.category_code = 300 then \"원피스/세트\"
        when  item_category.category_code = 400 then \"바지\"
    end
)
as item_category,

(
    case
        when  item_category.category_detail_code = 101 then \"가디건\"
        when  item_category.category_detail_code = 102 then \"자켓\"
        when  item_category.category_detail_code = 103 then \"코트\"
        when  item_category.category_detail_code = 201 then \"티셔츠\"
        when  item_category.category_detail_code = 202 then \"블라우스\"
        when  item_category.category_detail_code = 203 then \"셔츠/남방\"
        when  item_category.category_detail_code = 301 then \"미니원피스\"
        when  item_category.category_detail_code = 302 then \"미디원피스\"
        when  item_category.category_detail_code = 303 then \"롱원피스\"
        when  item_category.category_detail_code = 401 then \"일자바지\"
        when  item_category.category_detail_code = 402 then \"슬랙스팬츠\"
        when  item_category.category_detail_code = 403 then \"반바지\"
    end
)
as item_category_detail,

if(
    Item.Shipment = 0,
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

concat(substr(Item.price,1,2),',',substr(Item.price,-3)) as price

from Item

inner join Mall
on Item.mall_id = Mall.id

inner join item_category
on item_category.item_id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id";

    $query = $query_body." ".$keyword_input.$category_input.$ship_input." ".$filter_input;
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return $res;
}

//아이템 상세 조회
function getItemDetail($user_id, $item_id){

    $pdo = pdoSqlConnect();

    $query_body = "select

Item.id as id,

Mall.name as mall_name,

' ' as image_url,

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

concat(substr(Item.price,1,2),',',substr(Item.price,-3)) as price

from Item

inner join Mall
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

    $query_image = "select image_url from item_image

inner join Item on Item.id = item_image.item_id

where Item.id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    $st2->execute([$item_id]);
    $st2->setFetchMode(PDO::FETCH_ASSOC);
    $res_image = $st2->fetchAll();

    $res_body[0]["image_url"] = $res_image;
    $res = $res_body;


    $st=null;
    $pdo = null;

    return $res;
}

//아이템 색깔 리스트 조회 API
function getItemColors($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT * FROM Test WHERE no = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//-----------------------조건식--------------------------//

//기존에 있는 이메일인지 판단
function isExistEmail($email){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM User where email = ?) as exist";


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
    $query = "SELECT EXISTS(SELECT * FROM User where phone = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$phone]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 번호인지 판단
function isExistItem($item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Item where id = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$item_id]);
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

// ------------------------보조 함수------------------------------
//삭제 유저 재가입 함수
function recreateUser($email){

    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET is_deleted = 'N' where email = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}

//이메일을 id값으로 변환
function EmailToID($email){

    $pdo = pdoSqlConnect();

    $query = "select id from User where email = ?";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}

//세부 카테고리 텍스트 코드로 변환
function categoryDetailTextToCode($text){
    switch ($text){
        case "가디건":
            return 101;
            break;
        case "자켓":
            return 102;
            break;
        case "코트":
            return 103;
            break;
        case "티셔츠":
            return 201;
            break;
        case "블라우스":
            return 202;
            break;
        case "셔츠/남방":
            return 203;
            break;
        case "미니원피스":
            return 301;
            break;
        case "미디원피스":
            return 302;
            break;
        case "롱원피스":
            return 303;
            break;
        case "일자바지":
            return 401;
            break;
        case "슬랙스팬츠":
            return 402;
            break;
        case "반바지":
            return 403;
            break;
    }
}

//카테고리 텍스트 코드로 변환
function categoryTextToCode($text){
    switch ($text){
        case "아우터":
            return 100;
            break;
        case "상의":
            return 200;
            break;
        case "원피스/세트":
            return 300;
            break;
        case "바지":
            return 400;
            break;
        default:
            return 0;
            break;
    }
}


// CREATE
//    function addMaintenance($message){
//        $pdo = pdoSqlConnect();
//        $query = "INSERT INTO MAINTENANCE (MESSAGE) VALUES (?);";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message]);
//
//        $st = null;
//        $pdo = null;
//
//    }


// UPDATE
//    function updateMaintenanceStatus($message, $status, $no){
//        $pdo = pdoSqlConnect();
//        $query = "UPDATE MAINTENANCE
//                        SET MESSAGE = ?,
//                            STATUS  = ?
//                        WHERE NO = ?";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message, $status, $no]);
//        $st = null;
//        $pdo = null;
//    }

// RETURN BOOLEAN
//    function isRedundantEmail($email){
//        $pdo = pdoSqlConnect();
//        $query = "SELECT EXISTS(SELECT * FROM USER_TB WHERE EMAIL= ?) AS exist;";
//
//
//        $st = $pdo->prepare($query);
//        //    $st->execute([$param,$param]);
//        $st->execute([$email]);
//        $st->setFetchMode(PDO::FETCH_ASSOC);
//        $res = $st->fetchAll();
//
//        $st=null;$pdo = null;
//
//        return intval($res[0]["exist"]);
//
//    }

//READ
function test($text)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT * FROM Item where Item.id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute($text);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//READ
function testDetail($testNo)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT * FROM Test WHERE no = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$testNo]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}


function testPost($name)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Test (name) VALUES (?);";

    $st = $pdo->prepare($query);
    $st->execute([$name]);

    $st = null;
    $pdo = null;

}
