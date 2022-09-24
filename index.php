<?php

  session_start();

  $mode = 'input';

  $errmessage = array();

  if( isset($_POST['back']) && $_POST['back'] ){
    
  } else if( isset($_POST['confirm']) && $_POST['confirm'] ){

    // バリデーション（入力内容確認：名前）
    if ( !$_POST['fullname'] ) {
        $errmessage[] = "・名前を入力してください";
        
    } else if ( mb_strlen($_POST['fullname']) > 100 ){
        $errmessage[] = "・名前は100文字以内にしてください";

    }
    
    // クロスサイトスクリプション対策（サニタイズ）
    $_SESSION['fullname'] = htmlspecialchars($_POST['fullname'], ENT_QUOTES);
    
    // バリデーション（入力内容確認：Eメール）
    if ( !$_POST['email'] ) {
        $errmessage[] = "・Eメールを入力してください";
        
    } else if ( mb_strlen($_POST['email']) > 200 ){
        $errmessage[] = "・Eメールは200文字以内にしてください";
        
    } else if ( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ){
        $errmessage[] = "・Eメールアドレスが不正です";
        
    }
    
    // クロスサイトスクリプション対策（サニタイズ）
    $_SESSION['email'] = htmlspecialchars($_POST['email'], ENT_QUOTES);
    
    // バリデーション（入力内容確認：問い合わせ内容）
    if ( !$_POST['message'] ) {
        $errmessage[] = "・お問合せ内容を入力してください";
        
    } else if ( mb_strlen($_POST['message']) > 500 ){
        $errmessage[] = "・お問合せ内容は500文字以内にしてください";

    }
    
    // クロスサイトスクリプション対策（サニタイズ）
    $_SESSION['message'] = htmlspecialchars($_POST['message'], ENT_QUOTES);


    if ( $errmessage ){
        $mode = 'input';
    } else {
        // CSRF対策（クロスサイトリクエストフォージェリー）
        $token = bin2hex(random_bytes(32));
        $_SESSION['token'] = $token;
        $mode = 'confirm';
        
    }
    
} else if( isset($_POST['send']) && $_POST['send'] ){
    // 送信された時

    // CSRF対策（トークン情報が渡ってこなかった時）
    if( !$_POST['token'] || !$_SESSION['token'] || !$_SESSION['email'] ){
        $errmessage[]         = '不正な処理が行われました';
        $_SESSION['fullname'] = "";
        $_SESSION['email']    = "";
        $_SESSION['message']  = "";
        $_SESSION['token']    = "";
        $mode                 = 'input';
        
        // CSRF対策（トークンの一致を確認）
    } else if( $_POST['token'] != $_SESSION['token'] ){
        $errmessage[]         = '不正な処理が行われました';
        $_SESSION['fullname'] = "";
        $_SESSION['email']    = "";
        $_SESSION['message']  = "";
        $_SESSION['token']    = "";
        $mode                 = 'input';
    } else {

        $message = "お問い合わせを受け付けました。\r\n".
        "Name : " . $_SESSION['fullname'] . "\r\n".
        "Email : " . $_SESSION['email'] . "\r\n".
        "Infomation : " . preg_replace("/\r\n|\r|\n/", "\r\n", $_SESSION['message']); # 全てCRLF置換
        mail($_SESSION['email'], 'お問合せありがとうございます', $message);
        mail('nan0110.mh@gmail.com', 'お問合せありがとうございます', $message);
        
        $mode = 'send';
    }
        
} else {
    // セッションの値は予めクリアにしておく
    $_SESSION['fullname'] = "";
    $_SESSION['email']    = "";
    $_SESSION['message']  = "";
}


?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>お問合せフォーム</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <?php if( $mode == 'input' ) { ?>
        <?php
            if( $errmessage ){
                echo '<div class="alert alert-danger" role="alert">';
                echo implode('<br>', $errmessage );
                echo '</div>';
            }
        ?>

        <!-- 入力画面 -->
        <form action="./index.php" method="post"> 
            名前  <input type="text" class="form-control" name="fullname" value="<?php echo $_SESSION['fullname'] ?>"> <br>
            Eメール  <input type="email" class="form-control" name="email" value="<?php echo $_SESSION['email'] ?>"><br>
            お問い合わせ内容<br>  <textarea cols="40" rows="8" class="form-control" name="message"><?php echo $_SESSION['message'] ?></textarea><br>
            <div class="button">
                <input type="submit" class="btn btn-primary" name="confirm" value="Check">
            </div>
        </from>

    <?php } else if ( $mode == 'confirm' ) { ?>

        <!-- 確認画面 -->
        <form action="./index.php" method="post">
            <!-- CSRF対策（トークンの送信） -->
            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

            名前
            <div class = "box" ><?php echo $_SESSION['fullname'] ?></div><br>
            Eメール
            <div class = "box" ><?php echo $_SESSION['email'] ?></div><br>
            お問い合わせ内容
            <div class = "box" ><?php echo nl2br($_SESSION['message']) ?></div><br>
            <div class="button">
                <input type="submit" class="btn btn-primary" name="back" value="Back"/>
                <input type="submit" class="btn btn-primary" name="send" value="Send"/>
            </div>
        </form>

    <?php } else { ?>
        <!-- 完了画面 -->
        お問合せありがとうございました。

    <?php } ?>

</body>
</html>