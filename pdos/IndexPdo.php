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

    $st=null;
    $pdo = null;

    return;
}

//회원 탈퇴
function deleteUser($email){

    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET is_deleted = 'Y' where email = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$email]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}

//index controller

//아이템 조회(쿼리스트링 없음)
function getItems($user_id, $keyword_input, $category_input, $ship_input, $filter_input){

    $pdo = pdoSqlConnect();

    $query_body = "
select

Item.id as item_id,

(
    case
        when  item_category.category_code = 100 then '아우터'
        when  item_category.category_code = 200 then '상의'
        when  item_category.category_code = 300 then '원피스/세트'
        when  item_category.category_code = 400 then '바지'
    end
)
as item_category,

(
    case
        when  item_category.category_detail_code = 101 then '가디건'
        when  item_category.category_detail_code = 102 then '자켓'
        when  item_category.category_detail_code = 103 then '코트'
        when  item_category.category_detail_code = 201 then '티셔츠'
        when  item_category.category_detail_code = 202 then '블라우스'
        when  item_category.category_detail_code = 203 then '셔츠/남방'
        when  item_category.category_detail_code = 301 then '미니원피스'
        when  item_category.category_detail_code = 302 then '미디원피스'
        when  item_category.category_detail_code = 303 then '롱원피스'
        when  item_category.category_detail_code = 401 then '일자바지'
        when  item_category.category_detail_code = 402 then '슬랙스팬츠'
        when  item_category.category_detail_code = 403 then '반바지'
    end
)
as item_category_detail,

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

concat(substr(Item.price,1,2),',',substr(Item.price,-3)) as price

from Item

inner join Mall
on Item.mall_id = Mall.id

inner join item_category
on item_category.id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id";

    $query = $query_body." where ".$keyword_input." and ".$category_input." and ".$ship_input." ".$filter_input;
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $item_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $item_id[$i] = $res_body[$i]['item_id'];
    }

    $query_image = "
select
group_concat(case when item_image.id%2=1 then image_url end) image_url1,
group_concat(case when item_image.id%2=0 then image_url end) image_url2
from Item

inner join item_image
on Item.id = item_image.item_id

where Item.id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$item_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["image"] = $res_image[0];
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

