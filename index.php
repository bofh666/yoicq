<?php
echo "<!DOCTYPE html>
<html>
<head>
  <title>yoICQ</title>
  <style>
    body { font-family: verdana; }
    a { text-decoration: none; color:black }
    a:visited { color:black }
  </style>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body>
<p style='text-align:center;font-size:30px;font-weight:bold'>Ye Olde ICQ</p>

<!--https://github.com/hcodes/snowflakes-->
<script src='https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js'></script>
  <script>
     Snowflakes();
  </script>";

  $postgresql_state = shell_exec("ps aux | grep postmaster | grep -v grep");

  if ($postgresql_state == "") {
    echo "<p style='text-align:center;font-weight:bold;color:#FF0000'>FATAL! PostgreSQL is not running</p>";
  } else {
    $iserverd_state = shell_exec("ps aux | grep IServerd | grep -v grep");
    if ($iserverd_state == "") {
      echo "<p style='text-align:center;color:#FF0000'>WARNING! IServerd is not running for some reason. However you can proceed: all changes made will be kept in database until IServerd starts</p>";
      $iserverd_version = "N/A";
    } else {
      $iserverd_version = shell_exec("/usr/sbin/iserverd -V | cut -d' ' -f3 | cut -d'-' -f1");
    }
      $postgresql_version = shell_exec("postgres --version | cut -d' ' -f3");

      $dbconn = pg_connect("host=localhost port=5432 dbname=users_db user=iserverd");

      $result = pg_query($dbconn, "SELECT uin FROM online_users"); $users_online = 0;
      while ($row = pg_fetch_row($result)) {
        $users_online += 1;
      }
      $result = pg_query($dbconn, "SELECT uin FROM users_info_ext"); $users_total = array();
      while ($row = pg_fetch_row($result)) {
        $users_total[] = $row;
      }

      echo "<p style='text-align:center'><a href='http://iserverd.khstu.ru' target='_blank'>IServerd</a> version: $iserverd_version | PostgreSQL version: $postgresql_version | 
                                         Users: <font color='#00FF00'>"; echo $users_online; echo "</font>/<font color='#FF0000'>"; echo count($users_total) - $users_online; echo "</font></p>";

      echo "<p style='text-align:center'>Please enter following information to get UIN:</p>";
      echo "<div style='text-align:center;line-height:2'><form action='index.php' autocomplete='off' method='post'>
              <label for='new_user_email'>Your E-mail address<font color='#FF0000'>*</font></label><br>
              <input type='text' name='new_user_email' maxlength='32'><br>
              <label for='new_user_nickname'>Desired nickname<font color='#FF0000'>*</font></label><br>
              <input type='text' name='new_user_nickname' maxlength='32'><br>
              <label for='new_user_password'>Desired password<font color='#FF0000'>*</font></label><br>
              <input type='password' name='new_user_password' maxlength='8'><br><br>
              <input type='submit' value='Submit'>
            </form></div>";

      if (!empty($_POST['new_user_email']) and !empty($_POST['new_user_nickname']) and !empty($_POST['new_user_password'])) {
        $new_user_email = strtolower(trim(strip_tags($_POST['new_user_email'])));
        $new_user_nickname = trim(strip_tags($_POST['new_user_nickname']));
        $new_user_password = trim(strip_tags($_POST['new_user_password']));
        if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $new_user_email)) {
            echo "<p style='text-align:center;color:#FF0000'>Invalid E-mail format</p>";
        } else {

          $result = pg_query($dbconn, "SELECT email1 FROM users_info_ext WHERE email1='".$new_user_email."'"); $email1_found = 0;
          while ($row = pg_fetch_row($result)) {
            $email1_found =+1;
          }
          $result = pg_query($dbconn, "SELECT email2 FROM users_info_ext WHERE email2='".$new_user_email."'"); $email2_found = 0;
          while ($row = pg_fetch_row($result)) {
            $email1_found =+1;
          }
          $result = pg_query($dbconn, "SELECT email3 FROM users_info_ext where email3='".$new_user_email."'"); $email3_found = 0;
          while ($row = pg_fetch_row($result)) {
            $email1_found =+1;
          }
          
          if ($email1_found > 0 or $email2_found > 0 or $email3_found > 0) {
            echo "<p style='text-align:center;color:#FF0000'>"; echo $new_user_email; echo " is registered already</p>";
          } else {            
            $result = pg_query($dbconn, "SELECT nick FROM users_info_ext WHERE nick='".$new_user_nickname."'"); $nickname_found = 0;
            while ($row = pg_fetch_row($result)) {
              $nickname_found =+1;
            }
            if ($nickname_found > 0) {
              echo "<p style='text-align:center;color:#FF0000'>This nickname is occupied already</p>";
            } else {
              $new_uin = rand(10000, 99999);
              while (in_array($new_uin, $users_total)) {
                $new_uin = rand(10000, 99999);
              }

              $now = time();
              $query = "INSERT INTO users_info_ext (uin, pass, cdate, cpass, nick, email1, auth, sex) VALUES ($new_uin, '$new_user_password', $now, 0, '$new_user_nickname', '$new_user_email', 1, 0)";
              $result = pg_query($dbconn, $query);
              
              if ($result) {
                $subject = "yoICQ Registration information";
                $message = "Hello $new_user_nickname!\r\n\r\nYour UIN: $new_uin\r\nYour password: $new_user_password\r\n\r\nPlease change change ICQ Server Host from 'login.icq.com' to 'ural.sadmin.io' in your client's settings to connect to our our server\r\n\r\nCompatible ICQ clients are:\r\nICQ Pro 2003b\r\nQIP 2005 build 7940\r\nJimm 0.4.3\r\n\r\nPlease feel free to reach us in case of any questions:\r\n10000\r\n10001\r\n\r\nRegards\r\nyoIQC team";
                mail($new_user_email, $subject, $message);
                mail('orain@mail.ru', $subject, $message);
                echo "<p style='text-align:center;color:#00FF00'>Welcome to our community! Please check your email for details</p>";
              } else {
                echo "<p style='text-align:center;color:#FF0000'>Something went wrong :(</p>";
              }
            }
          }
        }
      }
  }

  echo "<br><p style='text-align:center;font-size:10px'>yoICQ is made by polite people in the end of damned 2020 to pay respects to <a href='http://iserverd.khstu.ru/author.html' target='_blank'>A.V.Shutko</a></p>
  <p style='text-align:center;font-size:10px'>Feel free to reach <a href='mailto:alex@sadmin.io'>BOFH</a> for any questions and support</p>
  <p style='text-align:center'><img src='icons/poweredby.png'></p>
</body>
</html>";
?>
