<?php

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

//삭제 물품 재등록
function recreateOrder($user_id, $item_id, $size,$color,$num)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Orders SET status=-999 
                where user_id = ? and item_id = ? and size = ? and color = ? and num = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;

}

//장바구니 삭제 물품 재등록
function recreateBasket($user_id, $item_id, $size,$color,$num)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Orders SET status=000 
                where user_id = ? and item_id = ? and size = ? and color = ? and num = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;

}

//하트 삭제 물품 재등록
function recreateHeart($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Heart SET is_deleted = 'N' where user_id = ? and item_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id]);

    $st = null;
    $pdo = null;

}

//즐겨찾기 삭제 물품 재등록
function recreateFavorite($user_id, $mall_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Favorite SET is_deleted = 'N' where user_id = ? and mall_id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$mall_id]);

    $st = null;
    $pdo = null;

}

//이메일을 id값으로 변환
function EmailToID($email){

    $pdo = pdoSqlConnect();

    $query = "select User.id as exist from User where email = ?";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);
}

//Completion 테이블에 추가_Bank
//function postCompletionBank($user_id,$item1,$item2,$item3,$item4,$item5)
//{
//    $pdo = pdoSqlConnect();
//
//    $query = "INSERT INTO Completion
//(user_id, order_id1, order_id2, order_id3, order_id4, order_id5, status)
// VALUES (?,?,?,?,?,?,'002');";
//
//    if($item2 == -2){
//        $item2 = 0;
//    }
//    else if($item3 == -3){
//        $item3 = 0;
//    }
//    else if($item4 == -4){
//        $item4 = 0;
//    }
//    else if($item5 == -5){
//        $item5 = 0;
//    }
//
//    $st = $pdo->prepare($query);
//    $st->execute([$user_id,$item1,$item2,$item3,$item4,$item5]);
//    $st = null;
//    $pdo = null;
//}
//
////Completion 테이블에 추가_NoneBank
//function postCompletionNoneBank($user_id,$item1,$item2,$item3,$item4,$item5)
//{
//    $pdo = pdoSqlConnect();
//
//    $query = "INSERT INTO Completion
//(user_id, order_id1, order_id2, order_id3, order_id4, order_id5, status)
// VALUES (?,?,?,?,?,?,'001');";
//
//    if($item2 == -2){
//        $item2 = 0;
//        $item3 = 0;
//        $item4 = 0;
//        $item5 = 0;
//    }
//    else if($item3 == -3){
//        $item3 = 0;
//        $item4 = 0;
//        $item5 = 0;
//    }
//    else if($item4 == -4){
//        $item4 = 0;
//        $item5 = 0;
//    }
//    else if($item5 == -5){
//        $item5 = 0;
//    }
//
//    $st = $pdo->prepare($query);
//    $st->execute([$user_id,$item1,$item2,$item3,$item4,$item5]);
//    $st = null;
//    $pdo = null;
//}

////completion_id 추가
//function postCompletionID($user_id,$item1,$item2,$item3,$item4,$item5){
//    $pdo = pdoSqlConnect();
//    $query_extra = "select Completion.id from Completion order by create_at desc";
//    $st = $pdo->prepare($query_extra);
//    $st->execute([]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//    $Completion_id = $res[0]['id'];
//    $st = null;
//
//    $query1 = "UPDATE Orders SET completion_id = ".$Completion_id." where user_id = ".$user_id." and id = ".$item1.";";
//    $query2 = "UPDATE Orders SET completion_id = ".$Completion_id." where user_id = ".$user_id." and id = ".$item2.";";
//    $query3 = "UPDATE Orders SET completion_id = ".$Completion_id." where user_id = ".$user_id." and id = ".$item3.";";
//    $query4 = "UPDATE Orders SET completion_id = ".$Completion_id." where user_id = ".$user_id." and id = ".$item4.";";
//    $query5 = "UPDATE Orders SET completion_id = ".$Completion_id." where user_id = ".$user_id." and id = ".$item5.";";
//
//    if($item5 != -5){
//        $st = $pdo->prepare($query1.' '.$query2.' '.$query3.' '.$query4.' '.$query5);
//        $st->execute([]);
//        $st = null;
//    }
//    else if($item4 != -4){
//        $st = $pdo->prepare($query1.' '.$query2.' '.$query3.' '.$query4);
//        $st->execute([]);
//        $st = null;
//    }
//    else if($item3 != -3){
//        $st = $pdo->prepare($query1.' '.$query2.' '.$query3);
//        $st->execute([]);
//        $st = null;
//    }
//    else if($item2 != -2){
//        $st = $pdo->prepare($query1.' '.$query2);
//        $st->execute([]);
//        $st = null;
//    }
//    else{
//        $st = $pdo->prepare($query1);
//        $st->execute([]);
//        $st = null;
//    }
//
//    $pdo = null;
//
//}