//아이템 리뷰 총평 조회 API
function getItemSummary($item_id)
{
    $pdo = pdoSqlConnect();
    $query = "select

Item.id as item_id,
avg(Comment.score) as score,

if(
    count(if(comment_summary.size=1,1,null)) / count(comment_summary.size) * 100 >= 50,
    '딱 맞아요',
    if(
        count(if(comment_summary.size=2,1,null)) / count(comment_summary.size) * 100 >= 50,
        '작게 나왔어요',
        '크게 나왔어요'
        )
    ) as size,

if(
    count(if(comment_summary.size=1,1,null)) / count(comment_summary.size) * 100 >= 50,
    concat(round(count(if(comment_summary.size=1,1,null)) / count(comment_summary.size) * 100),'%'),
    if(
        count(if(comment_summary.size=2,1,null)) / count(comment_summary.size) * 100 >= 50,
        concat(round(count(if(comment_summary.size=2,1,null)) / count(comment_summary.size) * 100),'%'),
        concat(round(count(if(comment_summary.size=3,1,null)) / count(comment_summary.size) * 100),'%')
        )
    ) as size_rate,

if(
    count(if(comment_summary.color=1,1,null)) / count(comment_summary.color) * 100 >= 50,
    '화면과 같아요',
    if(
        count(if(comment_summary.color=2,1,null)) / count(comment_summary.color) * 100 >= 50,
        '밝아요',
        '어두워요'
        )
    ) as color,

if(
    count(if(comment_summary.color=1,1,null)) / count(comment_summary.color) * 100 >= 50,
    concat(round(count(if(comment_summary.color=1,1,null)) / count(comment_summary.color) * 100),'%'),
    if(
        count(if(comment_summary.color=2,1,null)) / count(comment_summary.color) * 100 >= 50,
        concat(round(count(if(comment_summary.color=2,1,null)) / count(comment_summary.color) * 100),'%'),
        concat(round(count(if(comment_summary.color=3,1,null)) / count(comment_summary.color) * 100),'%')
        )
    ) as color_rate,

if(
    count(if(comment_summary.finish=1,1,null)) / count(comment_summary.finish) * 100 >= 50,
    '아주 좋아요',
    if(
        count(if(comment_summary.finish=2,1,null)) / count(comment_summary.finish) * 100 >= 50,
        '괜찮아요',
        '별로에요'
        )
    ) as finish,

if(
    count(if(comment_summary.finish=1,1,null)) / count(comment_summary.finish) * 100 >= 50,
    concat(round(count(if(comment_summary.finish=1,1,null)) / count(comment_summary.finish) * 100),'%'),
    if(
        count(if(comment_summary.finish=2,1,null)) / count(comment_summary.finish) * 100 >= 50,
        concat(round(count(if(comment_summary.finish=2,1,null)) / count(comment_summary.finish) * 100),'%'),
        concat(round(count(if(comment_summary.finish=3,1,null)) / count(comment_summary.finish) * 100),'%')
        )
    ) as finish_rate

from Item

inner join Comment
on Comment.item_id = Item.id

inner join comment_summary
on comment_summary.id = Comment.id

where Item.id = ?

order by Comment.id desc;";

    $st = $pdo->prepare($query);
    $st->execute([$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

//아이템 리뷰 리스트 조회 API
function getItemComments($item_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Item.id as item_id,
Comment.id as comment_id,
concat(left(User.email,2),'**') as email,
Comment.score as score,

concat(
    if(year(Comment.create_at)<10,
        concat(0,year(Comment.create_at)),
        year(Comment.create_at)
        ),
    '-',
    if(month(Comment.create_at)<10,
        concat(0,month(Comment.create_at)),
        month(Comment.create_at)
        ),
    '-',
    if(day(Comment.create_at)<10,
        concat(0,day(Comment.create_at)),
        day(Comment.create_at)
        )
    )  as date,

comment_option.color as color,
comment_option.size as size,

case
    when comment_summary.size = 1
    then '딱 맞아요'
    when comment_summary.size = 2
    then '작게 나왔어요'
    when comment_summary.size = 3
    then '크게 나왔어요'
    else '잘못된 입력'
    end as size_summary,

case
    when comment_summary.color = 1
    then '화면과 같아요'
    when comment_summary.color = 2
    then '밝아요'
    when comment_summary.color = 3
    then '어두워요'
    else '잘못된 입력'
    end as color_summary,

case
    when comment_summary.finish = 1
    then '아주 좋아요'
    when comment_summary.finish = 2
    then '괜찮아요'
    when comment_summary.finish = 3
    then '별로에요'
    else '잘못된 입력'
    end as finish_summary,

Comment.text as comment,
ifnull(comment_image.image_url,'없음') as image_url


from Item

inner join Comment
on Comment.item_id = Item.id

inner join User
on Comment.user_id = User.id

left join comment_image
on comment_image.id = Comment.id

inner join comment_summary
on comment_summary.id = Comment.id

inner join comment_option
on comment_option.id = Comment.id

where Item.id = ?

order by Comment.id desc;
";

    $st = $pdo->prepare($query);
    $st->execute([$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//자신이 구매한 아이템 리뷰 리스트 조회 API
function getItemCommentsMyself($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Item.id as item_id,
Comment.id as comment_id,
concat(left(User.email,2),'**') as email,
Comment.score as score,

concat(
    if(year(Comment.create_at)<10,
        concat(0,year(Comment.create_at)),
        year(Comment.create_at)
        ),
    '-',
    if(month(Comment.create_at)<10,
        concat(0,month(Comment.create_at)),
        month(Comment.create_at)
        ),
    '-',
    if(day(Comment.create_at)<10,
        concat(0,day(Comment.create_at)),
        day(Comment.create_at)
        )
    )  as date,

comment_option.color as color,
comment_option.size as size,

case
    when comment_summary.size = 1
    then '딱 맞아요'
    when comment_summary.size = 2
    then '작게 나왔어요'
    when comment_summary.size = 3
    then '크게 나왔어요'
    else '잘못된 입력'
    end as size_summary,

case
    when comment_summary.color = 1
    then '화면과 같아요'
    when comment_summary.color = 2
    then '밝아요'
    when comment_summary.color = 3
    then '어두워요'
    else '잘못된 입력'
    end as color_summary,

case
    when comment_summary.finish = 1
    then '아주 좋아요'
    when comment_summary.finish = 2
    then '괜찮아요'
    when comment_summary.finish = 3
    then '별로에요'
    else '잘못된 입력'
    end as finish_summary,

Comment.text as comment,
ifnull(comment_image.image_url,'없음') as image_url


from Item

inner join Comment
on Comment.item_id = Item.id

inner join User
on Comment.user_id = User.id

left join comment_image
on comment_image.id = Comment.id

inner join comment_summary
on comment_summary.id = Comment.id

inner join comment_option
on comment_option.id = Comment.id

where Comment.user_id = ? and Item.id = ?

order by Comment.id desc;
";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//리뷰 추가
function postComment($user_id,$item_id,$score,$item_color,$item_size,$size,$color,$finish,$comment,$image_url)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Comment (user_id, item_id, score, text) VALUES (?,?,?,?);
              INSERT INTO comment_option (color, size) VALUES (?,?);
              INSERT INTO comment_summary (size, color,finish) VALUES (?,?,?);
              INSERT INTO comment_image (image_url) VALUES (?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$score,$comment,$item_color,$item_size,$size,$color,$finish,$image_url]);

    $st = null;
    $pdo = null;
}

//리뷰 삭제
function deleteComment($user_id, $comment_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Comment SET is_deleted = 'Y' where user_id = ? and id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $comment_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

//쇼핑몰 리스트 조회 API
function getMalls($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
(
    SELECT COUNT(*) + 1 
    FROM 
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
' ' as tags

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall
    
    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

inner join Mall
on Mall.id = b.mall_id

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

order by mall_rank asc;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $mall_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $mall_id[$i] = $res_body[$i]['mall_id'];
    }

    $query_image = "
select
id as tag_id,
name as tag_name
from Tag

where Tag.mall_id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$mall_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["tags"] = $res_image;
    }

    $res = $res_body;

    $pdo = null;

    return $res;
}

function getMallsWithTag($user_id,$tag_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
(
    SELECT COUNT(*) + 1 
    FROM 
    (
        select
        count(if(Favorite.mall_id = Mall.id,1,null)) as num,
        ifnull(Mall.id,0) as mall_id
        from Mall
        left join Favorite
        on Mall.id = Favorite.mall_id
        group by Mall.id
    )
    mall_rank WHERE num > b.num
) AS mall_rank,
' ' as tags

from (
    select
    count(if(Favorite.mall_id = Mall.id,1,null)) as num,
    ifnull(Mall.id,0) as mall_id
    from Mall
    
    left join Favorite
    on Mall.id = Favorite.mall_id
    group by Mall.id
    ) b

inner join Mall
on Mall.id = b.mall_id

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

order by mall_rank asc;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $mall_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $mall_id[$i] = $res_body[$i]['mall_id'];
    }

    $query_image = "
select
id as tag_id,
name as tag_name
from Tag

where Tag.mall_id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$mall_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["tags"] = $res_image;
    }

    $res = $res_body;

    $pdo = null;

    return $res[0];
}

//쇼핑몰 상세 조회 API
function getMallDetail($user_id,$mallID)
{
    $pdo = pdoSqlConnect();
    $query = "
select

Mall.id as mall_id,
mall_image.image_url as image_url,
if(
    Favorite.user_id = ?,
    'Y',
    'N'
    )
as is_favorite,
Mall.name as mall_name,
' ' as tags

from Mall

inner join mall_image
on Mall.id = mall_image.id

left join Favorite
on Favorite.mall_id = Mall.id

where Mall.id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$mallID]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st->fetchAll();

    $st=null;

    $mall_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $mall_id[$i] = $res_body[$i]['mall_id'];
    }

    $query_image = "
select
id as tag_id,
name as tag_name
from Tag

where Tag.mall_id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$mall_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["tags"] = $res_image;
    }

    $res = $res_body;

    $pdo = null;

    return $res[0];
}

