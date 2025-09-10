<?php
date_default_timezone_set('Asia/Manila');
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
      $today = date("l");
      $currentTime = date("H:i:s");
      // echo "currentTime: $currentTime";
      // die();

      $sql = "SELECT * FROM tblfacultyschedule WHERE sched_day = :today ORDER BY sched_userId";
      $stmt = $conn->prepare($sql);
      $stmt->execute(['today' => $today]);
      $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $groupedSchedules = [];
      foreach ($schedules as $sched) {
        $userId = $sched['sched_userId'];
        if (!isset($groupedSchedules[$userId])) {
          $groupedSchedules[$userId] = [];
        }
        $groupedSchedules[$userId][] = $sched;
      }

      foreach ($groupedSchedules as $userId => $facultySchedules) {
        $newStatus = 1;
        $newNote = 'In Office';

        $isInClass = false;
        foreach ($facultySchedules as $sched) {
          $start = $sched['sched_startTime'];
          $end = $sched['sched_endTime'];
          if ($currentTime >= $start && $currentTime <= $end) {
            $isInClass = true;
            break;
          }
        }

        if ($isInClass) {
          $newStatus = 3;
          $newNote = 'In class';
        }

        $latestSql = "SELECT facStatus_statusMId 
                    FROM tblfacultystatus 
                    WHERE facStatus_userId = :userId 
                      AND DATE(facStatus_dateTime) = CURDATE()
                    ORDER BY facStatus_dateTime DESC 
                    LIMIT 1";
        $latestStmt = $conn->prepare($latestSql);
        $latestStmt->execute(['userId' => $userId]);
        $latestStatus = $latestStmt->fetchColumn();

        if ($latestStatus == 2 && $newStatus != 2) {
          continue;
        }

        if ($latestStatus != $newStatus) {
          $insertSql = "INSERT INTO tblfacultystatus 
                            (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime) 
                            VALUES (:userId, :statusMId, :note, NOW())";
          $insertStmt = $conn->prepare($insertSql);
          $insertStmt->execute([
            'userId' => $userId,
            'statusMId' => $newStatus,
            'note' => $newNote
          ]);
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
                  CONCAT(b.user_firstName, ' ', b.user_lastName) AS fullName, b.user_id, b.user_image,
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
              "user_image" => $row["user_image"],
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

  function getFacultySchedule($json)
  {
    // { "userId": 1 }
    include "connection.php";
    $data = json_decode($json, true);
    $userId = $data["userId"];
    $sql = "SELECT sched_id, sched_day,
            DATE_FORMAT(sched_startTime, '%l:%i %p') AS sched_startTime,
            DATE_FORMAT(sched_endTime, '%l:%i %p') AS sched_endTime,
            sched_userId
        FROM tblfacultyschedule 
        WHERE sched_userId = :userId
        ORDER BY FIELD(sched_day, 
            'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
        ), sched_startTime";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();
    $schedules = $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : 0;
    $facultyStatus = $this->getFacultyStatus($json);
    $returnValue = ["schedules" => $schedules, "status" => $facultyStatus];
    return $returnValue;
  }

  function addSchedule($json)
  {
    include "connection.php";
    $data = json_decode($json, true);

    $checkSql = "SELECT * 
            FROM tblfacultyschedule 
            WHERE sched_userId = :userId
              AND sched_day = :day
              AND (
                ( :startTime < sched_endTime AND :endTime > sched_startTime )
              )";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":userId", $data["userId"]);
    $checkStmt->bindParam(":day", $data["day"]);
    $checkStmt->bindParam(":startTime", $data["startTime"]);
    $checkStmt->bindParam(":endTime", $data["endTime"]);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
      return -1;
    }

    $sql = "INSERT INTO tblfacultyschedule (sched_day, sched_startTime, sched_endTime, sched_userId) 
            VALUES (:sched_day, :sched_startTime, :sched_endTime, :sched_userId)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":sched_day", $data["day"]);
    $stmt->bindParam(":sched_startTime", $data["startTime"]);
    $stmt->bindParam(":sched_endTime", $data["endTime"]);
    $stmt->bindParam(":sched_userId", $data["userId"]);
    $stmt->execute();

    return $stmt->rowCount() > 0 ? 1 : 0;
  }

  function getFacultyStatus($json)
  {
    // { "userId": 2 }
    include "connection.php";
    $data = json_decode($json, true);
    $this->setFacultyInClassStatus();
    $userId = $data["userId"];
    $sql = "SELECT * FROM tblfacultystatus WHERE facStatus_userId = :userId ORDER BY facStatus_dateTime DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : 0;
  }

  function changeFacultyStatus($json)
  {
    include "connection.php";
    $data = json_decode($json, true);
    $sql = "INSERT INTO tblfacultystatus (facStatus_userId, facStatus_note, facStatus_statusMId, facStatus_dateTime) VALUES (:userId, :notes, :status, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $data["userId"]);
    $stmt->bindParam(":notes", $data["notes"]);
    $stmt->bindParam(":status", $data["status"]);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? 1 : 0;
  }

  function addFaculty($json)
  {
    include "connection.php";
    $data = json_decode($json, true);

    $returnValueImage = uploadImage();

    switch ($returnValueImage) {
      case 2:
        return 2; // invalid type
      case 3:
        return 3; // upload error
      case 4:
        return 4; // too big
      default:
        break;
    }

    if (recordExists($data["email"], "tbluser", "user_email")) {
      return -1;
    } else if (recordExists($data["schoolId"], "tbluser", "user_schoolId")) {
      return -2;
    }

    if ($returnValueImage === "" || $returnValueImage === null) {
      $returnValueImage = "emptyImage.jpg";
    }

    $sql = "INSERT INTO tbluser (user_firstName, user_middleName, user_lastName, user_schoolId, user_password, user_email, user_level, user_image, user_isActive)
            VALUES (:firstName, :middleName, :lastName, :schoolId, :password, :email, 2, :image, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":firstName", $data["firstName"]);
    $stmt->bindParam(":middleName", $data["middleName"]);
    $stmt->bindParam(":lastName", $data["lastName"]);
    $stmt->bindParam(":schoolId", $data["schoolId"]);
    $stmt->bindParam(":password", $data["password"]);
    $stmt->bindParam(":email", $data["email"]);
    $stmt->bindParam(":image", $returnValueImage);

    $stmt->execute();
    return $stmt->rowCount() > 0 ? 1 : 0;
  }

  function getActiveFaculties()
  {
    include "connection.php";
    $sql = "SELECT * FROM tbluser WHERE user_level = 2 AND user_isActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : 0;
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

function uploadImage()
{
  if (isset($_FILES["file"])) {
    $file = $_FILES['file'];
    // print_r($file);
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    // $fileType = $_FILES['file']['type'];

    $fileExt = explode(".", $fileName);
    $fileActualExt = strtolower(end($fileExt));

    $allowed = ["jpg", "jpeg", "png"];

    if (in_array($fileActualExt, $allowed)) {
      if ($fileError === 0) {
        if ($fileSize < 25000000) {
          $fileNameNew = uniqid("", true) . "." . $fileActualExt;
          $fileDestination =  'images/' . $fileNameNew;
          move_uploaded_file($fileTmpName, $fileDestination);
          return $fileNameNew;
        } else {
          return 4;
        }
      } else {
        return 3;
      }
    } else {
      return 2;
    }
  } else {
    return "";
  }

  // $returnValueImage = uploadImage();

  // switch ($returnValueImage) {
  //     case 2:
  //         // You cannot Upload files of this type!
  //         return 2;
  //     case 3:
  //         // There was an error uploading your file!
  //         return 3;
  //     case 4:
  //         // Your file is too big (25mb maximum)
  //         return 4;
  //     default:
  //         break;
  // }
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
  case "getFacultySchedule":
    echo json_encode($admin->getFacultySchedule($json));
    break;
  case "addSchedule":
    echo $admin->addSchedule($json);
    break;
  case "getFacultyStatus":
    echo json_encode($admin->getFacultyStatus($json));
    break;
  case "changeFacultyStatus":
    echo $admin->changeFacultyStatus($json);
    break;
  case "addFaculty":
    echo $admin->addFaculty($json);
    break;
  case "getActiveFaculties":
    echo json_encode($admin->getActiveFaculties());
    break;
  default:
    echo "WALAY '$operation' NGA OPERATION SA UBOS HAHAHAHA BOBO";
    break;
}
