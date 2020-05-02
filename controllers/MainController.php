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

            $email = $req->email;
            $password = $req->password;
            $phone = $req->phone;
            $check1 = $req->is_over_14;
            $check2 = $req->is_service_agree;
            $check3 = $req->is_privacy_agree;
            $check4 = $req->is_alarm_agree;

            //값이 null값이 들어왔을 때 대처
            if($email == null
                or $password == null
                or $phone == null){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "빠진 항목을 다시 기입해주세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //입력값 타입 검사
            if(nl2br(gettype($email)) != "string"
                or nl2br(gettype($password)) != "string"
                or nl2br(gettype($phone)) != "string"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "유효한 타입이 아닙니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //동의 안하면 회원가입 못 함
            if($check1 != "Y"
            or $check2 != "Y"
            or $check3 != "Y"){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "필수 항목에 동의해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //잘못된 입력 거르기
            if(!($check4 == "Y" or $check4 == "N")){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "잘못된 입력입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            //이미 존재하는 회원인지 검토
            if(isExistEmail($email)){

                //삭제되었는지 확인 --> 삭제 시 재가입
                if(isDeletedUser($email)){
                    $res->result = recreateUser($email);
                    $res->is_success = TRUE;
                    $res->code = 100;
                    $res->message = "회원가입을 축하드립니다!";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
                else{
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "이미 존재하는 회원입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
            }

            //중복되는 핸드폰 번호인지 확인
            if(isExistPhone($phone)){
                $res->is_success = FALSE;
                $res->code = 201;
                $res->message = "이미 등록된 핸드폰 번호입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;

                //핸드폰 번호 길이 다를 경우 로그인 못 함
                if(strlen($phone) != 11){
                    $res->is_success = FALSE;
                    $res->code = 201;
                    $res->message = "잘못된 형식의 핸드폰 번호입니다.";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    return;
                }
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

            $email = $req->email;
            $password = $req->password;

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

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
