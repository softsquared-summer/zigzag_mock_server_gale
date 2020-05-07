<?php

//아이템 리뷰 총평 조회 API
function getItemSummary($item_id)
{
    $pdo = pdoSqlConnect();
    $query = "select

Comment.item_id as item_id,
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

from Comment

inner join comment_summary
on comment_summary.id = Comment.id

where Comment.item_id = ?

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
function getItemComments($item_id,$page)
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
comment_image.image_url as image_url


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

where Item.id = ? and Comment.is_deleted = 'N'

order by Comment.id desc
";

    $st = $pdo->prepare($query." limit ".(($page-1)*6).", 6;");
    $st->execute([$item_id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

//자신이 구매한 아이템 리뷰 리스트 조회 API
function getItemCommentsMyself($user_id, $item_id,$page)
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

where Comment.user_id = ? and Item.id = ? and Comment.is_deleted = 'N'

order by Comment.id desc
";

    $st = $pdo->prepare($query." limit ".(($page-1)*6).", 6;");
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