//태그 추가
function postTag($user_id,$mall_id,$tag_name)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Tag (user_id,mall_id,name) VALUES (?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$mall_id,$tag_name]);

    $st = null;
    $pdo = null;
}

//최근 사용한 태그 조회
function getTagRecent($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT id as tag_id, name as tag_name FROM Tag where user_id = ? order by create_at desc;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//태그 조회
function getTag($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT
    id as tag_id,
    name as tag_name,
    count(id) as tag_num

FROM Tag

where user_id = ? and is_deleted = 'N'
GROUP BY id
order by create_at;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//태그 삭제
function deleteTag($user_id, $tag_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Tag SET is_deleted = 'Y' where user_id = ? and id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $tag_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

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
function getHearts($user_id)
{
    $pdo = pdoSqlConnect();
    $query_num = "select concat('찜한 상품 ',count(id)) as num, ' ' as list from Heart where user_id = ?;";

    $st_num = $pdo->prepare($query_num);
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

concat(substr(Item.price,1,2),',',substr(Item.price,-3)) as price

from Item

inner join Mall
on Item.mall_id = Mall.id

inner join item_category
on item_category.id = Item.id

left join Heart
on Heart.item_id = Item.id

left join User
on User.id = Heart.user_id

where Heart.user_id = ?;
";

    $st_body = $pdo->prepare($query_body);
    //    $st->execute([$param,$param]);
    $st_body->execute([$user_id]);
    $st_body->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st_body->fetchAll();

    $item_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $item_id[$i] = $res_body[$i]['item_id'];
    }

    $query_image = "select
group_concat(case when item_image.id%2=1 then image_url end) image_url1,
group_concat(case when item_image.id%2=0 then image_url end) image_url2
from Item

inner join item_image
on Item.id = item_image.item_id

where Item.id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$item_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["image"] = $res_image[0];
    }

    $res_num[0]["list"] = $res_body[0];
    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res[0];
}

//즐겨찾기 추가
function postFavorite($user_id, $mall_id)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Favorite (user_id,mall_id) VALUES (?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$user_id, $mall_id]);

    $st = null;
    $pdo = null;
}

