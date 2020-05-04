<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));

$who = 'cmg4739@gmail.com';
$title = 'error_zigzag';
$option_value = 'From: justin4739@naver.com\r\n';

try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        /*
         * API No. 0
         * API Name : JWT 유효성 검사 테스트 API
         * 마지막 수정 날짜 : 19.04.25
         */
        case "validateJwt":
            // jwt 유효성 검사

            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->isSuccess = FALSE;
                $res->code = 201;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }

            http_response_code(200);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";

            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
     * API No. 1
     * API Name : 회원가입 API(재가입 가능)
     * 마지막 수정 날짜 : 20.04.30
     */
        case "createUser":
            http_response_code(200);

            //변수별 post variation
            if(empty($req->email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이메일을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $email = $req->email;
                //변수 타입 체크
                if(nl2br(gettype($email)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(isExistEmail($email)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "이미 존재하는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->password)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "비밀번호를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $password = $req->password;
                //변수 타입 체크
                if(nl2br(gettype($password)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            if(empty($req->phone)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "핸드폰 번호를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $phone = $req->phone;
                //변수 타입 체크
                if(nl2br(gettype($phone)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(isExistPhone($phone)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "이미 존재하는 핸드폰 번호입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->is_over_14)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "만 14세 이상입니다 항목에 체크해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $check1 = $req->is_over_14;
                //변수 타입 체크
                if($check1 != "Y"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "만 14세 이상입니다 항목에 체크해주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            if(empty($req->is_service_agree)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "서비스 이용약관 동의 항목에 체크해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $check2 = $req->is_service_agree;
                //변수 타입 체크
                if($check2 != "Y"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "서비스 이용약관 동의 항목에 체크해주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            if(empty($req->is_privacy_agree)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "개인정보 수집이용 동의 항목에 체크해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $check3 = $req->is_privacy_agree;
                //변수 타입 체크
                if($check3 != "Y"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "개인정보 수집이용 동의 항목에 체크해주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            if(empty($req->is_alarm_agree) or $req->is_alarm_agree == "N"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "개인정보 수집이용 동의 항목에 체크해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $check4 = $req->is_alarm_agree;
                //변수 타입 체크
                if($check3 != "Y"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "개인정보 수집이용 동의 항목에 체크해주세요.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            //삭제된 회원 재가입
            if(isDeletedUser($email)){
                $res->result = recreateUser($email);
                $res->is_success = TRUE;
                $res->code = 100;
                $res->message = "회원가입을 축하드립니다!";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //핸드폰 번호 길이 다를 경우 로그인 못 함
            if(strlen($phone) != 11){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 형식의 핸드폰 번호입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            $res->result = createUser($email, $password, $phone);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "회원가입이 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 2
         * API Name : JWT 생성 테스트 API (로그인)
         * 마지막 수정 날짜 : 19.04.30
         */
        case "createJwt":
            // jwt 유효성 검사
            http_response_code(200);

            if(empty($req->email)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이메일을 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $email = $req->email;
                //변수 타입 체크
                if(nl2br(gettype($email)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    //변수 존재성 체크
                    if(!isExistEmail($email)){
                        $res->is_success = FALSE;
                        $res->code = 201;
                        $res->message = "존재하지 않는 아이디입니다.";
                        echo json_encode($res, JSON_NUMERIC_CHECK);
                        return;
                    }
                }
            }

            if(empty($req->password)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "비밀번호를 입력해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            else{
                $password = $req->password;
                //변수 타입 체크
                if(nl2br(gettype($password)) != "string"){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 타입입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                //변수 존재성 체크 : 필요 없음
            }

            if(!isValidUser($email, $password)){
                $res->is_success = FALSE;
                $res->code = 100;
                $res->message = "유효하지 않은 아이디 입니다";
                mail($who,$title,$res->message,$option_value);
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //페이로드에 맞게 다시 설정 요함
            $jwt = getJWToken($email, $password, JWT_SECRET_KEY);
            $res->result = $jwt;
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            mail($who,$title,$res->message,$option_value);
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
     * API No. 3
     * API Name : 회원탈퇴 API(유저 삭제)
     * 마지막 수정 날짜 : 20.05.2
     */
        case "deleteUser":
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
            //$user_id = EmailToID($email);

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


            $res->result = deleteUser($email);
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "회원탈퇴가 완료되었습니다.";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
* API No. 4
* API Name : Order 리셋 API
* 마지막 수정 날짜 : 20.05.3
*/
        case "reset":
            http_response_code(200);
            $res->result = order_reset();
            $res->is_success = TRUE;
            $res->code = 100;
            $res->message = "Order 리셋 완료";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
