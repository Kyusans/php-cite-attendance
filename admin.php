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

    $sql = "SELECT a.*, CONCAT(b.user_lastName, ', ', b.user_firstName, ' ', b.user_middleName) AS fullName,
            s.facStatus_id, s.facStatus_statusMId, s.facStatus_note, s.facStatus_dateTime
        FROM tblfacultyschedule a
        INNER JOIN tbluser b ON b.user_id = a.sched_userId
        LEFT JOIN (
            SELECT x.facStatus_userId, x.facStatus_id, x.facStatus_statusMId, x.facStatus_note, x.facStatus_dateTime
            FROM tblfacultystatus x
            INNER JOIN (
                SELECT facStatus_userId, MAX(facStatus_dateTime) AS latestStatusTime
                FROM tblfacultystatus
                GROUP BY facStatus_userId
            ) y ON x.facStatus_userId = y.facStatus_userId AND x.facStatus_dateTime = y.latestStatusTime
        ) s ON s.facStatus_userId = a.sched_userId
        ORDER BY b.user_id, a.sched_day, a.sched_startTime
    ";

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
            "latestStatus" => [
              "facStatus_id" => $row["facStatus_id"],
              "facStatus_statusMId" => $row["facStatus_statusMId"],
              "facStatus_note" => $row["facStatus_note"],
              "facStatus_dateTime" => $row["facStatus_dateTime"]
            ],
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


  function setFacultyInClassStatus()
  {
    include "connection.php";

    try {
      // Get today's day name (e.g., "Wednesday")
      $today = date("l");

      // Get current time
      $currentTime = date("H:i:s");

      // Fetch all schedules for today
      $sql = "SELECT * FROM tblfacultyschedule WHERE sched_day = :today";
      $stmt = $conn->prepare($sql);
      $stmt->execute(['today' => $today]);
      $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($schedules as $sched) {
        $start  = $sched['sched_startTime'];
        $end    = $sched['sched_endTime'];
        $userId = $sched['sched_userId'];

        // ✅ Get the latest status of this faculty for today
        $latestSql = "SELECT facStatus_statusMId 
                      FROM tblfacultystatus 
                    WHERE facStatus_userId = :userId 
                      AND DATE(facStatus_dateTime) = CURDATE()
                  ORDER BY facStatus_dateTime DESC 
                    LIMIT 1";
        $latestStmt = $conn->prepare($latestSql);
        $latestStmt->execute(['userId' => $userId]);
        $latestStatus = $latestStmt->fetchColumn();

        // If current time is within the faculty's schedule → IN CLASS
        if ($currentTime >= $start && $currentTime <= $end) {
          $checkSql = "SELECT COUNT(*) FROM tblfacultystatus 
                          WHERE facStatus_userId = :userId 
                            AND DATE(facStatus_dateTime) = CURDATE()
                            AND facStatus_statusMId = 3";
          $checkStmt = $conn->prepare($checkSql);
          $checkStmt->execute(['userId' => $userId]);
          $exists = $checkStmt->fetchColumn();

          if ($exists == 0) {
            $insertSql = "INSERT INTO tblfacultystatus 
                                (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime) 
                                VALUES (:userId, 3, 'In class', NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->execute(['userId' => $userId]);
          }
        } else {
          if ($latestStatus != 2) {
            $checkSql = "SELECT COUNT(*) FROM tblfacultystatus 
                            WHERE facStatus_userId = :userId 
                              AND DATE(facStatus_dateTime) = CURDATE()
                              AND facStatus_statusMId = 1";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute(['userId' => $userId]);
            $exists = $checkStmt->fetchColumn();

            if ($exists == 0) {
              $insertSql = "INSERT INTO tblfacultystatus 
                                  (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime) 
                                  VALUES (:userId, 1, 'In Office', NOW())";
              $insertStmt = $conn->prepare($insertSql);
              $insertStmt->execute(['userId' => $userId]);
            }
          }
        }
      }

      return 1;
    } catch (\Throwable $th) {
      return $th;
    }
  }


  function getTodayFacultySchedules()
  {

    include "connection.php";
    try {
      $this->setFacultyInClassStatus();
      $sql = "SELECT a.*, 
                  CONCAT(b.user_lastName, ', ', b.user_firstName, ' ', b.user_middleName) AS fullName,
                  s.facStatus_id, s.facStatus_statusMId, s.facStatus_note, s.facStatus_dateTime
            FROM tblfacultyschedule a
            INNER JOIN tbluser b ON b.user_id = a.sched_userId
            LEFT JOIN (
                SELECT x.facStatus_userId, x.facStatus_id, x.facStatus_statusMId, x.facStatus_note, x.facStatus_dateTime
                FROM tblfacultystatus x
                INNER JOIN (
                    SELECT facStatus_userId, MAX(facStatus_dateTime) AS latestStatusTime
                    FROM tblfacultystatus
                    GROUP BY facStatus_userId
                ) y ON x.facStatus_userId = y.facStatus_userId 
                  AND x.facStatus_dateTime = y.latestStatusTime
            ) s ON s.facStatus_userId = a.sched_userId
            WHERE a.sched_day = DAYNAME(CURDATE())
            ORDER BY b.user_id, a.sched_startTime";

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
              "statusMId" => $row["facStatus_statusMId"],
              "status_note" => $row["facStatus_note"],
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
    } catch (\Throwable $th) {
      return 0;
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
  case "setFacultyInClassStatus":
    echo $admin->setFacultyInClassStatus();
    break;
  case "getTodayFacultySchedules":
    echo json_encode($admin->getTodayFacultySchedules());
    break;
  default:
    echo "WALAY '$operation' NGA OPERATION SA UBOS HAHAHAHA BOBO";
    break;
}
