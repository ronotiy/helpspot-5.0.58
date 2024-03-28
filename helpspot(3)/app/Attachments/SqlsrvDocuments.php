<?php

namespace HS\Attachments;

class SqlsrvDocuments extends BaseDocuments
{
    /**
     * Retrieve a single binary from the database.
     * @param $table
     * @param $column
     * @param $identifier
     * @param $id
     * @return mixed
     */
    public function getBinary($table, $column, $identifier, $id)
    {
        $pdo = $this->pdo();
        $query = "SELECT $column FROM $table WHERE $identifier = ?";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $stmt->bindColumn(1, $binaryFile, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $stmt->fetch(\PDO::FETCH_BOUND);

        return $binaryFile;
    }

    /**
     * Save a binary to the database.
     * @param $table
     * @param $column
     * @param $binary
     * @return string
     */
    public function putBinary($table, $column, $identifier, $id, $binary)
    {
        $pdo = $this->pdo();
        $query = "UPDATE $table SET $column = CONVERT(VARBINARY(MAX), ?) WHERE $identifier = ?";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $binary, \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
        $stmt->bindParam(2, $id);
        $stmt->execute();

        return $pdo->lastInsertId();
    }
}
