<?php 

  require_once('const.php');
  date_default_timezone_set('Asia,Tokyo');

  //変数の初期化
  $mysqli             = null;  //データベース接続
  $sql                = null;  //クエリ入れる変数
  $res                = null;  //データベースによるresult
  $ynj_date           = null;  //どの年月日を読み込むか最初に決めるための変数
  $check_ym           = null;  //$ynj_dateの年月を入れる変数
  $head_title         = null;  //$ynj_dateをY年n月という形に変える head_titleとDB SELECTで使う
  $ynj_first_day      = null;  //$ynj_dateの1日を取得する ○-○-1の形で
  $total_day          = null;  //$ynj_dateの月の 総日数を取得する 例）31
  $youbi              = null;  //$ynj_first_day、1日の曜日（数字）をとる、この数分の空セルを最初のtdに入れる  例）6
  $total_day_youbi    = null;  //$total_day(総日数)+$youbi(曜日数字)の合計を入れる変数  例）37
  $all_td_day         = null;  //行数 × 7日
  $end_nothing_td_num = null;  //( $all_td_day - $total_day_youbi ) 例）42 - 37 = 5
  $for_ynj_first_day  = null;  //for文で回すもの  2020-8-1,2020-8-2,2020-8-3,...というように、forが一回回るごとに日にちが増えていく
  $read_array = array();       //データベースを読み込んだ物が入る配列
  $date = null;
  $title = null;
  $content = null;
  $place = null;
  $time = null;
  $g_id = null;

  //削除機能
  if(!empty($_GET['gid'])) {
    $g_id = $_GET['gid'];
    $mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if($mysqli->connect_errno) {
      echo 'エラーが発生しました。' . $mysqli->connect_errno . '：' . $mysali->connect_error;
    } else {
      $sql = "DELETE FROM calendar_table WHERE id = '$g_id'";
      
      $res = $mysqli->query($sql);
    }

    $mysqli->close();

  } 


  //データの書き込み
  if(!empty($_POST['register'])) {
    $date = $_POST['date'];     //2020-8-1
    $date = date('Y-m-d', strtotime($date));   //2020-08-01に変更
    $title = $_POST['title'];     //飲み会
    $content = $_POST['content']; //本文
    $place = $_POST['place'];     //場所
    $time = $_POST['time'];       //20:20
    $time = date('H:i', strtotime($time)) . ":00";   //20:20:00
    
    //サニタイズ化
    $date = htmlspecialchars( $_POST['date'], ENT_QUOTES);
    
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES);
    $title = preg_replace( '/\\r\\n|\\n|\\r/', '', $title);
    
    $content = htmlspecialchars($_POST['content'], ENT_QUOTES);
    $content = nl2br($content);
    
    $place = htmlspecialchars($_POST['place'], ENT_QUOTES);
    $place = preg_replace( '/\\r\\n|\\n|\\r/', '', $place);
    
    $time = htmlspecialchars($_POST['time'], ENT_QUOTES);
    
    $mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if($mysqli->connect_errno) {
      echo 'エラーが発生しました。' . $mysqli->connect_errno . '：' . $mysali->connect_error;
    } else {
      
      $mysqli->set_charset('utf8');  //文字コード
      
      $sql = "INSERT INTO calendar_table (title, content, place, time, date) VALUES ('$title', '$content', '$place', '$time', '$date')";
      
      $res = $mysqli->query($sql);
    }
  
    $mysqli->close();

  } 

  
  //読み込みに使う物
  if($_POST['register']) {                                        //$ynj_date(年月),$day_date(日)を指定する
      //登録フォームに表示するための変数
      $input_check_date = date('Y-n-j', strtotime($_POST['date']));
      //1970-4-1
      $ynj_date = date('Y-n', strtotime($_POST['date'])) . "-1";
      //1  何日がクリックされたか、された物の日数、数字が入る
      $day_date = date('j', strtotime($_POST['date']));

  } elseif (empty($_POST['register']) && !empty($_GET['ym']) && empty($_POST['decision'])) {   //$ynj_date(年月),$day_date(日)を指定する
      //登録フォームに表示するための変数
      $input_check_date = date('Y-n', strtotime($_GET['ym']));
      $input_check_date = $input_check_date . "-" .($_GET['day']);
      //1970-4-1
      $ynj_date = $_GET['ym'] . "-1";
      //1  何日がクリックされたか、された物の日数、数字が入る
      $day_date = $_GET['day'];

  } else {                                                        //elseの場合は、左側のフォームは出ない
    if(!empty($_POST['decision'])) {    //$ynj_dateを指定
      //1970-4-1
      $ynj_date = $_POST['ym'] . "-1";  //$ynj_dateを指定

    } else {
      //2020-8-29
      $ynj_date = date('Y-n-j');
    }
  }


  //1970-8
  $check_ym = date('Y-n', strtotime($ynj_date));
  $get_check_ym = $check_ym;

  //1970年8月
  $head_title = date('Y年n月', strtotime($ynj_date));

  //2020-8-1
  $ynj_first_day = date('Y-n-1', strtotime($ynj_date));
  //31
  $total_day = date('t', strtotime($ynj_first_day));
  //6
  $youbi = date('w', strtotime($ynj_first_day));
  //37
  $total_day_youbi = $total_day + $youbi;
  //42
  $all_td_day = (floor($total_day_youbi / 7) + 1) * 7;
  //5
  $end_nothing_td_num = $all_td_day - $total_day_youbi;

  
  //連想配列に入れるための配列
  $seven_array = array();
  //連想配列
  $month_array = array();
  //2020-8-1 for文で回すための
  $for_ynj_first_day = $ynj_first_day;

  //最初の空のtdを入れる
  $first_nothing_td = array();
  //最後の空のtdを入れる
  $end_nothing_td = array();

  //optionを入れる配列
  $option_array = array();

  //optionの時間設定を入れる配列
  $option_time_array = array();

  for($h = 0; $h < 24; $h++) {
    if($h < 10) {
      $vh = '0' . $h;
    } else {
      $vh = $h;
    }
    for($m = 0; $m < 60; $m=$m+10) {
      if($m < 10) {
        $vm = '0' . $m;
      } else {
        $vm = $m;
      }
      $option_time_array[] = "<option value='" . $h . ":" . $m . "'>" . $vh . "時" . $vm . "分" . "</option>";
    }
  }
  

  //optionを作る
  for($i = 1970; $i <= 2100; $i++) {
    for($j = 1; $j <= 12; $j++) {
      $check = $i . '-' . $j;
      if($check_ym == $check){
        $option_array[] = "<option selected value='" . $i . "-" . $j . "'>" . $i . "年" . $j . "月" . "</option>";
      } else {
        $option_array[] = "<option value='" . $i . "-" . $j . "'>" . $i . "年" . $j . "月" . "</option>";
      }
    }
  }

  //最初の空のtd
  for($i = 1; $i <= $youbi; $i++) {
    $first_nothing_td[] = '<td></td>';
  }
  
  //最後の空のtd
    for($i = 1; $i <= $end_nothing_td_num; $i++) {
      $end_nothing_td[] = '<td></td>';
    }
  
  //カレンダーの日付
  for($i = 1; $i <= $total_day; $i++) {
    $seven_array[] = $for_ynj_first_day;
    $for_ynj_first_day = date('Y-n-j', strtotime('+1 day', strtotime($for_ynj_first_day)));
    if($i % 7 == 0) {
      $month_array[] = $seven_array;
      $seven_array = array();
    }
    if($i == $total_day) {
      $month_array[] = $seven_array;
      $seven_array = array();
    }
  }

  //書き込み作業


  //読み込み作業
  $mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME);

  if($mysqli->connect_errno) {
    echo 'エラーが発生しました。' . $mysqli->connect_errno . '：' . $mysali->connect_error;
  } else {
    $check_ym = date('Y-m', strtotime($check_ym)) . '%';
    $sql = "SELECT * FROM calendar_table WHERE date LIKE '$check_ym' ORDER BY time ASC";

    $res = $mysqli->query($sql);
    if($res) {
      // echo '読み込み成功';
      $read_array = $res->fetch_all(MYSQLI_ASSOC);
    }
  }

  $mysqli->close();

  // var_dump($read_array);
  







