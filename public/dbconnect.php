<?php
$id = $_POST['users_id'];

// データベース接続

$host = 'localhost';
$dbname = 'projectd';
$dbuser = '0000';
$dbpass = '0000';

try {
$dbh = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $dbuser,$dbpass, array(PDO::ATTR_EMULATE_PREPARES => false));
} catch (PDOException $e) {
 var_dump($e->getMessage());
 exit;
}
// データ取得
$sql = "SELECT spots_id, spots_name, spots_count, spots_status FROM spots WHERE users_id = ?";
$stmt = ($dbh->prepare($sql));
$stmt->execute(array($id));

$spot_list = array();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
 $spot_list[]=array(
  'spots_id' =>$row['spots_id'],
  'spots_name'=>$row['spots_name'],
  'spots_count'=>$row['spots_count'],
  'spots_status'=>$row['spots_status']
 );
}

//jsonとして出力
header('Content-type: application/json');
echo json_encode($spot_list,JSON_UNESCAPED_UNICODE);