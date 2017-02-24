<?php
namespace App\Model;
use \Phalcon\Db;

class Base extends \App\Model\Db
{
    /**
     * 查询一行数据
     * @param $where int|array
     * @param string $fields
     */
    public function fetchOne($where, $fields = "*")
    {
        $this->conn();
        $binds = [];
        $sql = "SELECT {$fields} FROM {$this->tb}";
        if (is_array($where)) {
            list($wheres, $binds) = $this->_parseWhere($where);
            if ($wheres) {
                $sql .= " WHERE" . join(' AND ', $wheres);
            }
        } else {
            $sql .= " WHERE `id` = ?";
            $binds = [$where];
        }
        $sql .= " LIMIT 1";
        return $this->db->fetchOne($sql, Db::FETCH_ASSOC, $binds);
    }

    /**
     * 查询多行数据
     * @param array $where
     * @param array $orders
     * @param int $offset
     * @param int $limit
     * @param string $fields
     * @return mixed
     */
    public function fetchAll(array $where, array $orders = [], $offset = 0, $limit = 10, $fields = "*")
    {
        $this->conn();
        $binds = [];
        $sql = "SELECT {$fields} FROM {$this->tb}";
        if ($where) {
            list($wheres, $binds) = $this->_parseWhere($where);
            if ($wheres) {
                $sql .= " WHERE" . join(' AND ', $wheres);
            }
        }
        if ($orders) {
            $sql .= " ORDER BY " . join(', ', $orders);
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }
        return $this->db->fetchAll($sql, Db::FETCH_ASSOC, $binds);
    }

    /**
     * 查询条数
     * @param $where
     * @return int
     */
    public function count($where)
    {
        $this->conn();
        $binds = [];
        $sql = "SELECT count(*) AS count FROM {$this->tb}";
        if (is_array($where)) {
            list($wheres, $binds) = $this->_parseWhere($where);
            if ($wheres) {
                $sql .= " WHERE" . join(' AND ', $wheres);
            }
        } else {
            $sql .= " WHERE `id` = ?";
            $binds = [$where];
        }
        $sql .= " LIMIT 1";
        $row =  $this->db->fetchOne($sql, Db::FETCH_ASSOC, $binds);
        return isset($row['count']) ? intval($row['count']) : 0;
    }

    /**
     *  创建数据
     * @param array $data
     * @return mixed
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException("invalid data for creating");
        }

        $this->conn(true);
        $success = $this->db->insertAsDict($this->tb, $data);
        return $this->db->lastInsertId();
    }

    /**
     * 更新数据
     * @param $where
     * @param array $data
     */
    public function update($where, array $data)
    {
        $this->conn(true);
        $whereString = $this->_parseWhereAsString($where);
        $success = $this->db->updateAsDict($this->tb, $data, $whereString);
        return $this->db->affectedRows();
    }

    /**
     * 硬删除
     * @param $where
     * @return mixed
     */
    public function delete($where)
    {
        $this->conn(true);
        $whereString = $this->_parseWhereAsString($where);
        $success = $this->db->delete($this->tb, $whereString);
        return $this->db->affectedRows();
    }

    /**
     * 自增、自减
     * eg: inc(['id' => 1], ['hot', 'click' => +5, 'vote' => -2])
     * eg: inc(['id' => 1], 'hot')
     * @param $where
     * @param $fields
     * @return mixed
     */
    public function inc($where, $fields)
    {
        $this->conn(true);
        if (empty($fields)) {
            throw new \InvalidArgumentException("invalid fields for inc");
        }

        $whereString = '';
        $wheres = $binds =  $setters = [];
        if (is_array($where)) {
            list($wheres, $binds) = $this->_parseWhere($where);
            if ($wheres) {
                $whereString .= " WHERE" . join(' AND ', $wheres);
            }
        } else {
            $whereString .= " WHERE `id` = ?";
            $binds = [$where];
        }

        if (is_string($fields)) {
            $setters[] = "`{$fields}` = `{$fields}` + 1";
        } elseif (is_array($fields)) {
            foreach ($fields as $field => $number) {
                if (is_numeric($field)) {
                    $setters[] = "`{$number}` = `{$number}` + 1";
                    continue;
                }
                $number = intval($number);
                if ($number > 0) {
                    $setters[] = "`{$field}` = `{$field}` + {$number}";
                } else {
                    $number = abs($number);
                    $setters[] = "`{$field}` = `{$field}` - {$number}";
                }
            }
        }

        $setter = join(', ', $setters);
        $sql = "UPDATE {$this->tb} SET {$setter} {$whereString} LIMIT 1";
        $this->db->execute($sql, $binds);
        return $this->db->affectedRows();
    }

    protected function _parseWhereAsString($where)
    {
        $str = '';
        $wheres = [];
        if(is_array($where)) {
            foreach ($where as $key => $val) {
                if($key == '_string') {
                    $wheres[] = $val;
                    continue;
                }
                $wheres[] = $this->_splitCondString($key, $val);
            }
            $str .= join(' AND ', $wheres);
        }else {
            $str = "id = {$where}";
        }
        return $str;
    }


    protected function _parseWhere($where)
    {
        if (empty($where)) {
            throw new \InvalidArgumentException("invalid params: where");
        }
        $wheres = $binds = [];
        foreach ($where as $first => $second) {
            if ($first == '_string') {
                $wheres[] = $second;
                continue;
            }
            list($field, $operator, $val, $bind) = $this->_splitCond($first, $second);
            $wheres[] = " {$field} {$operator} {$val}";
            $binds = array_merge($binds, $bind);
        }
        return [$wheres, $binds];
    }

    protected function _splitCond($first, $second)
    {
        $arr = explode(' ', $first);
        $field = array_shift($arr);
        $len = count($arr);
        if ($len >= 1) {
            $operator = join(' ', $arr);
        } else {
            $operator = '=';
        }

        if (strtoupper($operator) == 'BETWEEN') {
            $val = "? AND ?";
            $bind = [$second[0], $second[1]];
        } else {
            if (is_array($second)) {
                $val = '(' . join(', ', array_pad([], count($second), '?')) . ')';
                $bind = $second;
            } else {
                $val = '?';
                $bind = [$second];
            }
        }
        return [$field, $operator, $val, $bind];
    }

    protected function _splitCondString($first, $second)
    {
        $arr = explode(' ', trim($first));
        $field = array_shift($arr);
        $len = count($arr);
        if ($len >= 1) {
            $operator = join(' ', $arr);
        } else {
            $operator = '=';
        }

        if (strtoupper($operator) == 'BETWEEN') {
            $val = "{$second[0]} AND {$second[0]}";
        } else {
            if (is_array($second)) {
                $val = '(' . join(', ', $second) . ')';
            } else {
                $val = "'$second'";
            }
        }
        return "$field $operator $val";
    }
}