//즐겨찾기 삭제
function deleteFavorite($user_id, $mall_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Favorite SET is_deleted = 'Y' where user_id = ? and mall_id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $mall_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

//장바구니 추가
function postBasket($user_id, $item_id, $size,$color,$um)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Orders (user_id,item_id,size,color,num,is_basket ) VALUES (?,?,?,?,?,'Y');";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;
}

//직접구매
function postOrder($user_id, $item_id, $size,$color,$num)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO Orders (user_id,item_id,size,color,num,is_basket ) VALUES (?,?,?,?,?,'N');";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$item_id,$size,$color,$num]);

    $st = null;
    $pdo = null;
}

//장바구니 아이템 리스트 조회
function getBaskets($user_id)
{
    $pdo = pdoSqlConnect();
    $query_num = "select count(id), ' ' as list from Orders
                where Orders.user_id = ? and Orders.is_deleted = 'N' and Orders.is_purchased = 'N' and Orders.is_basket='Y';";

    $st_num = $pdo->prepare($query_num);
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

concat(substr(Item.price,1,2),',',substr(Item.price,-3)) as price,

Mall.shipment as ship

from Orders

inner join Item
on Orders.item_id = Item.id

inner join Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.is_deleted = 'N' and Orders.is_purchased = 'N' and Orders.is_basket='Y';
";

    $st_body = $pdo->prepare($query_body);
    //    $st->execute([$param,$param]);
    $st_body->execute([$user_id]);
    $st_body->setFetchMode(PDO::FETCH_ASSOC);
    $res_body = $st_body->fetchAll();

    $item_id[] = (Object)Array();

    for($i = 0; $i<count($res_body); $i++){
        $item_id[$i] = $res_body[$i]['item_id'];
    }

    $query_image = "select
group_concat(case when item_image.id%2=1 then image_url end) image_url1,
group_concat(case when item_image.id%2=0 then image_url end) image_url2
from Item

inner join item_image
on Item.id = item_image.item_id

where Item.id = ?;";


    $st2 = $pdo->prepare($query_image);
    //    $st->execute([$param,$param]);

    for($i = 0; $i<count($res_body); $i++){
        $st2->execute([$item_id[$i]]);
        $st2->setFetchMode(PDO::FETCH_ASSOC);
        $res_image = $st2->fetchAll();
        $res_body[$i]["image"] = $res_image[0];
    }

    $res_num[0]["list"] = $res_body[0];
    $res = $res_num;

    $st = null;
    $pdo = null;

    return $res[0];
}

