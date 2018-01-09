function refValues($arr){
  if (strnatcmp(phpversion(),'5.3') >= 0) {
    $refs = array();
    foreach($arr as $key => $value)
      $refs[$key] = &$arr[$key];
    return $refs;
  }
  return $arr;
}

function insertdatabase($dbname, $dbcols, $dbdatas, $types) {
  if (count($dbcols) == count($dbdatas)) {
    $conn = dbconnect();
    if ($conn) {
      $sql = 'INSERT INTO ' . $dbname . ' (' . implode(', ', $dbcols) . ') VALUES (' . str_repeat('?, ', count($dbdatas)-1) . '?)';
      if ($stmt = $conn->prepare($sql)) {
        $param = array_merge(array(implode('', $types)), $dbdatas);
        call_user_func_array(array($stmt, 'bind_param'), refValues($param));
        if ($stmt->execute()) {
          return true;
        } else {
          debugerror('Error occured: ' . htmlspecialchars($stmt->error));
        }
      } else {
        debugerror('Error occured: ' . htmlspecialchars($conn->error));
      }
    }
    dbclose($conn);
  } else {
    debugerror('Error occured: Array not match!');
  }
}

function updatedatabase($dbname, $dbcols, $dbdatas, $types, $keycols, $keydata, $operator = 'AND') {
  if (is_array($keycols) && (count($keycols) != count($keydata))) {
    debugerror('Error occured: Array not match!');
  }
  if (count($dbcols) == count($dbdatas)) {
    $conn = dbconnect();
    if ($conn) {
      $sql = 'UPDATE ' . $dbname . ' SET ' . implode(' = ?, ', $dbcols) . ' = ? WHERE ' . (is_array($keycols) ? implode(' = ? ' . $operator . ' ', $keycols) : $keycols) . ' = ?';
      if ($stmt = $conn->prepare($sql)) {
        $param = array_merge_recursive(array(implode('', $types).'s'), $dbdatas, (is_array($keydata) ? $keydata : array($keydata)));
        call_user_func_array(array($stmt, 'bind_param'), refValues($param));
        if ($stmt->execute()) {
          return true;
        } else {
          debugerror('Error occured: ' . htmlspecialchars($stmt->error));
        }
      } else {
        debugerror('Error occured: ' . htmlspecialchars($conn->error));
      }
    }
    dbclose($conn);
  } else {
    debugerror('Error occured: Array not match!');
  }
}

function deletedatabase($dbname, $keycols, $keydata) {
  $conn = dbconnect();
  if ($conn) {
    $sql = 'DELETE FROM ' . $dbname . ' WHERE ' . $keycols . ' = ?';
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param('s', $keydata);
      if ($stmt->execute()) {
        return true;
      } else {
        debugerror('Error occured: ' . htmlspecialchars($stmt->error));
      }
    } else {
      debugerror('Error occured: ' . htmlspecialchars($conn->error));
    }
  }
  dbclose($conn);
}

function selectdatabase($dbname, $returntype = 'int', $keycols = 'unset', $keydata  = 'unset', $operator = 'AND', $returnsize = 'plural', $types = 'unset') {
  $conn = dbconnect();
  if ($conn) {
    $sql = 'SELECT * FROM ' . $dbname;
    if ($keycols != 'unset') {
      $sql .= ' WHERE ' . (is_array($keycols) ? implode(' = ? ' . $operator . ' ', $keycols) : $keycols) . ' = ?';
    }
    if ($stmt = $conn->prepare($sql)) {
      if ($keycols != 'unset') {
        if (is_array($keycols)) {
          $param = array_merge(array(implode('', $types)), $keydata);
          call_user_func_array(array($stmt, 'bind_param'), refValues($param));
        } else {
          $stmt->bind_param('s', $keydata);
        }
      }
      if ($stmt->execute()) {
        $stmt->store_result();
        $recordnum = $stmt->num_rows;
        if ($returntype == 'int') {
          return $recordnum;
        } elseif ($returntype == 'array') {
          $metadata = $stmt->result_metadata()->fetch_fields();
          $params = array();
          $values = array();
          foreach ($metadata as $object) {
            $params[] = &$values[$object->orgname];
          }
          call_user_func_array(array($stmt, 'bind_result'), $params);
          $stmt->fetch();
          $recordcount = 0;
          if ($keycols != 'unset') {
            while ($recordcount < $recordnum) {
              $arrayval[$recordcount] = $values;
              $recordcount++;
            }
            return $arrayval;
          } else {
            return $values;
          }
          $stmt->free_result();
        }
        $stmt->bind_result($idsession, $username, $sessionkey, $loginip, $expirytime, $createdtime);
        return true;
      } else {
        debugerror('Error occured: ' . htmlspecialchars($stmt->error));
      }
    } else {
      debugerror('Error occured: ' . htmlspecialchars($conn->error));
    }
  }
  dbclose($conn);
}
