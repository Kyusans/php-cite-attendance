<?php
include "headers.php";

class Admin
{
  function login($json)
  {
    // { "email": "admin@gmail.com", "password": "admin" }
    include "connection.php";
    $data = json_decode($json);
    $email = $data->email;
    $password = $data->password;
    $sql = "SELECT * FROM tbluser WHERE user_email = :email AND BINARY user_password = :password";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? json_encode($stmt->fetch(PDO::FETCH_ASSOC)) : 0;
  }
  function getAllFacultySchedules()
  {
    include "connection.php";
    $sql = "SELECT a.*, CONCAT(b.user_lastName, ', ', b.user_firstName, ' ', b.user_middleName) AS fullName 
            FROM tblfacultyschedule a
            INNER JOIN tbluser b ON b.user_id = a.sched_userId
            ORDER BY b.user_id, a.sched_day, a.sched_startTime";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $grouped = [];
      foreach ($rows as $row) {
        $userId = $row['sched_userId'];

        if (!isset($grouped[$userId])) {
          $grouped[$userId] = [
            "userId" => $userId,
            "fullName" => $row['fullName'],
            "schedules" => []
          ];
        }

        $grouped[$userId]["schedules"][] = [
          "sched_id" => $row["sched_id"],
          "sched_day" => $row["sched_day"],
          "sched_startTime" => $row["sched_startTime"],
          "sched_endTime" => $row["sched_endTime"]
        ];
      }

      return array_values($grouped);
    } else {
      return [];
    }
  }
} //admin 

function recordExists($value, $table, $column)
{
  include "connection.php";
  $sql = "SELECT COUNT(*) FROM $table WHERE $column = :value";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(":value", $value);
  $stmt->execute();
  $count = $stmt->fetchColumn();
  return $count > 0;
}

$json = isset($_POST["json"]) ? $_POST["json"] : "0";
$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";

$admin = new Admin();

switch ($operation) {
  case "login":
    echo $admin->login($json);
    break;
  case "getAllFacultySchedules":
    echo json_encode($admin->getAllFacultySchedules());
    break;
  default:
    echo "WALAY '$operation' NGA OPERATION SA UBOS HAHAHAHA BOBO";
    break;
}