//장바구니 삭제
function deleteBaskets($user_id, $item_id)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE Orders SET is_deleted = 'Y', is_basket = 'N' where user_id = ? and item_id = ?;";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id, $item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st = null;
    $pdo = null;

    return;
}

//직접구매 총액 계산
function totalPay($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "
select
ifnull(
    sum(Item.price * Orders.num),
    0) as price,

ifnull(
    round(sum(
        if(
            Mall.shipment = 0,
            Mall.shipment,
            Mall.shipment / Mall.mall_count
            )
        )),
    0) as ship,
    
ifnull(
    sum(Item.price * Orders.num) + 
    round(sum(
        if(
            Mall.shipment = 0,
            Mall.shipment,
            Mall.shipment / Mall.mall_count
            )
        )),
    0) as total

from Orders

inner join Item
on Item.id = Orders.item_id

inner join (
    SELECT
        count(
            Mall.id
            ) as mall_count,
            Mall.id,
            Mall.shipment
        from Orders

        inner join Item
        on Item.id = Orders.item_id

        inner join Mall
        on Item.mall_id = Mall.id

        where Orders.user_id = ? and Orders.is_basket='N'
        and Orders.is_purchased='N' and Orders.is_deleted='N'

        GROUP BY Mall.id
            ) Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.is_basket='Y'
  and Orders.is_purchased='N' and Orders.is_deleted='N';";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$user_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

//장바구니 총액 계산
function totalPayBasket($user_id)
{
    $pdo = pdoSqlConnect();
    $query = "select
ifnull(
    sum(Item.price * Orders.num),
    0) as price,

ifnull(
    round(sum(
        if(
            Mall.shipment = 0,
            Mall.shipment,
            Mall.shipment / Mall.mall_count
            )
        )),
    0) as ship,
    
ifnull(
    sum(Item.price * Orders.num) + 
    round(sum(
        if(
            Mall.shipment = 0,
            Mall.shipment,
            Mall.shipment / Mall.mall_count
            )
        )),
    0) as total

from Orders

inner join Item
on Item.id = Orders.item_id

inner join (
    SELECT
        count(
            Mall.id
            ) as mall_count,
            Mall.id,
            Mall.shipment
        from Orders

        inner join Item
        on Item.id = Orders.item_id

        inner join Mall
        on Item.mall_id = Mall.id

        where Orders.user_id = ? and Orders.is_basket='Y'
        and Orders.is_purchased='N' and Orders.is_deleted='N'

        GROUP BY Mall.id
            ) Mall
on Item.mall_id = Mall.id

where Orders.user_id = ? and Orders.is_basket='Y'
  and Orders.is_purchased='N' and Orders.is_deleted='N';";

    $st = $pdo->prepare($query);
    $st->execute([$user_id,$user_id]);
//    $st->execute($user_id);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
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
    $query1 = "UPDATE Orders SET is_purchased = 'Y', is_basket = 'N' where user_id = ".$user_id." and id = ".$item1.";";
    $query2 = "UPDATE Orders SET is_purchased = 'Y', is_basket = 'N' where user_id = ".$user_id." and id = ".$item2.";";
    $query3 = "UPDATE Orders SET is_purchased = 'Y', is_basket = 'N' where user_id = ".$user_id." and id = ".$item3.";";
    $query4 = "UPDATE Orders SET is_purchased = 'Y', is_basket = 'N' where user_id = ".$user_id." and id = ".$item4.";";
    $query5 = "UPDATE Orders SET is_purchased = 'Y', is_basket = 'N' where user_id = ".$user_id." and id = ".$item5.";";

    $st = $pdo->prepare($query1);
    $st->execute([]);
    $st = null;

    if($item2 != 0){
        $st = $pdo->prepare($query2);
        $st->execute([]);
        $st = null;
    }

    if($item3 != 0){
        $st = $pdo->prepare($query3);
        $st->execute([]);
        $st = null;
    }

    if($item4 != 0){
        $st = $pdo->prepare($query4);
        $st->execute([]);
        $st = null;
    }

    if($item5 != 0){
        $st = $pdo->prepare($query5);
        $st->execute([]);
        $st = null;
    }

    $pdo = null;
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

//기존에 있는 쇼핑몰인지 판단
function isExistMall($mall_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Mall where id = ?) as exist";


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
    $query = "SELECT EXISTS(SELECT * FROM Tag where user_id = ? and id = ?) as exist";


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
    $query = "SELECT EXISTS(SELECT * FROM Item where id = ?) as exist";


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
    $query = "SELECT EXISTS(SELECT * FROM Comment where user_id = ? and id = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$comment_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//기존에 있는 리뷰인지 판단
function isExistAddress($zipcode,$address,$address_detail){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Address where zipcode = ? and address = ? and address_detail = ?) as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$zipcode,$address,$address_detail]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

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
                and Orders.is_deleted = 'N' and Orders.is_purchased = 'N' and Orders.is_basket='Y') as exist";


    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//장바구니에 등록된 아이템이 맞는지
