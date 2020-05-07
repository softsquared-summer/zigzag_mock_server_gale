<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;

        /*
* API No. 8
* API Name : 아이템 리뷰 총평 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemSummary":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            $res->result = getItemSummary($item_id);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "아이템 리뷰 총평 조회가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

            /*
* API No. 9
* API Name : 아이템 리뷰 리스트 조회 API
* 마지막 수정 날짜 : 19.05.02
*/
        case "getItemComments":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $item_id = $vars["itemID"];

            //입력받은 값 타입 체크
            if(nl2br(gettype($item_id)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 타입입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }else{
                //존재하는 아이템 아이디인지 확인
                if(!isExistItem($item_id)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "존재하지 않는 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($_GET["page"])){
                $page = 1;
            }
            else{
                if(isOverPage("Comment",$_GET["page"])){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "초과된 페이지입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $page = $_GET["page"];
                }
            }


            if(empty($_GET["user_id"])){
                //리뷰 리스트 전체 조회
                $res->result = getItemComments($item_id,$page);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "아이템 리뷰 조회가 완료되었습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            else{
                if($_GET["user_id"] == $user_id){
                    //자신이 쓴 리뷰 리스트 조회
                    $res->result = getItemCommentsMyself($user_id,$item_id,$page);
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "회원님이 쓰신 리뷰 조회가 완료되었습니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 유저 아이디입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

        /*
* API No. 10
* API Name : 아이템 리뷰 작성 API
* 마지막 수정 날짜 : 19.05.03
*/
        case "postComment":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //변수별 post variation
            if(empty($req->item_id)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_id = $req->item_id;
                //변수 타입 체크
                if(nl2br(gettype($item_id)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistItem($item_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이템입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->item_color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 색깔을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_color = $req->item_color;
                //변수 타입 체크
                if(nl2br(gettype($item_color)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
//                    //변수 존재성 체크
//                    if(!isExistColor($item_id,$item_color)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 색깔입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "아이템 사이즈를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $item_size = $req->item_size;
                //변수 타입 체크
                if(nl2br(gettype($item_size)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
//                    if(!isExistSize($item_id,$item_size)){
//                        $res->is_success = FALSE;
//                        $res->code = 201;
//                        $res->message = "존재하지 않는 사이즈입니다.";
//                        echo json_encode($res, JSON_NUMERIC_CHECK);
//                        return;
//                    }
                }
            }

            if(empty($req->score)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "별점을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $score = $req->score;
                //변수 타입 체크
                if(nl2br(gettype($score)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "사이즈가 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $size = $req->size;
                //변수 타입 체크
                if(nl2br(gettype($size)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->color)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "색깔이 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $color = $req->color;
                //변수 타입 체크
                if(nl2br(gettype($color)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->finish)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "마감 상태가 어땠는지 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $finish = $req->finish;
                //변수 타입 체크
                if(nl2br(gettype($finish)) != "integer"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            if(empty($req->comment)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "리뷰 내용을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $comment = $req->comment;
                //변수 타입 체크
                if(nl2br(gettype($comment)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //image_url값이 없으면 "없음"으로 표시
            if(empty($req->image_url)){
                $image_url = " ";
            }
            else{
                $image_url = $req->image_url;
                if(nl2br(gettype($image_url)) != "string"
                or !isImagePNG($image_url)){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.png 확장자로 이미지를 넣어주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //유저가 사지 않은 제품은 못 사게 하기
            if(!isPurchaseUser($user_id, $item_id, $item_color, $item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "구매하지 않은 아이템입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //이미 리뷰를 작성했을 경우 막기, 단 삭제된 경우 새로 진행
            if(isAlreayComment($user_id, $item_id, $item_color, $item_size)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 리뷰를 작성하신 아이템입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = postComment($user_id,$item_id,$score,$item_color,$item_size,$size,$color,$finish,$comment,$image_url );
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "리뷰가 작성되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 11
* API Name : 리뷰 삭제 API
* 마지막 수정 날짜 : 20.05.03
*/
        case "deleteComment":
            http_response_code(200);

            //토큰 가져오기
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            //헤더 유효 검사
            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            //입력받은 이메일 id로 변환
            $email = getDataByJWToken($jwt, JWT_SECRET_KEY)->id;
            $user_id = EmailToID($email);

            //이미 존재하는 회원인지 검토
            if(!isExistEmail($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "존재하지 않는 회원입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //삭제 유저 검사
            if(isDeletedUser($email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 삭제된 유저입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            if(empty($_GET["id"])){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "리뷰 아이디를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $comment_id = $_GET["id"];
                //변수 타입 체크
                if(nl2br(gettype($comment_id)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistComment($user_id,$comment_id)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 리뷰입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            $res->result = deleteComment($user_id, $comment_id);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "리뷰 삭제가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