?>



<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel=“stylesheet” href=“https://unpkg.com/ress/dist/ress.min.css”>
  <link rel="stylesheet" href="./style.css">

</head>

<body>

    <header>
    <form class="ym-form" action="" method="post">
      <h1><?php echo $head_title ?></h1>
      <p>
      <select name="ym">
        <?php foreach($option_array as $value): ?>
          <?php echo $value; ?>
        <?php endforeach ?>
      </select>
      <input type="submit" name="decision" value="決定">
      </p>
    </form>
    </header>
  
  <div class="container">

  <?php if(!empty($input_check_date)): ?>

  <div class="left">


    
    <div class="view"> <!-- すでに登録内容がある場合のみ表示する -->
    <h4>登録済みの予定</h4>
      <?php foreach($read_array as $r): ?>
      <?php if($_GET['day'] == date('j', strtotime(($r)['date']))):  ?>
        <div class="view-list">
          <p><?php echo date("Y年m月d日", strtotime($r['date'])) ?></p>
          <p class="p-mt-mb">時間：</p>
          <p class="p-mb"><input type="text" readonly value="<?php echo date('H時i分〜', strtotime($r['time'])) ?>"></p>
          <p class="p-mt-mb">タイトル：</p>
          <p class="p-mb"><input type="text" readonly value="<?php echo $r['title'] ?>"></p>
          <p class="p-mt-mb">詳細：</p>
          <p class="p-mb"><textarea readonly><?php echo nl2br($r['content']) ?></textarea></p>
          <p class="p-mt-mb">場所：</p>
          <p class="p-mb"><input type="text" readonly value="<?php echo $r['place'] ?>"></p>
          <p><a class="delete-btn" href="index.php?gid=<?php echo $r['id']; ?>&ym=<?php echo date('Y-m',strtotime($r['date'])) ?>&day=<?php echo date('j', strtotime(($r)['date'])) ?>">削除</a></p>
        </div>
      <?php endif ?>
      <?php endforeach ?>
    </div>
    

    <form class="create-form" action="index.php" method="post">  <!-- 登録フォーム -->
      <h4>新しい予定の追加</h4>
      <p>予定の月日：</p>
      <p><input type="text" name="date" readonly value="<?php echo date('Y-n-j', strtotime($input_check_date)) ?>"></p>
      <p>タイトル：</p>
      <p><input type="text" name="title" value=""></p>
      <p>内容：</p>
      <p><input type="text" name="content" value=""></p>
      <p>場所：</p>
      <p><input type="text" name="place" value=""></p>
      <p>時間：</p>
      <p><select name="time">
        <?php foreach($option_time_array as $value): ?>
          <?php echo $value; ?>
        <?php endforeach ?>
      </select></p>
      <p><input type="submit" name="register" value="登録"></p>
    </form>
  </div><!-- left -->
  <?php else: ?>
    <div class="nothing-left"></div>
  <?php endif ?>

  <div class="right">

    <table>

      <tr><th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th></tr>

      <tr>
        <!-- 空のtdを入れていく -->
        <?php foreach($first_nothing_td as $value): ?>
          <?php echo $value ?>
        <?php endforeach ?>

        <!-- カレンダーのメインの日付を入れていく -->
        <?php foreach($month_array as $value): ?>    <!-- 連想配列を$valueに入れて回す -->
          <?php foreach($value as $v): ?>            <!-- $valueの中に入っている配列を全て回す 0000-0-0 -->
            <td class="td-flex">
              <p class="td-in-p"><a href="index.php?ym=<?php echo $get_check_ym; ?>&day=<?php echo date('j', strtotime($v)); ?>"><?php echo date('j', strtotime($v)) ?></a></p>

                <div class="title-list">             <!-- 予定のタイトルが入る -->
                <?php foreach($read_array as $r): ?>
                  <?php if(date('j', strtotime($v)) == date('j', strtotime(($r)['date']))):  ?>
                    <p>・<?php echo $r['title'] ?></p>
                  <?php endif ?>
                <?php endforeach ?>
                </div>
              <!-- ifでデータベースからとってきた日付条件が同じものを<p>タグで表示する for $i = 1; $i <= 3; $i++ として3個まで -->
            </td>

              <?php if(((date('j', strtotime($v)) + $youbi) % 7 == 0)): ?>  <!-- もしもtdが7個目まで入ったら -->
                </tr><tr>
              <?php endif ?>

          <?php endforeach ?>
        <?php endforeach ?>

        <!-- 空のtdを入れていく -->
        <?php foreach($end_nothing_td as $value): ?>
          <?php echo $value ?>
        <?php endforeach ?>
      </tr>

    </table>
  </div>
  </div>  


  <footer>
    <p>2020/08/30 作成</p>
  </footer>
</body>

</html>