//카테고리 코드 텍스트 변환
function categoryCodeToText($code){
    switch ($code){
        case 100:
            return "아우터";
            break;
        case 200:
            return "상의";
            break;
        case 300:
            return "원피스/세트";
            break;
        case 400:
            return "바지";
            break;
        case 500:
            return "스커트";
            break;
        default:
            return "잘못된 입력";
            break;
    }
}

//세부 카테고리 코드 텍스트로 변환
function categoryDetailCodeToText($text){
    switch ($text){
        case 101:
            return "가디건";
            break;
        case 102:
            return "자켓";
            break;
        case 103:
            return "코트";
            break;
        case 104:
            return "점퍼";
            break;
        case 201:
            return "티셔츠";
            break;
        case 202:
            return "블라우스";
            break;
        case 203:
            return "셔츠/남방";
            break;
        case 204:
            return "니트/스웨터";
            break;
        case 301:
            return "미니원피스";
            break;
        case 302:
            return "미디원피스";
            break;
        case 303:
            return "롱원피스";
            break;
        case 304:
            return "투피스/세트";
            break;
        case 401:
            return "일자바지";
            break;
        case 402:
            return "슬랙스팬츠";
            break;
        case 403:
            return "반바지";
            break;
        case 404:
            return "와이드팬츠";
            break;
        case 501:
            return "미니스커트";
            break;
        case 502:
            return "미디스커트";
            break;
        case 503:
            return "롱스커트";
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
        case "스커트":
            return 500;
            break;
        default:
            return 0;
            break;
    }
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
        case "점퍼":
            return 104;
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
        case "니트/스웨터":
            return 204;
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
        case "투피스/세트":
            return 304;
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
        case "와이드팬츠":
            return 404;
            break;
        case "미니스커트":
            return 501;
            break;
        case "미디스커트":
            return 502;
            break;
        case "롱스커트":
            return 503;
            break;
    }
}

//status 코드 텍스트 변환
function statusCodeToText($code){
    switch ($code){
        case 101:
            return "입금대기";
            break;
        case 102:
            return "주문대기";
            break;
        case 103:
            return "주문승인";
            break;
        case 104:
            return "상품집계";
            break;
        case 105:
            return "출고준비";
            break;
        case 106:
            return "배송중";
            break;
        case 107:
            return "배송완료";
            break;
        case 201:
            return "주문취소";
            break;
        case 301:
            return "반품신청대기";
            break;
        case 302:
            return "반품진행중";
            break;
        case 303:
            return "반품완료";
            break;
        default:
            return "잘못된 코드";
            break;
    }
}

//status 코드 텍스트 변환
function statusToComment($code){
    switch ($code){
        case 101:
            return "예브게 포장해서 보내드릴게요! 아래 계좌로 입금해주세요:)";
            break;
        case 102:
            return "상품이 주문 대기중입니다.";
            break;
        case 103:
            return "상품이 주문승인되었습니다.";
            break;
        case 104:
            return "상품이 집계 중입니다.";
            break;
        case 105:
            return "상품이 출고 준비 중입니다.";
            break;
        case 106:
            return "상품이 배송중입니다.";
            break;
        case 107:
            return "상품 배송이 완료되었습니다.";
            break;
        case 201 or "주문취소":
            return "주문이 취소되었습니다.";
            break;
        case 301 or "반품신청대기":
            return "반품신청대기중입니다.";
            break;
        case 302 or "반품진행중":
            return "반품이 진행중입니다.";
            break;
        case 303 or "반품완료":
            return "반품이 완료되었습니다.";
            break;
        default:
            return "잘못된 코드";
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
//function test()
//{
//    $pdo = pdoSqlConnect();
//    $query_extra = "select Completion.id from Completion order by create_at desc";
//    $st = $pdo->prepare($query_extra);
//    $st->execute([]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//    $Completion_id = $res[0]['id'];
//    $st = null;
//
//    return $Completion_id;
//}

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
