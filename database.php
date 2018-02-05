<?php

// Calling functions
require 'functions.php';

// Calling dbconnect
require 'dbconnect.php';

/*
  Database Manipulation Function
  Time takken on this function: 220hour
    - Note: If you trying to edit this functions please update the time takken.
    - Caution: This things might make whole apps broken. Please bercarefull!

  refvalues($arr)
  Usage: I forget what it for

  SQL INSERT - insertdatabase($tblname, $dbcols, $dbdatas, $types)
  SQL SET - updatedatabase($tblname, $dbcols, $dbdatas, $types, $colname, $valueselect, $operator = 'unset')
  SQL DELETE - deletedatabase($tblname, $colname, $valueselect)
  SQL SELECT - selectdatabase($tblname, $returntype = 'int', $colname = 'unset', $valueselect  = 'unset', $operator = 'unset', $returnsize = 'unset', $types = 'unset')

*/

// Using INSERT
function insertdatabase($tblname, $dbcols, $dbdatas, $types) {
  if (count($dbcols) == count($dbdatas)) {
    $conn = dbconnect();
    if ($conn) {
      $sql = 'INSERT INTO ' . $tblname . ' (' . implode(', ', $dbcols) . ') VALUES (' . str_repeat('?, ', count($dbdatas)-1) . '?)';
      if ($stmt = $conn->prepare($sql)) {
        $param = array_merge(array(implode('', $types)), $dbdatas);
        call_user_func_array(array($stmt, 'bind_param'), refvalues($param));
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

// Using SET
function updatedatabase($tblname, $dbcols, $dbdatas, $types, $colname, $valueselect, $operator = 'unset') {
  if (is_array($colname) && (count($colname) != count($valueselect))) {
    debugerror('Error occured: Array not match!');
  }
  if (count($dbcols) == count($dbdatas)) {
    $conn = dbconnect();
    if ($conn) {
      $sql = 'UPDATE ' . $tblname . ' SET ' . implode(' = ?, ', $dbcols) . ' = ? WHERE ' . (is_array($colname) ? implode(' = ? ' . $operator . ' ', $colname) : $colname) . ' = ?';
      if ($stmt = $conn->prepare($sql)) {
        $param = array_merge_recursive(array(implode('', $types).'s'), $dbdatas, (is_array($valueselect) ? $valueselect : array($valueselect)));
        call_user_func_array(array($stmt, 'bind_param'), refvalues($param));
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

// Using DELETE
function deletedatabase($tblname, $colname, $valueselect) {
  $conn = dbconnect();
  if ($conn) {
    $sql = 'DELETE FROM ' . $tblname . ' WHERE ' . $colname . ' = ?';
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param('s', $valueselect);
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

// Using SELECT
function selectdatabase($tblname, $returntype = 'int', $colname = 'unset', $valueselect  = 'unset', $operator = 'unset', $returnsize = 'unset', $types = 'unset') {
  $conn = dbconnect();
  if ($conn) {
    $sql = 'SELECT * FROM ' . $tblname;
    if ($colname != 'unset') {
      $sql .= ' WHERE ' . (is_array($colname) ? implode(' = ? ' . $operator . ' ', $colname) : $colname) . ' = ?';
    }
    if ($stmt = $conn->prepare($sql)) {
      if ($colname != 'unset') {
        if (is_array($colname)) {
          $param = array_merge(array(implode('', $types)), $valueselect);
          call_user_func_array(array($stmt, 'bind_param'), refvalues($param));
        } else {
          $stmt->bind_param('s', $valueselect);
        }
      }
      if ($stmt->execute()) {
        $stmt->store_result();
        $recordnum = $stmt->num_rows;
        if ($returntype == 'int') {
          return $recordnum;
        } elseif ($returntype == 'array') {
          $metadata = $stmt->result_metadata()->fetch_fields();
          if ($colname == 'unset' || $returnsize == 'plural') {
            $recordcount = 0;
            while ($recordcount < $recordnum) {
              $params = array();
              $values = array();
              foreach ($metadata as $object) {
                $params[] = &$values[$object->orgname];
              }
              call_user_func_array(array($stmt, 'bind_result'), $params);
              $stmt->fetch();
              $arrayval[$tblname . 'Row' . $recordcount] = $values;
              $recordcount++;
            }
            return $arrayval;
          } else {
            $params = array();
            $values = array();
            foreach ($metadata as $object) {
              $params[] = &$values[$object->orgname];
            }
            call_user_func_array(array($stmt, 'bind_result'), $params);
            $stmt->fetch();
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
