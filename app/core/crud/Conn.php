<?php

namespace app\core\crud;

use app\helpers\Date;
use app\helpers\Number;
use Exception;
use PDO;
use PDOException;

abstract class Conn
{

    /**
     * @var array
     */
    private static $tablesInfo;

    /**
     * @var array
     */
    private static $config;

    /**
     * @return array
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Define as configurações de conexão da base de dados
     * @param array $config
     */
    public static function setConfig(array $config = null)
    {
        self::$config = $config;
    }

    public static function getDataBaseName()
    {
        return self::$config['database'];
    }

    /**
     * Retorna o valor do auto increment da tabela
     * @param string $table
     * @return int
     */
    public static function getAutoIncrement($table)
    {
        $sql = self::getConn();
        $prepare = $sql->prepare("SHOW TABLE STATUS LIKE :table");
        $prepare->bindValue(':table', self::getTableName($table, false));
        $prepare->execute();
        if ($prepare->rowCount() > 0) {
            $result = $prepare->fetch();
            return (int)$result['Auto_increment'];
        }
        return 0;
    }

    /**
     * Retorna um objeto PDO Singleton Pattern.
     * @return PDO
     */
    public static function getConn()
    {
        return self::pdoConnect();
    }

    /**
     * Iniciar uma conexão com o banco de dados e retorna um objeto PDO.
     * @return PDO
     */
    private static function pdoConnect()
    {
        static $conn;
        if (!$conn) {
            try {
                if (!self::$config) {
                    self::$config = app_config('db' . (IS_LOCAL ? '-local' : null));
                }

                $dsn = 'mysql:host=' . self::$config['host'] . ';dbname=' . self::$config['database'] . ';charset=utf8';
                $options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'];

                $conn = new PDO($dsn, self::$config['username'], self::$config['password'], $options);
                $conn->exec('SET NAMES utf8');
                $conn->exec('SET CHARACTER SET utf8');
                try {
                    $conn->exec('SET time_zone = \'' . date('P') . '\'');
                } catch (\Throwable $tzError) {
                    // Log timezone error for debugging (MySQL timezone tables may not be populated)
                    error_log('MySQL timezone setting failed: ' . $tzError->getMessage() . ' - Using server default timezone. Consider running mysql_tzinfo_to_sql to populate timezone tables.');
                }

                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {
                error_log('Database Connection Error: ' . $e->getMessage());
                throw new Exception('Não foi possível conectar a base de dados: ' . $e->getMessage());
            } catch (Exception $e) {
                error_log('Database Connection Error: ' . $e->getMessage());
                throw new Exception('Não foi possível conectar a base de dados: ' . $e->getMessage());
            }
        }


        return $conn;
    }

    /**
     * @param string $table
     * @return mixed|string
     */
    public static function getTableName($table)
    {
        return $table;

        $names = explode('.', $table);

        $table = str_replace('`', '', end($names));

        if (!empty(self::$config['prefixo']) and strpos($table, self::$config['prefixo']) !== 0) {
            $table = self::$config['prefixo'] . $table;
        }

        if (count($names) == 2) {
            $table = "{$table} AS {$names[0]}";
        }

        return (string)$table;
    }

    /**
     * Verifica se uma determinada tabela existe na base de dados.
     * @return boolean
     */
    public static function tableExsits($table)
    {
        return (boolean)in_array(self::getTableName($table), self::getTables());
    }

    /**
     * Lista de tabelas no banco de dados
     * @return array
     */
    public static function getTables()
    {
        static $tables = [];

        if (!$tables) {
            $prepare = self::getConn()->prepare("SHOW TABLES FROM `" . self::$config['database'] . "`");
            $prepare->setFetchMode(PDO::FETCH_ASSOC);
            $prepare->execute();
            $result = $prepare->fetchAll();
            foreach ($result as $key => $value) {
                $result[$key] = current($value);
            }
        }
        return $result;
    }

    /**
     * Retorna o tipo de valor do parâmetro
     * @param mixed $value
     * @return int
     */
    public static function getParamType($value)
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        } else if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } else if (is_null($value)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    /**
     *
     * @param string $Table
     * @param array $values
     * @return array
     */
    public static function format_values($Table, array $values = null)
    {

        if (is_null($values)) {
            return $values;
        }

        $infoTable = self::getTableInfo($Table);

        $result = [];

        foreach ($infoTable as $info) {

            $field = $info['Field'];

            // A chave auto_increment deve ser omitida quando chega vazia.
            // Converter null para '' faz o MySQL em modo estrito rejeitar o INSERT
            // (SQLSTATE 1366) em qualquer formulário que salve um novo registro.
            if (
                array_key_exists($field, $values)
                && stripos((string)($info['Extra'] ?? ''), 'auto_increment') !== false
                && ($values[$field] === null || trim((string)$values[$field]) === '')
            ) {
                continue;
            }

            # Verificando campo
            if (array_key_exists($field, $values)) {

                # Valor passado
                $value = $values[$field];

                # Tipo de dado
                $type = strtolower(current(explode('(', $info['Type'])));

                $isNullOrEmpty = ($value === null || (is_string($value) && trim($value) === ''));

                # Formatando tipos de valores não vazios
                if (!$isNullOrEmpty) {
                    switch ($type) {
                        case 'date':
                            $value = Date::data((string)$value);
                            break;
                        case 'tinyint':
                        case 'smallint':
                        case 'mediumint':
                        case 'bigint':
                        case 'int':
                            $value = Number::int($value);
                            break;
                        case 'decimal':
                        case 'float':
                        case 'double':
                        case 'real':
                            $value = Number::float($value);
                            break;
                        case 'datetime':
                        case 'timestamp':
                            $value = Date::timestamp((string)$value);
                            break;
                        case 'time':
                            $value = Date::time((string)$value);
                            break;
                    }
                }

                # Se o valor ficou vazio ou nulo
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    if ($info['Null'] == 'NO') {
                        if (in_array($type, ['tinyint', 'smallint', 'mediumint', 'bigint', 'int', 'decimal', 'float', 'double', 'real'], true)) {
                            // Campos numéricos vazios devem usar o DEFAULT da tabela;
                            // sem DEFAULT, grava zero para evitar enviar '' ao MySQL.
                            if (array_key_exists('Default', $info) && $info['Default'] !== null) {
                                $value = $info['Default'];
                            } else {
                                $value = 0;
                            }
                        } else if (in_array($type, ['date', 'datetime', 'timestamp', 'time'], true)) {
                            // Datas vazias em MySQL estrito não aceitam ''. Usar DEFAULT ou NULL.
                            if (array_key_exists('Default', $info) && $info['Default'] !== null && $info['Default'] !== '') {
                                $value = $info['Default'];
                            } else {
                                $value = null;
                            }
                        } else {
                            $value = '';
                        }
                    } else {
                        $value = null;
                    }
                }

                # Setando valor na array
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * Retorna todas as informações de coluna da tabela informada.
     * @param string $table
     * @return array
     */
    public static function getTableInfo($table = null)
    {
        if ($table == null) {
            return self::$tablesInfo;
        }
        $table = self::getTableName($table);
        if (!empty(self::$tablesInfo[$table])) {
            return self::$tablesInfo[$table];
        }
        $sql = self::getConn();
        $prepare = $sql->prepare("SHOW COLUMNS FROM `{$table}`");
        $prepare->setFetchMode(PDO::FETCH_ASSOC);
        $prepare->execute();
        return self::$tablesInfo[$table] = $prepare->fetchAll();
    }

    /**
     * Iniciar uma nova transação
     */
    public static function startTransaction()
    {
        self::getConn()->exec('SET autocommit=0;');
        self::getConn()->beginTransaction();
    }

    /**
     * Reverte a transação atual, cancelando as alterações
     */
    public static function rollBack()
    {
        self::getConn()->rollBack();
        self::getConn()->exec('SET autocommit=1;');
    }

    /**
     * Efetiva a transação corrente, fazendo suas mudanças permanentes.
     */
    public static function commit()
    {
        self::getConn()->commit();
        self::getConn()->exec('SET autocommit=1;');
    }

}
    
