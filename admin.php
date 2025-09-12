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
    date_default_timezone_set('Asia/Manila');

    $nowTs = time();
    $today = date('l'); // e.g. Wednesday

    // Get today’s schedules
    $sql = "SELECT a.sched_userId, a.sched_startTime, a.sched_endTime
            FROM tblfacultyschedule a
            INNER JOIN tbluser b ON b.user_id = a.sched_userId
            WHERE a.sched_day = :today";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($schedules as $sched) {
      $userId  = $sched['sched_userId'];
      $startTs = strtotime($sched['sched_startTime']);
      $endTs   = strtotime($sched['sched_endTime']);

      // check the latest status row for this user today
      $sqlCheck = "SELECT facStatus_id, facStatus_statusMId
                       FROM tblfacultystatus
                      WHERE facStatus_userId = :userId
                        AND DATE(facStatus_dateTime) = CURDATE()
                      ORDER BY facStatus_dateTime DESC LIMIT 1";
      $stmtCheck = $conn->prepare($sqlCheck);
      $stmtCheck->execute([':userId' => $userId]);
      $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

      // ⛔ Skip if the current status is 2 (Out)
      if ($existing && (int)$existing['facStatus_statusMId'] === 2) {
        continue;
      }

      // compute status
      if ($nowTs >= $startTs && $nowTs <= $endTs) {
        $statusMId = 3; // In Class
        $statusNote = "In Class";
      } else {
        $statusMId = 1; // In Office
        $statusNote = "In Office";
      }

      if ($existing) {
        // Update existing row for today
        $sql2 = "UPDATE tblfacultystatus 
                        SET facStatus_statusMId = :statusMId,
                            facStatus_note = :note,
                            facStatus_dateTime = NOW()
                      WHERE facStatus_id = :id";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
          ':statusMId' => $statusMId,
          ':note' => $statusNote,
          ':id' => $existing['facStatus_id']
        ]);
      } else {
        // Insert new row for today
        $sql2 = "INSERT INTO tblfacultystatus 
                        (facStatus_userId, facStatus_statusMId, facStatus_note, facStatus_dateTime)
                    VALUES (:userId, :statusMId, :note, NOW())";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
          ':userId' => $userId,
          ':statusMId' => $statusMId,
          ':note' => $statusNote
        ]);
      }
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
    $this->setFacultyInClassStatus();
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
    // make PDO throw exceptions so we can see errors
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $data = json_decode($json, true);
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;
    $status = isset($data['status']) ? (int)$data['status'] : 0;
    $notes  = isset($data['notes']) ? $data['notes'] : '';

    try {
      // Check if there's already a row for today (use CURDATE() to match DB date)
      $sqlCheck = "SELECT facStatus_id FROM tblfacultystatus 
                     WHERE facStatus_userId = :userId
                       AND DATE(facStatus_dateTime) = CURDATE()
                     ORDER BY facStatus_id DESC LIMIT 1";
      $stmtCheck = $conn->prepare($sqlCheck);
      $stmtCheck->execute([':userId' => $userId]);
      $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

      if ($existing) {
        // Update today's row
        $sql = "UPDATE tblfacultystatus
                    SET facStatus_note = :notes,
                        facStatus_statusMId = :status,
                        facStatus_dateTime = NOW()
                    WHERE facStatus_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
          ':notes'  => $notes,
          ':status' => $status,
          ':id'     => $existing['facStatus_id']
        ]);
      } else {
        // Insert new row for today
        $sql = "INSERT INTO tblfacultystatus 
                    (facStatus_userId, facStatus_note, facStatus_statusMId, facStatus_dateTime) 
                    VALUES (:userId, :notes, :status, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
          ':userId' => $userId,
          ':notes'  => $notes,
          ':status' => $status
        ]);
      }

      return $stmt->rowCount() > 0 ? 1 : 0;
    } catch (\PDOException $e) {
      // log server-side for debugging (do not echo in prod)
      error_log("changeFacultyStatus error: " . $e->getMessage());
      return 0;
    }
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

    $password = $data["schoolId"] . $data["lastName"];
    $sql = "INSERT INTO tbluser (user_firstName, user_middleName, user_lastName, user_schoolId, user_password, user_email, user_level, user_image, user_isActive)
            VALUES (:firstName, :middleName, :lastName, :schoolId, :password, :email, 2, :image, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":firstName", $data["firstName"]);
    $stmt->bindParam(":middleName", $data["middleName"]);
    $stmt->bindParam(":lastName", $data["lastName"]);
    $stmt->bindParam(":schoolId", $data["schoolId"]);
    $stmt->bindParam(":password", $password);
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

  function getFacultyProfile($json)
  {
    include "connection.php";
    $data = json_decode($json, true);
    $sql = "SELECT * FROM tbluser WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $data["userId"]);
    $stmt->execute();
    return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : 0;
  }

  function updateSchedule($json)
  {
    include "connection.php";
    $data = json_decode($json, true);

    $schedId   = $data["sched_id"];
    $userId    = $data["userId"];
    $day       = $data["day"];
    $startTime = $data["startTime"];
    $endTime   = $data["endTime"];

    // 1️⃣ Check for conflicts except itself
    $checkSql = "SELECT * 
                FROM tblfacultyschedule 
                WHERE sched_userId = :userId
                  AND sched_day = :day
                  AND sched_id <> :schedId
                  AND ( :startTime < sched_endTime AND :endTime > sched_startTime )";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":userId", $userId);
    $checkStmt->bindParam(":day", $day);
    $checkStmt->bindParam(":schedId", $schedId);
    $checkStmt->bindParam(":startTime", $startTime);
    $checkStmt->bindParam(":endTime", $endTime);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
      return -1; // Conflict with another schedule
    }

    // 2️⃣ Update the schedule
    $sql = "UPDATE tblfacultyschedule 
            SET sched_day = :day,
                sched_startTime = :startTime,
                sched_endTime = :endTime
            WHERE sched_id = :schedId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":day", $day);
    $stmt->bindParam(":startTime", $startTime);
    $stmt->bindParam(":endTime", $endTime);
    $stmt->bindParam(":schedId", $schedId);
    $stmt->execute();

    return $stmt->rowCount() > 0 ? 1 : 0;
  }

  function deleteSchedule($json)
  {
    // { "sched_id": 1 }
    include "connection.php";
    $data = json_decode($json, true);
    $schedId = $data["sched_id"];
    try {
      $sql = "DELETE FROM tblfacultyschedule WHERE sched_id = :schedId";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(":schedId", $schedId);
      $stmt->execute();
      return $stmt->rowCount() > 0 ? 1 : 0;
    } catch (PDOException $e) {
      return $e;
    }
  }

  function changeProfilePicture($json)
  {
    include "connection.php";
    $data = json_decode($json, true);
    $userId = $data["userId"];

    $sql = "SELECT user_image FROM tbluser WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldImage = $current ? $current['user_image'] : null;

    // Upload new image
    $returnValueImage = uploadImage();
    switch ($returnValueImage) {
      case 2:
        return 2; // invalid file type
      case 3:
        return 3; // upload error
      case 4:
        return 4; // file too big
      default:
        break;
    }

    $sql = "UPDATE tbluser SET user_image = :image WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":image", $returnValueImage);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      // Delete old image if not default
      if (!empty($oldImage) && $oldImage !== 'emptyImage.jpg') {
        $oldImagePath = __DIR__ . '/images/' . $oldImage;
        if (file_exists($oldImagePath)) {
          unlink($oldImagePath);
        }
      }
      return 1;
    } else {
      return 0;
    }
  }

  function updateFaculty($json)
  {
    include "connection.php";
    $data = json_decode($json, true);

    $userId = $data["userId"];

    if (recordExistsExceptSelf($data["email"], "tbluser", "user_email", $userId, $conn)) {
      return -1; // Email already exists
    }

    if (recordExistsExceptSelf($data["schoolId"], "tbluser", "user_schoolId", $userId, $conn)) {
      return -2; // School ID already exists
    }

    $sql = "UPDATE tbluser 
            SET user_firstName = :firstName,
                user_middleName = :middleName,
                user_lastName = :lastName,
                user_schoolId = :schoolId,
                user_email = :email
            WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":firstName", $data["firstName"]);
    $stmt->bindParam(":middleName", $data["middleName"]);
    $stmt->bindParam(":lastName", $data["lastName"]);
    $stmt->bindParam(":schoolId", $data["schoolId"]);
    $stmt->bindParam(":email", $data["email"]);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();

    return $stmt->rowCount() > 0 ? 1 : 0;
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

function recordExistsExceptSelf($value, $table, $column, $userId, $conn)
{
  $sql = "SELECT COUNT(*) FROM $table WHERE $column = :value AND user_id != :userId";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':value', $value);
  $stmt->bindParam(':userId', $userId);
  $stmt->execute();
  return $stmt->fetchColumn() > 0;
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
  //   case 2:
  //     return 2; // invalid file type
  //   case 3:
  //     return 3; // upload error
  //   case 4:
  //     return 4; // file too big
  //   default:
  //     break;
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
  case "getFacultyProfile":
    echo json_encode($admin->getFacultyProfile($json));
    break;
  case "updateSchedule":
    echo $admin->updateSchedule($json);
    break;
  case "deleteSchedule":
    echo $admin->deleteSchedule($json);
    break;
  case "changeProfilePicture":
    echo $admin->changeProfilePicture($json);
    break;
  case "updateFaculty":
    echo $admin->updateFaculty($json);
    break;
  default:
    echo "WALAY '$operation' NGA OPERATION SA UBOS HAHAHAHA BOBO";
    break;
}