function isExistOrder($user_id,$item_id){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.id = ?
                and Orders.is_deleted = 'N' and Orders.is_purchased = 'N') as exist";

    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$user_id,$item_id]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;$pdo = null;

    return intval($res[0]["exist"]);
}

//유저가 산 아이템이 맞는지
function isPurchaseUser($user_id,$item_id,$item_color,$item_size){

    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT * FROM Orders where Orders.user_id = ? and Orders.item_id = ? 
                and Orders.color = ? and Orders.size = ? and Orders.is_purchased = 'Y') as exist";


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
                and Orders.is_deleted = 'Y' and Orders.is_purchased = 'N' and Orders.is_basket='N') as exist";

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

// ------------------------보조 함수------------------------------
//장바구니 리셋
function order_reset(){

    $pdo = pdoSqlConnect();

    $query = "UPDATE Orders SET is_deleted = 'Y' where is_basket = 'N' and is_purchased = 'N';";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([]);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $st=null;
    $pdo = null;

    return;
}

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
    $query = "UPDATE Orders SET is_deleted = 'N', is_basket = 'N' 
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
    $query = "UPDATE Orders SET is_deleted = 'N', is_basket = 'Y' 
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
//function test($text)
//{
//    $pdo = pdoSqlConnect();
//    $query = "SELECT * FROM Item where Item.id = ?;";
//
//    $st = $pdo->prepare($query);
//    //    $st->execute([$param,$param]);
//    $st->execute($text);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return $res;
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
